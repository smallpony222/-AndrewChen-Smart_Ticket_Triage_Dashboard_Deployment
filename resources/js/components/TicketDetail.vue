<template>
  <div class="ticket-detail">
    <div class="ticket-detail__header">
      <div class="ticket-detail__breadcrumb">
        <router-link to="/tickets" class="breadcrumb-link">← Back to Tickets</router-link>
      </div>
      <h2 class="ticket-detail__title">Ticket Details</h2>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="ticket-detail__loading">
      <div class="loading-spinner"></div>
      <p>Loading ticket...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="ticket-detail__error">
      <div class="error-state">
        <div class="error-state__icon">⚠️</div>
        <h3 class="error-state__title">Error Loading Ticket</h3>
        <p class="error-state__message">{{ error }}</p>
        <button @click="loadTicket" class="btn btn--primary">Try Again</button>
      </div>
    </div>

    <!-- Ticket Content -->
    <div v-else-if="ticket" class="ticket-detail__content">
      <!-- Ticket Info -->
      <div class="ticket-info">
        <div class="ticket-info__main">
          <h3 class="ticket-info__subject">{{ ticket.subject }}</h3>
          <div class="ticket-info__meta">
            <span class="meta-item">
              <strong>ID:</strong> {{ ticket.id }}
            </span>
            <span class="meta-item">
              <strong>Created:</strong> {{ formatDate(ticket.created_at) }}
            </span>
            <span class="meta-item">
              <strong>Updated:</strong> {{ formatDate(ticket.updated_at) }}
            </span>
          </div>
        </div>
        
        <div class="ticket-info__actions">
          <button 
            @click="classifyTicket"
            :disabled="classifying"
            class="btn btn--secondary"
          >
            <span v-if="classifying" class="btn-spinner"></span>
            {{ classifying ? 'Classifying...' : 'Run Classification' }}
          </button>
        </div>
      </div>

      <!-- Ticket Body -->
      <div class="ticket-body">
        <h4 class="ticket-body__title">Description</h4>
        <div class="ticket-body__content">{{ ticket.body }}</div>
      </div>

      <!-- AI Classification -->
      <div v-if="ticket.explanation || ticket.confidence" class="ticket-classification">
        <h4 class="ticket-classification__title">AI Classification</h4>
        <div class="classification-info">
          <div v-if="ticket.confidence" class="classification-confidence">
            <span class="confidence-label">Confidence:</span>
            <div class="confidence-bar">
              <div 
                class="confidence-bar__fill" 
                :style="{ width: `${ticket.confidence * 100}%` }"
              ></div>
              <span class="confidence-percentage">{{ Math.round(ticket.confidence * 100) }}%</span>
            </div>
          </div>
          <div v-if="ticket.explanation" class="classification-explanation">
            <span class="explanation-label">Explanation:</span>
            <p class="explanation-text">{{ ticket.explanation }}</p>
          </div>
        </div>
      </div>

      <!-- Edit Form -->
      <div class="ticket-edit">
        <h4 class="ticket-edit__title">Edit Ticket</h4>
        <form @submit.prevent="updateTicket" class="edit-form">
          <div class="form-row">
            <div class="form-group">
              <label class="form-group__label">Status</label>
              <select 
                v-model="editForm.status" 
                class="form-group__select"
                :class="{ 'form-group__select--error': validationErrors.status }"
              >
                <option value="open">Open</option>
                <option value="pending">Pending</option>
                <option value="closed">Closed</option>
              </select>
              <span v-if="validationErrors.status" class="form-error">{{ validationErrors.status }}</span>
            </div>

            <div class="form-group">
              <label class="form-group__label">Category</label>
              <select 
                v-model="editForm.category" 
                class="form-group__select"
                :class="{ 'form-group__select--error': validationErrors.category }"
              >
                <option value="">Uncategorized</option>
                <option value="technical">Technical</option>
                <option value="billing">Billing</option>
                <option value="general">General</option>
                <option value="bug_report">Bug Report</option>
                <option value="feature_request">Feature Request</option>
                <option value="account">Account</option>
                <option value="other">Other</option>
              </select>
              <span v-if="validationErrors.category" class="form-error">{{ validationErrors.category }}</span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-group__label">Internal Note</label>
            <textarea 
              v-model="editForm.note"
              rows="4"
              maxlength="5000"
              class="form-group__textarea"
              :class="{ 'form-group__textarea--error': validationErrors.note }"
              placeholder="Add internal notes for team reference..."
            ></textarea>
            <div class="form-group__meta">
              <span v-if="validationErrors.note" class="form-error">{{ validationErrors.note }}</span>
              <span class="char-count">{{ (editForm.note || '').length }}/5000</span>
            </div>
          </div>

          <div class="form-actions">
            <button 
              type="submit" 
              :disabled="updating || !hasChanges"
              class="btn btn--primary"
            >
              {{ updating ? 'Updating...' : 'Update Ticket' }}
            </button>
            <button 
              type="button" 
              @click="resetForm"
              :disabled="updating"
              class="btn btn--secondary"
            >
              Reset
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TicketDetail',
  props: {
    id: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      ticket: null,
      loading: false,
      error: null,
      classifying: false,
      updating: false,
      editForm: {
        status: '',
        category: '',
        note: ''
      },
      originalForm: {},
      validationErrors: {}
    }
  },
  computed: {
    hasChanges() {
      return (
        this.editForm.status !== this.originalForm.status ||
        this.editForm.category !== this.originalForm.category ||
        this.editForm.note !== this.originalForm.note
      )
    }
  },
  mounted() {
    this.loadTicket()
  },
  watch: {
    id() {
      this.loadTicket()
    },
    // Live validation
    'editForm.status'() {
      this.validateField('status')
    },
    'editForm.category'() {
      this.validateField('category')
    },
    'editForm.note'() {
      this.validateField('note')
    }
  },
  methods: {
    async loadTicket() {
      this.loading = true
      this.error = null
      try {
        const response = await this.$http.get(`/api/tickets/${this.id}`)
        this.ticket = response.data
        this.initializeForm()
      } catch (error) {
        console.error('Failed to load ticket:', error)
        if (error.response?.status === 404) {
          this.error = 'Ticket not found.'
        } else {
          this.error = 'Failed to load ticket. Please try again.'
        }
      } finally {
        this.loading = false
      }
    },

    initializeForm() {
      this.editForm = {
        status: this.ticket.status || '',
        category: this.ticket.category || '',
        note: this.ticket.note || ''
      }
      this.originalForm = { ...this.editForm }
      this.validationErrors = {}
    },

    async updateTicket() {
      if (!this.validateForm()) {
        return
      }

      this.updating = true
      try {
        const updateData = {}
        
        // Only send changed fields
        if (this.editForm.status !== this.originalForm.status) {
          updateData.status = this.editForm.status
        }
        if (this.editForm.category !== this.originalForm.category) {
          updateData.category = this.editForm.category || null
        }
        if (this.editForm.note !== this.originalForm.note) {
          updateData.note = this.editForm.note || null
        }

        const response = await this.$http.patch(`/api/tickets/${this.id}`, updateData)
        this.ticket = response.data
        this.originalForm = { ...this.editForm }
        
        this.$emitter.emit('showNotification', {
          type: 'success',
          message: 'Ticket updated successfully!'
        })
      } catch (error) {
        console.error('Failed to update ticket:', error)
        if (error.response?.data?.errors) {
          this.validationErrors = error.response.data.errors
        } else {
          this.$emitter.emit('showNotification', {
            type: 'error',
            message: 'Failed to update ticket. Please try again.'
          })
        }
      } finally {
        this.updating = false
      }
    },

    async classifyTicket() {
      this.classifying = true
      try {
        await this.$http.post(`/api/tickets/${this.id}/classify`)
        this.$emitter.emit('showNotification', {
          type: 'success',
          message: 'Classification job queued successfully!'
        })
        // Refresh the ticket after a short delay
        setTimeout(() => {
          this.loadTicket()
        }, 2000)
      } catch (error) {
        console.error('Failed to classify ticket:', error)
        this.$emitter.emit('showNotification', {
          type: 'error',
          message: 'Failed to queue classification job.'
        })
      } finally {
        this.classifying = false
      }
    },

    resetForm() {
      this.editForm = { ...this.originalForm }
      this.validationErrors = {}
    },

    validateForm() {
      this.validationErrors = {}
      let isValid = true

      // Validate status
      if (!this.editForm.status) {
        this.validationErrors.status = 'Status is required.'
        isValid = false
      } else if (!['open', 'pending', 'closed'].includes(this.editForm.status)) {
        this.validationErrors.status = 'Invalid status selected.'
        isValid = false
      }

      // Validate category
      if (this.editForm.category && !['technical', 'billing', 'general', 'bug_report', 'feature_request', 'account', 'other'].includes(this.editForm.category)) {
        this.validationErrors.category = 'Invalid category selected.'
        isValid = false
      }

      // Validate note
      if (this.editForm.note && this.editForm.note.length > 5000) {
        this.validationErrors.note = 'Note cannot exceed 5,000 characters.'
        isValid = false
      }

      return isValid
    },

    validateField(field) {
      // Clear existing error for this field
      if (this.validationErrors[field]) {
        delete this.validationErrors[field]
      }

      // Validate specific field
      switch (field) {
        case 'status':
          if (!this.editForm.status) {
            this.validationErrors.status = 'Status is required.'
          } else if (!['open', 'pending', 'closed'].includes(this.editForm.status)) {
            this.validationErrors.status = 'Invalid status selected.'
          }
          break
        case 'category':
          if (this.editForm.category && !['technical', 'billing', 'general', 'bug_report', 'feature_request', 'account', 'other'].includes(this.editForm.category)) {
            this.validationErrors.category = 'Invalid category selected.'
          }
          break
        case 'note':
          if (this.editForm.note && this.editForm.note.length > 5000) {
            this.validationErrors.note = 'Note cannot exceed 5,000 characters.'
          }
          break
      }
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleString()
    }
  }
}
</script>
