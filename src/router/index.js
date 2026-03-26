import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '../views/Dashboard.vue'
import Classes from '../views/Classes.vue'
import Sessions from '../views/Sessions.vue'
import Reports from '../views/Reports.vue'

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: Dashboard },
    { path: '/classes', component: Classes },
    { path: '/sessions', component: Sessions },
    { path: '/reports', component: Reports },
  ],
})
