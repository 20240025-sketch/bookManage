<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          パスワード変更
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          現在のパスワードと新しいパスワードを入力してください
        </p>
      </div>
      
      <!-- 成功メッセージ -->
      <div v-if="success" class="rounded-md bg-green-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-green-800">
              パスワードが正常に変更されました
            </h3>
          </div>
        </div>
      </div>

      <form v-if="!success" class="mt-8 space-y-6" @submit.prevent="handlePasswordChange">
        <div class="rounded-md shadow-sm -space-y-px">
          <div class="relative">
            <label for="current_password" class="sr-only">現在のパスワード</label>
            <input 
              id="current_password" 
              name="current_password" 
              :type="showCurrentPassword ? 'text' : 'password'" 
              required 
              class="appearance-none rounded-none relative block w-full px-3 py-2 pr-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
              placeholder="現在のパスワード"
              v-model="form.current_password"
            />
            <button 
              type="button" 
              @click="showCurrentPassword = !showCurrentPassword"
              class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              <svg v-if="showCurrentPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.05 6.05M9.878 9.878a3 3 0 105.303 5.303m0 0l3.828 3.829M20.95 20.95l-3.829-3.828m3.829 3.828L6.05 6.05" />
              </svg>
            </button>
          </div>
          <div class="relative">
            <label for="password" class="sr-only">新しいパスワード</label>
            <input 
              id="password" 
              name="password" 
              :type="showNewPassword ? 'text' : 'password'" 
              required 
              minlength="6"
              class="appearance-none rounded-none relative block w-full px-3 py-2 pr-10 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
              placeholder="新しいパスワード（6文字以上）"
              v-model="form.password"
            />
            <button 
              type="button" 
              @click="showNewPassword = !showNewPassword"
              class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              <svg v-if="showNewPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.05 6.05M9.878 9.878a3 3 0 105.303 5.303m0 0l3.828 3.829M20.95 20.95l-3.829-3.828m3.829 3.828L6.05 6.05" />
              </svg>
            </button>
          </div>
          <div class="relative">
            <label for="password_confirmation" class="sr-only">新しいパスワード確認</label>
            <input 
              id="password_confirmation" 
              name="password_confirmation" 
              :type="showPasswordConfirm ? 'text' : 'password'" 
              required 
              minlength="6"
              class="appearance-none rounded-none relative block w-full px-3 py-2 pr-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
              placeholder="新しいパスワード確認"
              v-model="form.password_confirmation"
            />
            <button 
              type="button" 
              @click="showPasswordConfirm = !showPasswordConfirm"
              class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              <svg v-if="showPasswordConfirm" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.05 6.05M9.878 9.878a3 3 0 105.303 5.303m0 0l3.828 3.829M20.95 20.95l-3.829-3.828m3.829 3.828L6.05 6.05" />
              </svg>
            </button>
          </div>
        </div>

        <!-- パスワード一致チェック -->
        <div v-if="!passwordsMatch && form.password_confirmation" class="text-sm text-red-600">
          新しいパスワードが一致しません
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

        <div>
          <button 
            type="submit" 
            :disabled="loading || !passwordsMatch || !form.current_password || !form.password || !form.password_confirmation"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
              </svg>
            </span>
            {{ loading ? '変更中...' : 'パスワードを変更' }}
          </button>
        </div>

        <div class="text-sm text-center">
          <button 
            type="button"
            @click="goToBooks"
            class="font-medium text-indigo-600 hover:text-indigo-500"
          >
            書籍一覧に戻る
          </button>
        </div>
      </form>

      <!-- 成功後のボタン -->
      <div v-if="success" class="mt-6">
        <button 
          @click="goToBooks"
          class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          書籍一覧に戻る
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'PasswordChange',
  setup() {
    const router = useRouter()
    
    const form = reactive({
      email: '',
      current_password: '',
      password: '',
      password_confirmation: ''
    })
    
    const loading = ref(false)
    const error = ref('')
    const success = ref(false)
    const showCurrentPassword = ref(false)
    const showNewPassword = ref(false)
    const showPasswordConfirm = ref(false)

    // ログインユーザーのメールアドレスを取得
    onMounted(() => {
      try {
        const student = JSON.parse(localStorage.getItem('student') || '{}')
        if (student.email) {
          form.email = student.email
        } else {
          error.value = 'ログイン情報が見つかりません。再度ログインしてください。'
        }
      } catch (e) {
        error.value = 'ログイン情報の取得に失敗しました。'
      }
    })

    // パスワード一致チェック
    const passwordsMatch = computed(() => {
      if (!form.password || !form.password_confirmation) {
        return true // まだ入力されていない場合は表示しない
      }
      return form.password === form.password_confirmation
    })

    const handlePasswordChange = async () => {
      if (!form.email) {
        error.value = 'ログイン情報が見つかりません。再度ログインしてください。'
        return
      }

      if (!passwordsMatch.value) {
        error.value = '新しいパスワードが一致しません'
        return
      }

      if (form.password.length < 6) {
        error.value = 'パスワードは6文字以上で入力してください'
        return
      }

      try {
        loading.value = true
        error.value = ''

        const response = await axios.post('/api/change-password', {
          email: form.email,
          current_password: form.current_password,
          password: form.password,
          password_confirmation: form.password_confirmation
        })
        
        if (response.data.success) {
          success.value = true
          // フォームをリセット
          form.current_password = ''
          form.password = ''
          form.password_confirmation = ''
          
          // 3秒後に自動的に書籍一覧ページに移動
          setTimeout(() => {
            goToBooks()
          }, 3000)
        }
      } catch (err) {
        error.value = err.response?.data?.message || 'パスワード変更に失敗しました'
      } finally {
        loading.value = false
      }
    }

    const goToBooks = () => {
      router.push('/books')
    }

    return {
      form,
      loading,
      error,
      success,
      showCurrentPassword,
      showNewPassword,
      showPasswordConfirm,
      passwordsMatch,
      handlePasswordChange,
      goToBooks
    }
  }
}
</script>

<style scoped>
/* カスタムスタイルがあれば追加 */
</style>