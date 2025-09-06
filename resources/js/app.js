import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import axios from 'axios'
import mitt from 'mitt'
import App from './components/App.vue'
import TicketList from './components/TicketList.vue'
import TicketDetail from './components/TicketDetail.vue'
import Dashboard from './components/Dashboard.vue'

// Create event emitter for Vue 3
const emitter = mitt()

// Configure axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'

// Get CSRF token
const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
}

// Configure routes
const routes = [
    { path: '/', redirect: '/tickets' },
    { path: '/tickets', component: TicketList, name: 'tickets' },
    { path: '/tickets/:id', component: TicketDetail, name: 'ticket-detail', props: true },
    { path: '/dashboard', component: Dashboard, name: 'dashboard' }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

// Create and mount the app
const app = createApp(App)
app.use(router)
app.config.globalProperties.$http = axios
app.config.globalProperties.$emitter = emitter
app.mount('#app')
