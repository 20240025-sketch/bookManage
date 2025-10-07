<template>
  <div id="app" class="min-h-screen">
    <!-- 認証が必要なページの場合はヘッダー付きレイアウトを表示 -->
    <template v-if="isAuthenticatedRoute">
      <AppHeader />
      
      <!-- メインナビゲーション -->
      <nav class="bg-white shadow-lg border-b border-gray-200 fixed top-16 left-0 right-0 z-40">
        <div class="max-w-7xl mx-auto px-4">
          <!-- ナビゲーション切り替えボタン -->
          <div class="flex items-center justify-between py-2">
            <button 
              @click="toggleNavigation"
              class="flex items-center space-x-2 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors duration-200"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="!isNavigationVisible" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
              <span class="text-sm font-medium">{{ isNavigationVisible ? 'メニューを閉じる' : 'メニューを開く' }}</span>
            </button>
          </div>
          
          <!-- ナビゲーションメニュー -->
          <div v-show="isNavigationVisible" class="flex space-x-2 overflow-x-auto py-3 border-t border-gray-100">
            <!-- 書籍一覧 -->
            <router-link 
              to="/books" 
              class="flex flex-col items-center px-4 py-3 min-w-0 flex-shrink-0 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200"
              :class="{ 'text-blue-600 bg-blue-50 border-b-2 border-blue-600': $route.path === '/books' }"
            >
              <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
              </svg>
              <span class="text-xs font-medium text-center">書籍一覧</span>
              <span class="text-xs text-gray-500 text-center">本を探す・確認</span>
            </router-link>
            
            <!-- 書籍登録 -->
            <router-link 
              to="/books/create" 
              class="flex flex-col items-center px-4 py-3 min-w-0 flex-shrink-0 text-gray-700 hover:text-green-600 hover:bg-green-50 rounded-lg transition-all duration-200"
              :class="{ 'text-green-600 bg-green-50 border-b-2 border-green-600': $route.path === '/books/create' }"
            >
              <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span class="text-xs font-medium text-center">書籍登録</span>
              <span class="text-xs text-gray-500 text-center">新しい本を追加</span>
            </router-link>
            
            <!-- 生徒一覧 -->
            <router-link 
              to="/students" 
              class="flex flex-col items-center px-4 py-3 min-w-0 flex-shrink-0 text-gray-700 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-all duration-200"
              :class="{ 'text-purple-600 bg-purple-50 border-b-2 border-purple-600': $route.path === '/students' }"
            >
              <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
              <span class="text-xs font-medium text-center">生徒一覧</span>
              <span class="text-xs text-gray-500 text-center">生徒情報管理</span>
            </router-link>
            
            <!-- 貸出登録 -->
            <router-link 
              to="/borrows/create" 
              class="flex flex-col items-center px-4 py-3 min-w-0 flex-shrink-0 text-gray-700 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-all duration-200"
              :class="{ 'text-orange-600 bg-orange-50 border-b-2 border-orange-600': $route.path === '/borrows/create' }"
            >
              <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
              </svg>
              <span class="text-xs font-medium text-center">貸出登録</span>
              <span class="text-xs text-gray-500 text-center">本を貸し出す</span>
            </router-link>
            
            <!-- 本のリクエスト -->
            <router-link 
              to="/book-requests" 
              class="flex flex-col items-center px-4 py-3 min-w-0 flex-shrink-0 text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200"
              :class="{ 'text-red-600 bg-red-50 border-b-2 border-red-600': $route.path === '/book-requests' }"
            >
              <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
              </svg>
              <span class="text-xs font-medium text-center">本のリクエスト</span>
              <span class="text-xs text-gray-500 text-center">購入希望・要望</span>
            </router-link>
          </div>
        </div>
      </nav>
      
      <main class="min-h-screen bg-gray-50" :class="isNavigationVisible ? 'pt-44' : 'pt-28'">
        <div class="max-w-7xl mx-auto px-4 py-6">
          <router-view />
        </div>
      </main>
    </template>
    
    <!-- ログイン・パスワード設定ページは全画面表示 -->
    <template v-else>
      <router-view />
    </template>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppHeader from './components/layouts/AppHeader.vue'

const route = useRoute()
const isNavigationVisible = ref(true)

// 認証が必要なルートかどうかを判定
const isAuthenticatedRoute = computed(() => {
  const authRoutes = ['/books', '/students', '/borrows', '/book-requests', '/password-change']
  return authRoutes.some(authRoute => route.path.startsWith(authRoute))
})

// ナビゲーションの表示/非表示を切り替え
const toggleNavigation = () => {
  isNavigationVisible.value = !isNavigationVisible.value
  // 状態をローカルストレージに保存
  localStorage.setItem('navigationVisible', isNavigationVisible.value.toString())
}

// ページ読み込み時にナビゲーションの表示状態を復元
onMounted(() => {
  const saved = localStorage.getItem('navigationVisible')
  if (saved !== null) {
    isNavigationVisible.value = saved === 'true'
  }
})

console.log('App.vue loaded successfully')
</script>