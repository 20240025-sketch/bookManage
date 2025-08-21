<template>
  <div class="max-w-4xl mx-auto">
    <!-- ページヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">新規書籍登録</h1>
          <p class="mt-1 text-sm text-gray-600">
            新しい書籍を蔵書に追加します
          </p>
        </div>
        <router-link
          to="/books"
          class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md"
        >
          ← 一覧に戻る
        </router-link>
      </div>
    </div>

    <!-- フォーム -->
    <div class="bg-white rounded-lg shadow p-6">
      <BookForm
        v-model:form="form"
        :errors="errors"
        :loading="loading"
        submit-label="書籍を登録"
        @submit="submitForm"
        @reset="resetForm"
        @isbn-blur="fetchBookByIsbn"
      />

      <!-- エラー表示 -->
      <div v-if="generalError" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
        <div class="flex">
          <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
          </svg>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">
              登録に失敗しました
            </h3>
            <div class="mt-2 text-sm text-red-700">
              {{ generalError }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import BookForm from '@/components/books/BookForm.vue';

const router = useRouter();

const loading = ref(false);
const errors = ref({});
const generalError = ref('');

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
  acceptance_date: '',
  acceptance_type: '',
  acceptance_source: '',
  discard: ''
});

const fetchBookByIsbn = async (isbn) => {
  if (!isbn || isbn.length < 10) return;
  loading.value = true;
  generalError.value = '';
  try {
    const response = await axios.get(`/api/books/search-by-isbn`, { params: { isbn } });
    const data = response.data.data || response.data;
    // 取得できたフィールドのみformに反映
    Object.keys(form).forEach(key => {
      if (data[key] !== undefined && data[key] !== null) {
        form[key] = data[key];
      }
    });
  } catch (err) {
    if (err.response?.status === 404) {
      generalError.value = '書誌情報が見つかりませんでした。ISBNをご確認ください。';
    } else {
      generalError.value = '書誌情報の取得に失敗しました。ネットワークまたはAPIの状態をご確認ください。';
    }
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  Object.keys(form).forEach(key => {
    if (key === 'pages' || key === 'price') {
      form[key] = null;
    } else {
      form[key] = '';
    }
  });
  errors.value = {};
  generalError.value = '';
};

const submitForm = async () => {
  loading.value = true;
  errors.value = {};
  generalError.value = '';

  try {
    const response = await axios.post('/api/books', form);
    
    // 成功時は書籍一覧ページに遷移
    router.push({
      name: 'BookIndex',
      query: { success: '書籍が正常に登録されました' }
    });
    
  } catch (error) {
    if (error.response && error.response.status === 422) {
      // バリデーションエラー
      errors.value = error.response.data.errors || {};
    } else {
      // その他のエラー
      generalError.value = error.response?.data?.message || 'エラーが発生しました。もう一度お試しください。';
    }
  } finally {
    loading.value = false;
  }
};

console.log('BookCreate.vue loaded successfully');
</script>
