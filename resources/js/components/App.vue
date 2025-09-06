<template>
  <div class="app">
    <nav class="app__nav">
      <div class="nav__container">
        <div class="nav__brand">
          <h1 class="nav__title">Smart Ticket Triage</h1>
        </div>
        <ul class="nav__menu">
          <li class="nav__item">
            <router-link to="/tickets" class="nav__link" :class="{ 'nav__link--active': $route.name === 'tickets' }">
              Tickets
            </router-link>
          </li>
          <li class="nav__item">
            <router-link to="/dashboard" class="nav__link" :class="{ 'nav__link--active': $route.name === 'dashboard' }">
              Dashboard
            </router-link>
          </li>
        </ul>
        <div class="nav__theme">
          <button @click="toggleTheme" class="theme-toggle" :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
            {{ isDark ? '☀️' : '🌙' }}
          </button>
        </div>
      </div>
    </nav>

    <main class="app__main">
      <router-view />
    </main>

    <div v-if="notification" class="notification" :class="`notification--${notification.type}`">
      <div class="notification__content">
        <span class="notification__message">{{ notification.message }}</span>
        <button @click="clearNotification" class="notification__close">×</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'App',
  data() {
    return {
      isDark: false,
      notification: null
    }
  },
  mounted() {
    // Initialize theme from localStorage
    const savedTheme = localStorage.getItem('theme')
    if (savedTheme) {
      this.isDark = savedTheme === 'dark'
    } else {
      // Default to system preference
      this.isDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    }
    this.applyTheme()

    // Listen for global notification events using Vue 3 event emitter
    this.$emitter.on('showNotification', this.showNotification)
  },
  beforeUnmount() {
    // Clean up event listeners
    this.$emitter.off('showNotification', this.showNotification)
  },
  methods: {
    toggleTheme() {
      this.isDark = !this.isDark
      this.applyTheme()
      localStorage.setItem('theme', this.isDark ? 'dark' : 'light')
    },
    applyTheme() {
      document.documentElement.setAttribute('data-theme', this.isDark ? 'dark' : 'light')
    },
    showNotification(notification) {
      this.notification = notification
      // Auto-clear after 5 seconds
      setTimeout(() => {
        this.clearNotification()
      }, 5000)
    },
    clearNotification() {
      this.notification = null
    }
  }
}
</script>
