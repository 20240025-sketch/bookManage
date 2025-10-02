import axios from 'axios';
window.axios = axios;

// ベースURLを設定
window.axios.defaults.baseURL = 'http://localhost:8000';
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
