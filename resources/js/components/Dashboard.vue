<template>
  <div class="dashboard">
    <div class="dashboard__header">
      <h2 class="dashboard__title">Analytics Dashboard</h2>
      <button @click="refreshStats" :disabled="loading" class="btn btn--secondary">
        {{ loading ? 'Refreshing...' : 'Refresh' }}
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="dashboard__loading">
      <div class="loading-spinner"></div>
      <p>Loading dashboard data...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="dashboard__error">
      <div class="error-state">
        <div class="error-state__icon">⚠️</div>
        <h3 class="error-state__title">Error Loading Dashboard</h3>
        <p class="error-state__message">{{ error }}</p>
        <button @click="loadStats" class="btn btn--primary">Try Again</button>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="dashboard__content">
      <!-- Summary Cards -->
      <div class="dashboard__cards">
        <div class="stat-card">
          <div class="stat-card__icon">🎫</div>
          <div class="stat-card__content">
            <h3 class="stat-card__value">{{ stats.total_tickets || 0 }}</h3>
            <p class="stat-card__label">Total Tickets</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__icon">🤖</div>
          <div class="stat-card__content">
            <h3 class="stat-card__value">{{ stats.classification_stats?.ai_classified || 0 }}</h3>
            <p class="stat-card__label">AI Classified</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__icon">👤</div>
          <div class="stat-card__content">
            <h3 class="stat-card__value">{{ stats.classification_stats?.manually_classified || 0 }}</h3>
            <p class="stat-card__label">Manual Classification</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__icon">❓</div>
          <div class="stat-card__content">
            <h3 class="stat-card__value">{{ stats.classification_stats?.unclassified || 0 }}</h3>
            <p class="stat-card__label">Unclassified</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__icon">📊</div>
          <div class="stat-card__content">
            <h3 class="stat-card__value">{{ stats.classification_stats?.classification_rate || 0 }}%</h3>
            <p class="stat-card__label">Classification Rate</p>
          </div>
        </div>

        <div v-if="stats.classification_stats?.average_confidence" class="stat-card">
          <div class="stat-card__icon">🎯</div>
          <div class="stat-card__content">
            <h3 class="stat-card__value">{{ Math.round(stats.classification_stats.average_confidence * 100) }}%</h3>
            <p class="stat-card__label">Avg. Confidence</p>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="dashboard__charts">
        <!-- Status Distribution Chart -->
        <div class="chart-container">
          <h3 class="chart-container__title">Tickets by Status</h3>
          <div class="chart-wrapper">
            <canvas ref="statusChart" class="chart-canvas"></canvas>
          </div>
        </div>

        <!-- Category Distribution Chart -->
        <div class="chart-container">
          <h3 class="chart-container__title">Tickets by Category</h3>
          <div class="chart-wrapper">
            <canvas ref="categoryChart" class="chart-canvas"></canvas>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="dashboard__activity">
        <h3 class="dashboard__activity-title">Recent Activity</h3>
        <div v-if="stats.recent_activity && stats.recent_activity.length > 0" class="activity-list">
          <div 
            v-for="ticket in stats.recent_activity" 
            :key="ticket.id"
            class="activity-item"
          >
            <div class="activity-item__content">
              <router-link :to="`/tickets/${ticket.id}`" class="activity-item__link">
                {{ ticket.subject }}
              </router-link>
              <div class="activity-item__meta">
                <span class="status-badge" :class="`status-badge--${ticket.status}`">
                  {{ formatStatus(ticket.status) }}
                </span>
                <span v-if="ticket.category" class="category-badge">
                  {{ formatCategory(ticket.category) }}
                </span>
                <span class="activity-item__date">
                  {{ formatRelativeDate(ticket.created_at) }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="activity-empty">
          <p>No recent activity to display.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

export default {
  name: 'Dashboard',
  data() {
    return {
      stats: {},
      loading: false,
      error: null,
      statusChart: null,
      categoryChart: null
    }
  },
  mounted() {
    this.loadStats()
  },
  beforeUnmount() {
    if (this.statusChart) {
      this.statusChart.destroy()
    }
    if (this.categoryChart) {
      this.categoryChart.destroy()
    }
  },
  methods: {
    async loadStats() {
      this.loading = true
      this.error = null
      try {
        const response = await this.$http.get('/api/stats')
        this.stats = response.data
        
        // Wait for DOM update before creating charts
        this.$nextTick(() => {
          this.createCharts()
        })
      } catch (error) {
        console.error('Failed to load stats:', error)
        this.error = 'Failed to load dashboard data. Please try again.'
      } finally {
        this.loading = false
      }
    },

    async refreshStats() {
      await this.loadStats()
      this.$emitter.emit('showNotification', {
        type: 'success',
        message: 'Dashboard refreshed successfully!'
      })
    },

    createCharts() {
      // Add small delay to ensure DOM is fully rendered
      setTimeout(() => {
        this.createStatusChart()
        this.createCategoryChart()
      }, 100)
    },

    createStatusChart() {
      if (this.statusChart) {
        this.statusChart.destroy()
      }

      const ctx = this.$refs.statusChart?.getContext('2d')
      if (!ctx || !this.stats.tickets_by_status) return

      const statusData = this.stats.tickets_by_status
      const labels = Object.keys(statusData).map(this.formatStatus)
      const data = Object.values(statusData)

      const isDark = document.documentElement.getAttribute('data-theme') === 'dark'
      
      // Enhanced color scheme for better visibility
      const colors = {
        open: '#10B981',     // Green
        pending: '#F59E0B',  // Amber
        closed: '#6B7280'    // Gray
      }
      
      const backgroundColors = Object.keys(statusData).map(status => colors[status] || '#8B5CF6')
      
      this.statusChart = new Chart(ctx, {
        type: 'doughnut', // Changed to doughnut for modern look
        data: {
          labels,
          datasets: [{
            data,
            backgroundColor: backgroundColors,
            borderColor: isDark ? '#1F2937' : '#ffffff',
            borderWidth: 3,
            hoverBorderWidth: 4,
            hoverOffset: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '60%', // Creates the doughnut hole
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: isDark ? '#F3F4F6' : '#374151',
                padding: 20,
                font: {
                  size: 13,
                  weight: '500'
                },
                usePointStyle: true,
                pointStyle: 'circle'
              }
            },
            tooltip: {
              backgroundColor: isDark ? '#374151' : '#ffffff',
              titleColor: isDark ? '#F3F4F6' : '#374151',
              bodyColor: isDark ? '#F3F4F6' : '#374151',
              borderColor: isDark ? '#6B7280' : '#E5E7EB',
              borderWidth: 1,
              cornerRadius: 8,
              displayColors: true,
              callbacks: {
                label: function(context) {
                  const label = context.label || ''
                  const value = context.parsed
                  const total = context.dataset.data.reduce((a, b) => a + b, 0)
                  const percentage = ((value / total) * 100).toFixed(1)
                  return `${label}: ${value} tickets (${percentage}%)`
                }
              }
            }
          },
          animation: {
            animateRotate: true,
            animateScale: true,
            duration: 1000
          }
        }
      })
    },

    createCategoryChart() {
      if (this.categoryChart) {
        this.categoryChart.destroy()
      }

      const ctx = this.$refs.categoryChart?.getContext('2d')
      if (!ctx || !this.stats.tickets_by_category) return

      const categoryData = this.stats.tickets_by_category
      const labels = Object.keys(categoryData).map(this.formatCategory)
      const data = Object.values(categoryData)

      const isDark = document.documentElement.getAttribute('data-theme') === 'dark'

      // Enhanced gradient colors for each category
      const categoryColors = {
        technical: '#3B82F6',      // Blue
        billing: '#10B981',        // Green
        bug_report: '#EF4444',     // Red
        feature_request: '#8B5CF6', // Purple
        account: '#F59E0B',        // Amber
        general: '#6B7280',        // Gray
        other: '#EC4899',          // Pink
        uncategorized: '#94A3B8'   // Light Gray
      }

      const backgroundColors = Object.keys(categoryData).map(category => 
        categoryColors[category] || '#6366F1'
      )

      this.categoryChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Number of Tickets',
            data,
            backgroundColor: backgroundColors,
            borderColor: backgroundColors.map(color => color),
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
            hoverBackgroundColor: backgroundColors.map(color => color + 'CC'),
            hoverBorderWidth: 3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: isDark ? '#374151' : '#ffffff',
              titleColor: isDark ? '#F3F4F6' : '#374151',
              bodyColor: isDark ? '#F3F4F6' : '#374151',
              borderColor: isDark ? '#6B7280' : '#E5E7EB',
              borderWidth: 1,
              cornerRadius: 8,
              displayColors: true,
              callbacks: {
                title: function(context) {
                  return `Category: ${context[0].label}`
                },
                label: function(context) {
                  const value = context.parsed.y
                  const total = context.dataset.data.reduce((a, b) => a + b, 0)
                  const percentage = ((value / total) * 100).toFixed(1)
                  return `${value} tickets (${percentage}% of total)`
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                color: isDark ? '#9CA3AF' : '#6B7280',
                stepSize: 1,
                font: {
                  size: 12
                }
              },
              grid: {
                color: isDark ? '#374151' : '#E5E7EB',
                lineWidth: 1
              },
              title: {
                display: true,
                text: 'Number of Tickets',
                color: isDark ? '#F3F4F6' : '#374151',
                font: {
                  size: 13,
                  weight: '500'
                }
              }
            },
            x: {
              ticks: {
                color: isDark ? '#9CA3AF' : '#6B7280',
                maxRotation: 45,
                font: {
                  size: 11
                }
              },
              grid: {
                display: false
              },
              title: {
                display: true,
                text: 'Categories',
                color: isDark ? '#F3F4F6' : '#374151',
                font: {
                  size: 13,
                  weight: '500'
                }
              }
            }
          },
          animation: {
            duration: 1200,
            easing: 'easeOutQuart'
          },
          interaction: {
            intersect: false,
            mode: 'index'
          }
        }
      })
    },

    formatStatus(status) {
      return status.charAt(0).toUpperCase() + status.slice(1)
    },

    formatCategory(category) {
      if (category === 'uncategorized') return 'Uncategorized'
      return category.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
      ).join(' ')
    },

    formatRelativeDate(dateString) {
      const date = new Date(dateString)
      const now = new Date()
      const diffInHours = Math.floor((now - date) / (1000 * 60 * 60))
      
      if (diffInHours < 1) return 'Just now'
      if (diffInHours < 24) return `${diffInHours}h ago`
      
      const diffInDays = Math.floor(diffInHours / 24)
      if (diffInDays < 7) return `${diffInDays}d ago`
      
      return date.toLocaleDateString()
    }
  },
  watch: {
    // Re-create charts when theme changes
    '$root.isDark'() {
      this.$nextTick(() => {
        this.createCharts()
      })
    }
  }
}
</script>
