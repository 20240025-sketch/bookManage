<template>
  <div class="max-w-4xl mx-auto">
    <!-- ページヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">新規書籍登録</h1>
          <div class="mt-1 flex items-center gap-4">
            <p class="text-sm text-gray-600">
              新しい書籍を蔵書に追加します
            </p>
            <router-link
              to="/books/create-no-isbn"
              class="text-xs px-3 py-1 rounded-full border transition-colors bg-blue-100 text-blue-800 border-blue-300 hover:bg-blue-200"
            >
              ISBNコードのない本
            </router-link>
          </div>
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
        :isbn-searching="isbnSearching"
        :isbn-search-message="isbnSearchMessage"
        :isbn-search-success="isbnSearchSuccess"
        :no-isbn-mode="noIsbnMode"
        submit-label="書籍を登録"
        @submit="submitForm"
        @reset="resetForm"
        @isbn-blur="(isbn) => { fetchBookByIsbn(isbn); fetchBookByJanCode(isbn); }"
      />      <!-- エラー表示 -->
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
import { ref, reactive, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import BookForm from '@/components/books/BookForm.vue';

const router = useRouter();

const loading = ref(false);
const errors = ref({});
const generalError = ref('');

// ISBN検索状態
const isbnSearching = ref(false);
const isbnSearchMessage = ref('');
const isbnSearchSuccess = ref(false);

// ISBNなしモード
const noIsbnMode = ref(false);

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
  acceptance_type: '購入',
  acceptance_source: '文英堂',
  discard: '',
  storage_location: '図書室'
});

const fetchBookByIsbn = async (isbn) => {
  if (!isbn || isbn.length < 10) {
    isbnSearchMessage.value = '';
    isbnSearchSuccess.value = false;
    return;
  }

  isbnSearching.value = true;
  isbnSearchMessage.value = 'ISBN検索中...';
  isbnSearchSuccess.value = false;
  generalError.value = '';

  try {
    const response = await axios.get(`/api/books/search-by-isbn`, { params: { isbn } });
    const data = response.data.data || response.data;
    const source = response.data.source || 'unknown';
    
    // 取得できたフィールドのみformに反映
    let updatedFields = [];
    Object.keys(form).forEach(key => {
      if (data[key] !== undefined && data[key] !== null && data[key] !== '') {
        form[key] = data[key];
        updatedFields.push(key);
      }
    });

    if (updatedFields.length > 0) {
      isbnSearchSuccess.value = true;
      const sourceNames = {
        openBD: 'openBD',
        googleBooks: 'Google Books',
        ndl: '国立国会図書館'
      };
      isbnSearchMessage.value = `${sourceNames[source] || source}から書籍情報を取得しました (${updatedFields.length}項目更新)`;
    } else {
      isbnSearchSuccess.value = false;
      isbnSearchMessage.value = '書籍情報は見つかりましたが、利用可能なデータがありませんでした。';
    }
  } catch (err) {
    isbnSearchSuccess.value = false;
    if (err.response?.status === 404) {
      isbnSearchMessage.value = 'ISBN ' + isbn + ' の書籍情報が見つかりませんでした。ISBNをご確認ください。';
    } else {
      isbnSearchMessage.value = '書籍情報の取得に失敗しました。ネットワークまたはAPIの状態をご確認ください。';
    }
  } finally {
    isbnSearching.value = false;
  }
};

const fetchBookByJanCode = async (janCode) => {
  if (!janCode || janCode.length !== 13) {
    return;
  }

  // 独自JANコード（938525で始まる）の場合のみ検索
  if (!janCode.startsWith('938525')) {
    return;
  }

  isbnSearching.value = true;
  isbnSearchMessage.value = 'JANコード検索中...';
  isbnSearchSuccess.value = false;
  generalError.value = '';

  try {
    const response = await axios.get(`/api/books/search-by-jan`, { params: { jan_code: janCode } });
    const data = response.data.data || response.data;
    
    // 取得できたフィールドのみformに反映
    let updatedFields = [];
    Object.keys(form).forEach(key => {
      if (data[key] !== undefined && data[key] !== null && data[key] !== '') {
        form[key] = data[key];
        updatedFields.push(key);
      }
    });

    if (updatedFields.length > 0) {
      isbnSearchSuccess.value = true;
      isbnSearchMessage.value = `登録済みJANコードから書籍情報を取得しました (${updatedFields.length}項目更新)`;
    } else {
      isbnSearchSuccess.value = false;
      isbnSearchMessage.value = 'JANコードに対応する書籍が見つかりましたが、利用可能なデータがありませんでした。';
    }
  } catch (err) {
    isbnSearchSuccess.value = false;
    if (err.response?.status === 404) {
      isbnSearchMessage.value = 'JANコード ' + janCode + ' に対応する書籍情報が見つかりませんでした。';
    } else {
      isbnSearchMessage.value = 'JANコード検索中にエラーが発生しました。';
    }
  } finally {
    isbnSearching.value = false;
  }
};

const resetForm = () => {
  Object.keys(form).forEach(key => {
    if (key === 'pages' || key === 'price') {
      form[key] = null;
    } else if (key === 'quantity') {
      form[key] = 1; // 冊数のデフォルト値
    } else if (key === 'acceptance_type') {
      form[key] = '購入'; // 受け入れ種別のデフォルト値
    } else if (key === 'acceptance_source') {
      form[key] = '文英堂'; // 受け入れ元のデフォルト値
    } else if (key === 'storage_location') {
      form[key] = '図書室'; // 保管場所のデフォルト値
    } else {
      form[key] = '';
    }
  });
  errors.value = {};
  generalError.value = '';
  
  // ISBN検索状態もリセット
  isbnSearching.value = false;
  isbnSearchMessage.value = '';
  isbnSearchSuccess.value = false;
};

const toggleNoIsbnMode = () => {
  noIsbnMode.value = !noIsbnMode.value;
  
  if (noIsbnMode.value) {
    // ISBNなしモードの場合、ISBNフィールドをクリア
    form.isbn = '';
    isbnSearchMessage.value = '';
    isbnSearchSuccess.value = false;
  }
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

// ページマウント時にISBNフィールドにフォーカス
onMounted(() => {
  nextTick(() => {
    const isbnInput = document.getElementById('isbn');
    if (isbnInput) {
      isbnInput.focus();
    }
  });
});

console.log('BookCreate.vue loaded successfully');
</script>
