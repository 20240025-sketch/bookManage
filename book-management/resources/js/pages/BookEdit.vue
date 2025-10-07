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
      <BookForm
        v-model:form="form"
        :errors="validationErrors"
        :loading="loading"
        submit-label="更新"
        @submit="updateBook"
        @reset="resetForm"
      />

      <!-- エラーメッセージ -->
      <div v-if="errors.length > 0" class="mt-4 bg-red-50 border-l-4 border-red-400 p-4">
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
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import BookForm from '@/components/books/BookForm.vue';

const route = useRoute();
const router = useRouter();

const initialLoading = ref(true);
const loading = ref(false);
const loadError = ref('');
const errors = ref([]);
const validationErrors = ref({});

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
  quantity: 1,
  acceptance_date: '',
  acceptance_type: '',
  acceptance_source: '',
  discard: '',
  storage_location: ''
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
      } else if (key === 'quantity') {
        // 冊数は数値として設定、nullや未定義の場合は1
        form[key] = book[key] !== null && book[key] !== undefined ? Number(book[key]) : 1;
      } else if ((key === 'published_date' || key === 'acceptance_date') && book[key]) {
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
  validationErrors.value = {};
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
      validationErrors.value = err.response.data.errors;
      errors.value = Object.values(err.response.data.errors).flat();
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
    validationErrors.value = {};
  }
};

onMounted(() => {
  loadBook();
});

console.log('BookEdit.vue loaded successfully');
</script>
