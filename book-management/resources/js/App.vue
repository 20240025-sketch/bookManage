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
            
            <!-- 書籍登録 (メールアドレスが数字で始まる利用者または管理者のみ表示) -->
            <router-link 
              v-if="userPermissions.canCreateBooks && shouldShowBookRegistration"
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
            
            <!-- 生徒一覧 (メールアドレスが数字で始まる利用者または管理者のみ表示) -->
            <router-link 
              v-if="shouldShowStudentInfo"
              to="/students" 
              class="flex flex-col items-center px-4 py-3 min-w-0 flex-shrink-0 text-gray-700 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-all duration-200"
              :class="{ 'text-purple-600 bg-purple-50 border-b-2 border-purple-600': $route.path === '/students' }"
            >
              <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
              <span class="text-xs font-medium text-center">{{ userPermissions.isAdmin ? '生徒一覧' : '自分の情報' }}</span>
              <span class="text-xs text-gray-500 text-center">{{ userPermissions.isAdmin ? '生徒情報管理' : '個人情報確認' }}</span>
            </router-link>
            
            <!-- 貸出登録 (メールアドレスが数字で始まる利用者または管理者のみ表示) -->
            <router-link 
              v-if="userPermissions.canCreateBorrows && shouldShowBorrowFeature"
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
            
            <!-- 貸出状況 (管理者のみ) -->
            <router-link 
              v-if="userPermissions.isAdmin"
              to="/borrow-status" 
              class="flex flex-col items-center px-4 py-3 min-w-0 flex-shrink-0 text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all duration-200"
              :class="{ 'text-indigo-600 bg-indigo-50 border-b-2 border-indigo-600': $route.path === '/borrow-status' }"
            >
              <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
              </svg>
              <span class="text-xs font-medium text-center">貸出状況</span>
              <span class="text-xs text-gray-500 text-center">滞納管理</span>
            </router-link>
            
            <!-- 利用状況 (管理者のみ) -->
            <router-link 
              v-if="userPermissions.isAdmin"
              to="/usage-statistics" 
              class="flex flex-col items-center px-4 py-3 min-w-0 flex-shrink-0 text-gray-700 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-all duration-200"
              :class="{ 'text-purple-600 bg-purple-50 border-b-2 border-purple-600': $route.path === '/usage-statistics' }"
            >
              <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
              <span class="text-xs font-medium text-center">利用状況</span>
              <span class="text-xs text-gray-500 text-center">統計・グラフ</span>
            </router-link>
            
            <!-- 図書当番 (管理者のみ) -->
            <router-link 
              v-if="userPermissions.isAdmin"
              to="/library-duty" 
              class="flex flex-col items-center px-4 py-3 min-w-0 flex-shrink-0 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-all duration-200"
              :class="{ 'text-teal-600 bg-teal-50 border-b-2 border-teal-600': $route.path === '/library-duty' }"
            >
              <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <span class="text-xs font-medium text-center">図書当番</span>
              <span class="text-xs text-gray-500 text-center">記録管理</span>
            </router-link>
            
            <!-- 本のリクエスト (全ユーザー利用可能) -->
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

            <!-- 通知 (全ユーザー利用可能) -->
            <router-link 
              to="/notifications" 
              class="flex flex-col items-center px-4 py-3 min-w-0 flex-shrink-0 text-gray-700 hover:text-yellow-600 hover:bg-yellow-50 rounded-lg transition-all duration-200 relative"
              :class="{ 'text-yellow-600 bg-yellow-50 border-b-2 border-yellow-600': $route.path === '/notifications' }"
            >
              <div class="relative">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <span 
                  v-if="unreadCount > 0"
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
                >
                  {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
              </div>
              <span class="text-xs font-medium text-center">通知</span>
              <span class="text-xs text-gray-500 text-center">お知らせ</span>
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
import axios from 'axios'

const route = useRoute()
const isNavigationVisible = ref(true)
const unreadCount = ref(0)

// 権限管理
const userPermissions = ref({
  isAdmin: false,
  canCreateBooks: false,
  canViewStudents: false,
  canCreateBorrows: false,
  canViewBookRequestHistory: false,
  canEditBooks: false,
  canUseBorrowFeatures: false
})

// 認証が必要なルートかどうかを判定
const isAuthenticatedRoute = computed(() => {
  const authRoutes = ['/books', '/students', '/borrows', '/borrow-status', '/usage-statistics', '/library-duty', '/book-requests', '/notifications', '/password-change']
  return authRoutes.some(authRoute => route.path.startsWith(authRoute))
})

// ナビゲーションの表示/非表示を切り替え
const toggleNavigation = () => {
  isNavigationVisible.value = !isNavigationVisible.value
  // 状態をローカルストレージに保存
  localStorage.setItem('navigationVisible', isNavigationVisible.value.toString())
}

// 権限情報をローカルストレージから読み込み
const loadPermissions = () => {
  try {
    const stored = localStorage.getItem('userPermissions')
    if (stored) {
      userPermissions.value = { ...userPermissions.value, ...JSON.parse(stored) }
    }
  } catch (error) {
    console.error('権限情報の読み込みに失敗:', error)
  }
}

// 権限情報を保存
const savePermissions = (permissions) => {
  userPermissions.value = { ...userPermissions.value, ...permissions }
  localStorage.setItem('userPermissions', JSON.stringify(userPermissions.value))
}

// グローバルに権限情報を提供
window.updateUserPermissions = savePermissions

// メールアドレスが数字で始まるかチェック（管理者は常にtrue）
const shouldShowStudentInfo = computed(() => {
  if (userPermissions.value.isAdmin) return true
  
  const student = JSON.parse(localStorage.getItem('student') || '{}')
  const email = student.email || ''
  
  // メールアドレスが数字で始まる場合のみ表示
  return /^[0-9]/.test(email)
})

// 貸出登録機能の表示判定（管理者は常にtrue、利用者はメールアドレスが数字で始まる場合のみ）
const shouldShowBorrowFeature = computed(() => {
  if (userPermissions.value.isAdmin) return true
  
  const student = JSON.parse(localStorage.getItem('student') || '{}')
  const email = student.email || ''
  
  // メールアドレスが数字で始まる場合のみ表示
  return /^[0-9]/.test(email)
})

// 書籍登録機能の表示判定（管理者は常にtrue、利用者はメールアドレスが数字で始まる場合のみ）
const shouldShowBookRegistration = computed(() => {
  if (userPermissions.value.isAdmin) return true
  
  const student = JSON.parse(localStorage.getItem('student') || '{}')
  const email = student.email || ''
  
  // メールアドレスが数字で始まる場合のみ表示
  return /^[0-9]/.test(email)
})

// 未読通知数を取得
const fetchUnreadCount = async () => {
  try {
    const response = await axios.get('/api/notifications/unread-count')
    unreadCount.value = response.data.count
  } catch (error) {
    // 401エラー（未認証）の場合は静かに0にする
    if (error.response && error.response.status === 401) {
      unreadCount.value = 0
      console.log('未読数取得: 未認証のため0に設定')
    } else {
      console.error('未読数取得エラー:', error)
      unreadCount.value = 0
    }
  }
}

// ページ読み込み時にナビゲーションの表示状態を復元
onMounted(() => {
  const saved = localStorage.getItem('navigationVisible')
  if (saved !== null) {
    isNavigationVisible.value = saved === 'true'
  }
  
  // 権限情報を読み込み
  loadPermissions()
  
  // 未読通知数を取得
  fetchUnreadCount()
  
  // 1分ごとに未読数を更新
  setInterval(fetchUnreadCount, 60000)
})

console.log('App.vue loaded successfully')
</script>