import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'bootstrap';
import axios from 'axios';
import { createApp } from 'vue';
import DashboardApp from './components/DashboardApp.vue';
import TenantApp from './components/TenantApp.vue';
import AdminApp from './components/AdminApp.vue';

axios.defaults.headers.common.Accept = 'application/json';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
if (csrf) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;

const page = document.body.dataset.page;
const components = { dashboard: DashboardApp, tenant: TenantApp, admin: AdminApp };
if (components[page]) createApp(components[page]).mount('#app');
