<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-900 to-purple-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <div class="mx-auto h-16 w-16 bg-yellow-400 rounded-full flex items-center justify-center">
          <svg class="h-10 w-10 text-indigo-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
          管理者ログイン
        </h2>
        <p class="mt-2 text-center text-sm text-gray-300">
          管理者専用のログイン画面です
        </p>
      </div>
      
      <div class="bg-white rounded-lg shadow-xl p-8">
        <form class="space-y-6" @submit.prevent="handleAdminLogin">
          <!-- 管理者パスワード -->
          <div>
            <label for="admin-password" class="block text-sm font-medium text-gray-700 mb-2">
              管理者パスワード
            </label>
            <div class="relative">
              <input 
                id="admin-password" 
                name="admin-password" 
                :type="showPassword ? 'text' : 'password'" 
                required 
                class="appearance-none block w-full px-3 py-3 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                placeholder="管理者パスワードを入力"
                v-model="adminPassword"
                autocomplete="off"
              />
              <button 
                type="button" 
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <svg v-if="showPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.05 6.05M9.878 9.878a3 3 0 105.303 5.303m0 0l3.828 3.829M20.95 20.95l-3.829-3.828m3.829 3.828L6.05 6.05" />
                </svg>
              </button>
            </div>
          </div>

          <!-- エラーメッセージ -->
          <div v-if="error" class="rounded-md bg-red-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">
                  {{ error }}
                </h3>
              </div>
            </div>
          </div>

          <!-- メールアドレス -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              メールアドレス
            </label>
            <input 
              id="email" 
              name="email" 
              type="email" 
              required 
              class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
              placeholder="メールアドレス"
              v-model="form.email"
            />
          </div>

          <!-- ユーザーパスワード -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              パスワード
            </label>
            <input 
              id="password" 
              name="password" 
              type="password" 
              required 
              class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
              placeholder="パスワード"
              v-model="form.password"
            />
          </div>

          <!-- ログインボタン -->
          <div>
            <button 
              type="submit" 
              :disabled="loading"
              class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                </svg>
              </span>
              {{ loading ? '認証中...' : '管理者としてログイン' }}
            </button>
          </div>

          <!-- 通常ログインへ戻る -->
          <div class="text-sm text-center">
            <router-link 
              to="/login"
              class="font-medium text-indigo-600 hover:text-indigo-500"
            >
              ← 通常ログインに戻る
            </router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const adminPassword = ref('')
const form = reactive({
  email: '',
  password: ''
})

const loading = ref(false)
const error = ref('')
const showPassword = ref(false)

// 管理者パスワード（ハードコード）
const ADMIN_PASSWORD = '0835385252'

const handleAdminLogin = async () => {
  try {
    loading.value = true
    error.value = ''

    // まず管理者パスワードを確認
    if (adminPassword.value !== ADMIN_PASSWORD) {
      error.value = '管理者パスワードが正しくありません'
      loading.value = false
      return
    }

    console.log('管理者パスワード認証成功、ログイン試行開始:', form.email)
    
    // 通常のログイン処理を実行
    const response = await axios.post('/api/login', form)
    console.log('ログインAPIレスポンス:', response.data)
    
    if (response.data.success) {
      // ログイン成功
      console.log('ログイン成功:', response.data.student)
      localStorage.setItem('student', JSON.stringify(response.data.student))
      
      // 管理者フラグを設定
      localStorage.setItem('isAdmin', 'true')
      localStorage.setItem('userRole', 'admin')
      
      // 権限情報を保存（管理者ログインは強制的にisAdmin=trueを設定）
      const permissions = response.data.permissions || {}
      const adminPermissions = {
        ...permissions,
        isAdmin: true  // 管理者ログインでは必ずtrueに設定
      }
      
      console.log('権限情報を保存（管理者として設定）:', adminPermissions)
      localStorage.setItem('userPermissions', JSON.stringify(adminPermissions))
      
      // グローバル権限更新関数が利用可能な場合は呼び出し
      if (window.updateUserPermissions) {
        window.updateUserPermissions(adminPermissions)
      }
      
      console.log('管理者としてログイン成功、蔵書管理システムに遷移します')
      
      // 蔵書管理システム（書籍一覧）に遷移
      window.location.replace('/books')
    } else {
      console.log('ログイン失敗:', response.data)
      error.value = response.data.message || 'ログインに失敗しました'
    }
  } catch (err) {
    console.error('管理者ログインエラー:', err)
    error.value = err.response?.data?.message || 'ログインに失敗しました'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* カスタムスタイル */
</style>
