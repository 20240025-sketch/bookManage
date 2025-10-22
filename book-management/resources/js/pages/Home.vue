<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">ようこそ、蔵書管理システムへ</h1>

    <!-- クイックアクセスセクション -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- 貸出登録カード (メールアドレスが数字で始まる利用者または管理者のみ表示) -->
      <div v-if="shouldShowBorrowFeature" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">貸出登録</h2>
            <router-link
              to="/borrows/create"
              class="text-blue-600 hover:text-blue-800 text-sm font-medium"
            >
              貸出登録へ →
            </router-link>
          </div>
          <p class="text-gray-600 mb-4">
            本の貸出を登録できます。生徒と本を選択して、簡単に貸出手続きが完了します。
          </p>
          <div class="flex items-center justify-center py-4">
            <router-link
              to="/borrows/create"
              class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors"
            >
              新しい貸出を登録
            </router-link>
          </div>
        </div>
      </div>

      <!-- 本のリクエストカード -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">本のリクエスト</h2>
            <router-link
              to="/book-requests"
              class="text-blue-600 hover:text-blue-800 text-sm font-medium"
            >
              リクエスト一覧へ →
            </router-link>
          </div>
          <p class="text-gray-600 mb-4">
            読みたい本をリクエストできます。タイトル、著者、出版社などの情報を入力してください。
          </p>
          <div class="flex items-center justify-center py-4">
            <router-link
              to="/book-requests"
              class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors"
            >
              新しいリクエストを作成
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- その他のクイックアクセス -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
      <!-- 書籍一覧 -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">書籍一覧</h2>
        <p class="text-gray-600 mb-4">
          登録されている本の一覧を確認できます。
        </p>
        <router-link
          to="/books"
          class="text-blue-600 hover:text-blue-800 text-sm font-medium"
        >
          書籍一覧へ →
        </router-link>
      </div>

      <!-- 書籍登録 (メールアドレスが数字で始まる利用者または管理者のみ表示) -->
      <div v-if="shouldShowBookRegistration" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">書籍登録</h2>
        <p class="text-gray-600 mb-4">
          新しい本を登録できます。
        </p>
        <router-link
          to="/books/create"
          class="text-blue-600 hover:text-blue-800 text-sm font-medium"
        >
          書籍登録へ →
        </router-link>
      </div>

      <!-- 生徒一覧 (メールアドレスが数字で始まる利用者または管理者のみ表示) -->
      <div v-if="shouldShowStudentInfo" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ userPermissions.isAdmin ? '生徒一覧' : '自分の情報' }}</h2>
        <p class="text-gray-600 mb-4">
          {{ userPermissions.isAdmin ? '生徒の一覧と貸出状況を確認できます。' : '自分の生徒情報を確認できます。' }}
        </p>
        <router-link
          to="/students"
          class="text-blue-600 hover:text-blue-800 text-sm font-medium"
        >
          {{ userPermissions.isAdmin ? '生徒一覧へ →' : '自分の情報へ →' }}
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// 権限管理
const userPermissions = ref({
  isAdmin: false
})

// ローカルストレージから権限情報を読み込み
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

loadPermissions()

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
</script>
