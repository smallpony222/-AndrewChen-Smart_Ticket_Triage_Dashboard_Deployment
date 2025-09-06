<template>
  <div class="ticket-list">
    <div class="ticket-list__header">
      <h2 class="ticket-list__title">Support Tickets</h2>
      <div class="ticket-list__actions">
        <button @click="showNewTicketModal = true" class="btn btn--primary">
          New Ticket
        </button>
        <button @click="exportTickets" class="btn btn--secondary" :disabled="loading">
          Export CSV
        </button>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="ticket-list__filters">
      <div class="filter-group">
        <label class="filter-group__label">Status:</label>
        <select v-model="filters.status" @change="loadTickets" class="filter-group__select">
          <option value="">All Statuses</option>
          <option value="open">Open</option>
          <option value="pending">Pending</option>
          <option value="closed">Closed</option>
        </select>
      </div>

      <div class="filter-group">
        <label class="filter-group__label">Category:</label>
        <select v-model="filters.category" @change="loadTickets" class="filter-group__select">
          <option value="">All Categories</option>
          <option value="technical">Technical</option>
          <option value="billing">Billing</option>
          <option value="general">General</option>
          <option value="bug_report">Bug Report</option>
          <option value="feature_request">Feature Request</option>
          <option value="account">Account</option>
          <option value="other">Other</option>
        </select>
      </div>

      <div class="filter-group filter-group--search">
        <label class="filter-group__label">Search:</label>
        <input 
          v-model="filters.search" 
          @input="debounceSearch"
          type="text" 
          placeholder="Search subject or body..."
          class="filter-group__input"
        >
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="ticket-list__loading">
      <div class="loading-spinner"></div>
      <p>Loading tickets...</p>
    </div>

    <!-- Tickets Table -->
    <div v-else-if="tickets.length > 0" class="ticket-list__table-container">
      <table class="ticket-table">
        <thead class="ticket-table__head">
          <tr>
            <th class="ticket-table__header">Subject</th>
            <th class="ticket-table__header">Status</th>
            <th class="ticket-table__header">Category</th>
            <th class="ticket-table__header">Confidence</th>
            <th class="ticket-table__header">Note</th>
            <th class="ticket-table__header">Actions</th>
          </tr>
        </thead>
        <tbody class="ticket-table__body">
          <tr v-for="ticket in tickets" :key="ticket.id" class="ticket-table__row">
            <td class="ticket-table__cell ticket-table__cell--subject">
              <router-link :to="`/tickets/${ticket.id}`" class="ticket-link">
                {{ ticket.subject }}
              </router-link>
            </td>
            <td class="ticket-table__cell">
              <span class="status-badge" :class="`status-badge--${ticket.status}`">
                {{ formatStatus(ticket.status) }}
              </span>
            </td>
            <td class="ticket-table__cell">
              <select 
                v-if="ticket.category || editingCategory === ticket.id"
                v-model="ticket.category"
                @change="updateTicketCategory(ticket)"
                @blur="editingCategory = null"
                class="category-select"
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
              <button 
                v-else
                @click="editingCategory = ticket.id; ticket.category = ''"
                class="btn btn--link btn--small"
              >
                Set Category
              </button>
            </td>
            <td class="ticket-table__cell">
              <div v-if="ticket.confidence" class="confidence-display">
                <span class="confidence-value">{{ Math.round(ticket.confidence * 100) }}%</span>
                <div 
                  v-if="ticket.explanation" 
                  class="confidence-tooltip"
                  :title="ticket.explanation"
                >
                  ℹ️
                </div>
              </div>
              <span v-else class="confidence-empty">—</span>
            </td>
            <td class="ticket-table__cell">
              <span v-if="ticket.note" class="note-badge" :title="ticket.note">📝</span>
              <span v-else class="note-empty">—</span>
            </td>
            <td class="ticket-table__cell ticket-table__cell--actions">
              <button 
                @click="classifyTicket(ticket)"
                :disabled="classifyingTickets.includes(ticket.id)"
                class="btn btn--small btn--secondary"
              >
                <span v-if="classifyingTickets.includes(ticket.id)" class="btn-spinner"></span>
                {{ classifyingTickets.includes(ticket.id) ? 'Classifying...' : 'Classify' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-else class="ticket-list__empty">
      <div class="empty-state">
        <div class="empty-state__icon">🎫</div>
        <h3 class="empty-state__title">No tickets found</h3>
        <p class="empty-state__message">
          {{ hasFilters ? 'Try adjusting your filters or search terms.' : 'Create your first support ticket to get started.' }}
        </p>
        <button v-if="!hasFilters" @click="showNewTicketModal = true" class="btn btn--primary">
          Create First Ticket
        </button>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.per_page" class="ticket-list__pagination">
      <div class="pagination">
        <button 
          @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page <= 1"
          class="pagination__btn pagination__btn--prev"
        >
          Previous
        </button>
        
        <span class="pagination__info">
          Page {{ pagination.current_page }} of {{ pagination.last_page }}
          ({{ pagination.total }} total)
        </span>
        
        <button 
          @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page >= pagination.last_page"
          class="pagination__btn pagination__btn--next"
        >
          Next
        </button>
      </div>
    </div>

    <!-- New Ticket Modal -->
    <div v-if="showNewTicketModal" class="modal-overlay" @click="closeModal">
      <div class="modal" @click.stop>
        <div class="modal__header">
          <h3 class="modal__title">Create New Ticket</h3>
          <button @click="closeModal" class="modal__close">×</button>
        </div>
        <form @submit.prevent="createTicket" class="modal__form">
          <div class="form-group">
            <label class="form-group__label">Subject *</label>
            <input 
              v-model="newTicket.subject"
              type="text" 
              required
              maxlength="255"
              class="form-group__input"
              placeholder="Brief description of the issue"
            >
          </div>
          <div class="form-group">
            <label class="form-group__label">Description *</label>
            <textarea 
              v-model="newTicket.body"
              required
              maxlength="10000"
              rows="6"
              class="form-group__textarea"
              placeholder="Detailed description of the issue"
            ></textarea>
          </div>
          <div class="form-group">
            <label class="form-group__label">Status</label>
            <select v-model="newTicket.status" class="form-group__select">
              <option value="open">Open</option>
              <option value="pending">Pending</option>
              <option value="closed">Closed</option>
            </select>
          </div>
          <div class="modal__actions">
            <button type="button" @click="closeModal" class="btn btn--secondary">
              Cancel
            </button>
            <button type="submit" :disabled="creatingTicket" class="btn btn--primary">
              {{ creatingTicket ? 'Creating...' : 'Create Ticket' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TicketList',
  data() {
    return {
      tickets: [],
      pagination: {},
      loading: false,
      filters: {
        status: '',
        category: '',
        search: ''
      },
      showNewTicketModal: false,
      newTicket: {
        subject: '',
        body: '',
        status: 'open'
      },
      creatingTicket: false,
      classifyingTickets: [],
      editingCategory: null,
      searchTimeout: null
    }
  },
  computed: {
    hasFilters() {
      return this.filters.status || this.filters.category || this.filters.search
    }
  },
  mounted() {
    this.loadTickets()
  },
  methods: {
    async loadTickets(page = 1) {
      this.loading = true
      try {
        const params = {
          page,
          per_page: 15,
          ...this.filters
        }
        
        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '') delete params[key]
        })

        const response = await this.$http.get('/api/tickets', { params })
        this.tickets = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total
        }
      } catch (error) {
        console.error('Failed to load tickets:', error)
        this.$emitter.emit('showNotification', {
          type: 'error',
          message: 'Failed to load tickets. Please try again.'
        })
      } finally {
        this.loading = false
      }
    },
    
    debounceSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.loadTickets()
      }, 500)
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.loadTickets(page)
      }
    },

    async createTicket() {
      this.creatingTicket = true
      try {
        await this.$http.post('/api/tickets', this.newTicket)
        this.closeModal()
        this.loadTickets()
        this.$emitter.emit('showNotification', {
          type: 'success',
          message: 'Ticket created successfully!'
        })
      } catch (error) {
        console.error('Failed to create ticket:', error)
        this.$emitter.emit('showNotification', {
          type: 'error',
          message: 'Failed to create ticket. Please try again.'
        })
      } finally {
        this.creatingTicket = false
      }
    },

    async classifyTicket(ticket) {
      this.classifyingTickets.push(ticket.id)
      try {
        await this.$http.post(`/api/tickets/${ticket.id}/classify`)
        this.$emitter.emit('showNotification', {
          type: 'success',
          message: 'Classification job queued successfully!'
        })
        // Refresh the ticket after a short delay to show updated classification
        setTimeout(() => {
          this.loadTickets()
        }, 2000)
      } catch (error) {
        console.error('Failed to classify ticket:', error)
        this.$emitter.emit('showNotification', {
          type: 'error',
          message: 'Failed to queue classification job.'
        })
      } finally {
        this.classifyingTickets = this.classifyingTickets.filter(id => id !== ticket.id)
      }
    },

    async updateTicketCategory(ticket) {
      try {
        await this.$http.patch(`/api/tickets/${ticket.id}`, {
          category: ticket.category
        })
        this.$emitter.emit('showNotification', {
          type: 'success',
          message: 'Category updated successfully!'
        })
      } catch (error) {
        console.error('Failed to update category:', error)
        this.$emitter.emit('showNotification', {
          type: 'error',
          message: 'Failed to update category.'
        })
        // Reload to revert changes
        this.loadTickets()
      }
    },

    async exportTickets() {
      try {
        const params = { ...this.filters }
        Object.keys(params).forEach(key => {
          if (params[key] === '') delete params[key]
        })

        const response = await this.$http.get('/api/tickets-export', { 
          params,
          responseType: 'blob'
        })
        
        const blob = new Blob([response.data], { type: 'text/csv' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `tickets_export_${new Date().toISOString().split('T')[0]}.csv`
        link.click()
        window.URL.revokeObjectURL(url)

        this.$emitter.emit('showNotification', {
          type: 'success',
          message: 'Tickets exported successfully!'
        })
      } catch (error) {
        console.error('Failed to export tickets:', error)
        this.$emitter.emit('showNotification', {
          type: 'error',
          message: 'Failed to export tickets.'
        })
      }
    },

    closeModal() {
      this.showNewTicketModal = false
      this.newTicket = {
        subject: '',
        body: '',
        status: 'open'
      }
    },

    formatStatus(status) {
      return status.charAt(0).toUpperCase() + status.slice(1)
    }
  }
}
</script>
