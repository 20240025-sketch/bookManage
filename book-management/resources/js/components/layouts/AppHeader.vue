<template>
  <header class="bg-white shadow-sm border-b border-gray-200 fixed top-0 left-0 right-0 z-50">
    <div class="px-6 py-4">
      <div class="flex items-center justify-between">
        <!-- ロゴとタイトル -->
        <div class="flex items-center space-x-4">
          <div class="flex items-center space-x-2">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            <h1 class="text-xl font-bold text-gray-900">蔵書管理システム</h1>
          </div>
        </div>

        <!-- ユーザーメニュー -->
        <div class="flex items-center space-x-4">
          <!-- 通知ベル -->
          <button class="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
          </button>

          <!-- ユーザーアバター -->
          <div class="relative" v-if="currentStudent">
            <button 
              @click="toggleUserMenu"
              class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                {{ currentStudent.name.charAt(0) }}
              </div>
              <span class="text-sm font-medium text-gray-700">{{ currentStudent.name }}</span>
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>

            <!-- ドロップダウンメニュー -->
            <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
              <div class="px-4 py-2 text-xs text-gray-500 border-b">
                {{ currentStudent.email }}
              </div>
              <button @click="changePassword" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">パスワード変更</button>
              <hr class="my-1">
              <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">ログアウト</button>
            </div>
          </div>
          
          <!-- ログインボタン（未認証時） -->
          <div v-else>
            <a href="/login" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
              ログイン
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const showUserMenu = ref(false);
const currentStudent = ref(null);

// 認証されたユーザー情報を取得
const loadCurrentStudent = () => {
  const student = localStorage.getItem('student');
  if (student) {
    currentStudent.value = JSON.parse(student);
  }
};

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value;
};

const logout = async () => {
  try {
    await axios.post('/api/logout');
  } catch (error) {
    console.error('Logout error:', error);
  } finally {
    // ローカルストレージをクリア
    localStorage.removeItem('student');
    currentStudent.value = null;
    // ログインページにリダイレクト
    router.push('/login');
  }
};

const changePassword = () => {
  router.push('/password-change');
};

// クリック外でメニューを閉じる
document.addEventListener('click', (e) => {
  if (!e.target.closest('.relative')) {
    showUserMenu.value = false;
  }
});

onMounted(() => {
  loadCurrentStudent();
});
</script>
