<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          図書館システム ログイン
        </h2>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email" class="sr-only">メールアドレス</label>
            <input 
              id="email" 
              name="email" 
              type="email" 
              required 
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
              placeholder="メールアドレス"
              v-model="form.email"
            />
          </div>
          <div class="relative">
            <label for="password" class="sr-only">パスワード</label>
            <input 
              id="password" 
              name="password" 
              :type="showPassword ? 'text' : 'password'" 
              required 
              class="appearance-none rounded-none relative block w-full px-3 py-2 pr-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
              placeholder="パスワード"
              v-model="form.password"
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

        <!-- パスワード設定が必要な場合 -->
        <div v-if="requiresPasswordSetup" class="rounded-md bg-yellow-50 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-yellow-800">
                初回パスワード設定が必要です
              </h3>
              <div class="mt-2 text-sm text-yellow-700">
                <button 
                  type="button"
                  @click="goToPasswordSetup"
                  class="underline hover:text-yellow-900"
                >
                  パスワード設定画面へ
                </button>
              </div>
            </div>
          </div>
        </div>

        <div>
          <button 
            type="submit" 
            :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
              </svg>
            </span>
            {{ loading ? 'ログイン中...' : 'ログイン' }}
          </button>
        </div>

        <div class="text-sm text-center">
          <button 
            type="button"
            @click="goToPasswordSetup"
            class="font-medium text-indigo-600 hover:text-indigo-500"
          >
            初回パスワード設定
          </button>
        </div>

        <!-- 管理者ログインへのリンク -->
        <div class="text-sm text-center pt-4 border-t border-gray-200">
          <router-link 
            to="/admin-login"
            class="font-medium text-purple-600 hover:text-purple-500 flex items-center justify-center gap-2"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            管理者ログイン
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'Login',
  setup() {
    const router = useRouter()
    
    const form = reactive({
      email: '',
      password: ''
    })
    
    const loading = ref(false)
    const error = ref('')
    const requiresPasswordSetup = ref(false)
    const showPassword = ref(false)

    const handleLogin = async () => {
      try {
        loading.value = true
        error.value = ''
        requiresPasswordSetup.value = false

        console.log('ログイン試行開始:', form.email)
        const response = await axios.post('/api/login', form)
        console.log('ログインAPIレスポンス:', response.data)
        
        if (response.data.success) {
          // ログイン成功
          console.log('ログイン成功:', response.data.student)
          
          // バックエンドから返された権限情報を使用
          const permissions = response.data.permissions || {}
          const isAdmin = permissions.isAdmin === true
          
          console.log('バックエンドの権限判定:', permissions)
          
          localStorage.setItem('student', JSON.stringify(response.data.student))
          
          // バックエンドから返された権限情報をそのまま使用
          localStorage.setItem('isAdmin', isAdmin ? 'true' : 'false')
          localStorage.setItem('userRole', isAdmin ? 'admin' : 'user')
          localStorage.setItem('userPermissions', JSON.stringify(permissions))
          
          console.log('権限情報を保存:', permissions)
          
          // グローバル権限更新関数が利用可能な場合は呼び出し
          if (window.updateUserPermissions) {
            window.updateUserPermissions(permissions)
          }
          
          console.log('localStorage設定完了、ログイン成功')
          
          // 強制的にページをリロードして書籍一覧に遷移
          console.log('書籍一覧ページに遷移します（window.location）')
          window.location.replace('/books')
        } else {
          console.log('ログイン失敗:', response.data)
          error.value = response.data.message || 'ログインに失敗しました'
        }
      } catch (err) {
        console.error('ログインエラー:', err)
        if (err.response?.data?.requires_password_setup) {
          requiresPasswordSetup.value = true
          error.value = err.response.data.message
        } else {
          error.value = err.response?.data?.message || 'ログインに失敗しました'
        }
      } finally {
        loading.value = false
      }
    }

    const goToPasswordSetup = () => {
      router.push('/password-setup')
    }

    return {
      form,
      loading,
      error,
      requiresPasswordSetup,
      showPassword,
      handleLogin,
      goToPasswordSetup
    }
  }
}
</script>

<style scoped>
/* カスタムスタイルがあれば追加 */
</style>