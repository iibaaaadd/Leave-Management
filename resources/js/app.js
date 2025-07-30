// resources/js/main.js
import { createApp } from "vue";
import App from "./app.vue";
import router from "./router";
import axios from "axios";
import "../css/app.css";

// Setup axios globally
window.axios = axios;
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// CSRF token
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
}

// Set auth token
const authToken = localStorage.getItem("auth_token");
if (authToken) {
    axios.defaults.headers.common["Authorization"] = `Bearer ${authToken}`;
}

// Create Vue app
const app = createApp(App);
app.use(router);
app.mount("#app");
