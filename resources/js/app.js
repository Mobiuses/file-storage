import './bootstrap';
import { createApp } from 'vue';
import FileManager from './components/FileManager.vue';

// Import Bootstrap CSS and JS
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Import Bootstrap Icons
import 'bootstrap-icons/font/bootstrap-icons.css';

const app = createApp(FileManager);
app.mount('#app');
