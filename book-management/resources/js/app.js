import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router/index.js';

// CSS読み込み
import '../css/app.css';

console.log('app.js loading...');

// Pinia (状態管理)
const pinia = createPinia();

// Vue アプリケーション作成
const app = createApp(App);

app.use(pinia);
app.use(router);

console.log('Vue app created, mounting...');

app.mount('#app');

console.log('Vue app mounted');
