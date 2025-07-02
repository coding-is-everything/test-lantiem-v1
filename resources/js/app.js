import './bootstrap';
import { createApp } from 'vue';
import Dashboard from './components/Dashboard.vue';
import 'chart.js/auto';

// Create Vue app
const app = createApp({
    components: {
        Dashboard,
    },
    template: '<Dashboard />'
});

// Mount the app
app.mount('#app');