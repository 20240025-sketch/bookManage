<template>
  <div class="max-w-4xl mx-auto">
    <!-- ページヘッダー -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">書籍編集</h1>
      <p class="text-gray-600">書籍情報を編集してください</p>
    </div>

    <!-- ローディング -->
    <div v-if="initialLoading" class="text-center py-8">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">書籍情報を読み込んでいます...</p>
    </div>

    <!-- エラー（読み込み失敗） -->
    <div v-else-if="loadError" class="text-center py-8">
      <div class="text-red-600 mb-4">
        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">書籍が見つかりません</h3>
      <p class="text-gray-600 mb-4">{{ loadError }}</p>
      <router-link to="/books" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
        書籍一覧に戻る
      </router-link>
    </div>

    <!-- 編集フォーム -->
    <div v-else class="bg-white rounded-lg shadow p-6">
      <form @submit.prevent="updateBook" class="space-y-6">
        <!-- エラーメッセージ -->
        <div v-if="errors.length > 0" class="bg-red-50 border-l-4 border-red-400 p-4">
          <div class="flex">
            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
              <p class="text-sm text-red-700 font-medium">入力エラーがあります：</p>
              <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                <li v-for="error in errors" :key="error">{{ error }}</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- 基本情報 -->
        <div>
          <h2 class="text-lg font-semibold text-gray-900 mb-4">基本情報</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- タイトル -->
            <div class="md:col-span-2">
              <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                タイトル <span class="text-red-500">*</span>
              </label>
              <input
                type="text"
                id="title"
                v-model="form.title"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="書籍のタイトルを入力"
                required
              />
            </div>

            <!-- タイトルのヨミ -->
            <div class="md:col-span-2">
              <label for="title_transcription" class="block text-sm font-medium text-gray-700 mb-1">
                タイトルのヨミ
              </label>
              <input
                type="text"
                id="title_transcription"
                v-model="form.title_transcription"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="タイトルのヨミ（任意）"
              />
            </div>

            <!-- 著者 -->
            <div>
              <label for="author" class="block text-sm font-medium text-gray-700 mb-1">
                著者 <span class="text-red-500">*</span>
              </label>
              <input
                type="text"
                id="author"
                v-model="form.author"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="著者名を入力"
                required
              />
            </div>

            <!-- 出版社 -->
            <div>
              <label for="publisher" class="block text-sm font-medium text-gray-700 mb-1">
                出版社
              </label>
              <input
                type="text"
                id="publisher"
                v-model="form.publisher"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="出版社名を入力"
              />
            </div>

            <!-- 出版日 -->
            <div>
              <label for="published_date" class="block text-sm font-medium text-gray-700 mb-1">
                出版日
              </label>
              <input
                type="date"
                id="published_date"
                v-model="form.published_date"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <!-- ISBN -->
            <div>
              <label for="isbn" class="block text-sm font-medium text-gray-700 mb-1">
                ISBN
              </label>
              <input
                type="text"
                id="isbn"
                v-model="form.isbn"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="ISBN番号を入力"
              />
            </div>

            <!-- ページ数 -->
            <div>
              <label for="pages" class="block text-sm font-medium text-gray-700 mb-1">
                ページ数
              </label>
              <input
                type="number"
                id="pages"
                v-model.number="form.pages"
                min="1"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="ページ数を入力"
              />
            </div>

            <!-- 価格 -->
            <div>
              <label for="price" class="block text-sm font-medium text-gray-700 mb-1">
                価格
              </label>
              <input
                type="number"
                id="price"
                v-model.number="form.price"
                min="0"
                step="1"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="価格（円）"
              />
            </div>

            <!-- 日本十進分類法 -->
            <div>
              <label for="ndc" class="block text-sm font-medium text-gray-700 mb-1">
                NDC分類
              </label>
              <input
                type="text"
                id="ndc"
                v-model="form.ndc"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="例: 410"
              />
            </div>

            <!-- 読書状況 -->
            <div>
              <label for="reading_status" class="block text-sm font-medium text-gray-700 mb-1">
                読書状況
              </label>
              <select
                id="reading_status"
                v-model="form.reading_status"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">読書状況を選択</option>
                <option value="unread">未読</option>
                <option value="reading">読書中</option>
                <option value="read">読了</option>
              </select>
            </div>
          </div>
        </div>

        <!-- ボタン -->
        <div class="flex items-center justify-between pt-4 border-t">
          <router-link
            :to="`/books/${$route.params.id}`"
            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md"
          >
            キャンセル
          </router-link>
          
          <div class="flex items-center space-x-3">
            <button
              type="button"
              @click="resetForm"
              class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-md"
            >
              リセット
            </button>
            
            <button
              type="submit"
              :disabled="loading"
              class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                更新中...
              </span>
              <span v-else>更新</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const initialLoading = ref(true);
const loading = ref(false);
const loadError = ref('');
const errors = ref([]);

const form = reactive({
  title: '',
  title_transcription: '',
  author: '',
  publisher: '',
  published_date: '',
  isbn: '',
  pages: null,
  price: null,
  ndc: '',
  reading_status: ''
});

const originalForm = ref({});

const loadBook = async () => {
  try {
    const response = await axios.get(`/api/books/${route.params.id}`);
    const book = response.data.data || response.data;
    
    // フォームに既存データを設定
    Object.keys(form).forEach(key => {
      if (key === 'pages' || key === 'price') {
        // 数値フィールドは数値として設定、nullの場合はnullのまま
        form[key] = book[key] !== null && book[key] !== undefined ? Number(book[key]) : null;
      } else if (key === 'published_date' && book[key]) {
        // 日付フィールドはYYYY-MM-DD形式に変換
        const date = new Date(book[key]);
        form[key] = date.toISOString().split('T')[0];
      } else {
        // その他のフィールドは文字列として設定、nullの場合は空文字
        form[key] = book[key] || '';
      }
    });
    
    // 元データを保存（リセット用）
    originalForm.value = { ...form };
    
  } catch (err) {
    loadError.value = err.response?.data?.message || '書籍が見つかりませんでした';
  } finally {
    initialLoading.value = false;
  }
};

const updateBook = async () => {
  errors.value = [];
  loading.value = true;
  
  try {
    // バリデーション
    if (!form.title?.trim()) {
      errors.value.push('タイトルは必須です');
    }
    if (!form.author?.trim()) {
      errors.value.push('著者は必須です');
    }
    
    if (errors.value.length > 0) {
      loading.value = false;
      return;
    }
    
    // データ準備
    const updateData = { ...form };
    
    // 空文字列をnullに変換
    Object.keys(updateData).forEach(key => {
      if (updateData[key] === '') {
        updateData[key] = null;
      }
    });
    
    // API送信
    await axios.put(`/api/books/${route.params.id}`, updateData);
    
    // 成功時の処理
    router.push({
      name: 'BookShow',
      params: { id: route.params.id },
      query: { success: '書籍情報が更新されました' }
    });
    
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      // バリデーションエラー
      const validationErrors = err.response.data.errors;
      errors.value = Object.values(validationErrors).flat();
    } else {
      errors.value = [err.response?.data?.message || '更新に失敗しました'];
    }
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  if (confirm('フォームをリセットしてもよろしいですか？\n入力した変更内容が失われます。')) {
    Object.keys(form).forEach(key => {
      if (key === 'pages' || key === 'price') {
        // 数値フィールドは元の数値をそのまま設定
        form[key] = originalForm.value[key];
      } else {
        // その他のフィールドは文字列として設定
        form[key] = originalForm.value[key] || '';
      }
    });
    errors.value = [];
  }
};

onMounted(() => {
  loadBook();
});

console.log('BookEdit.vue loaded successfully');
</script>
