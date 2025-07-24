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
      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- 基本情報 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- タイトル -->
          <div class="md:col-span-2">
            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
              タイトル <span class="text-red-500">*</span>
            </label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              required
              class="form-input"
              :class="{ 'border-red-500': errors.title }"
              placeholder="書籍のタイトルを入力してください"
            >
            <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title[0] }}</p>
          </div>

          <!-- タイトルのヨミ -->
          <div class="md:col-span-2">
            <label for="title_transcription" class="block text-sm font-medium text-gray-700 mb-2">
              タイトルのヨミ
            </label>
            <input
              id="title_transcription"
              v-model="form.title_transcription"
              type="text"
              class="form-input"
              placeholder="タイトルのヨミ（任意）"
            >
          </div>

          <!-- 著者 -->
          <div>
            <label for="author" class="block text-sm font-medium text-gray-700 mb-2">
              著者 <span class="text-red-500">*</span>
            </label>
            <input
              id="author"
              v-model="form.author"
              type="text"
              required
              class="form-input"
              :class="{ 'border-red-500': errors.author }"
              placeholder="著者名を入力してください"
            >
            <p v-if="errors.author" class="mt-1 text-sm text-red-600">{{ errors.author[0] }}</p>
          </div>

          <!-- 出版社 -->
          <div>
            <label for="publisher" class="block text-sm font-medium text-gray-700 mb-2">
              出版社
            </label>
            <input
              id="publisher"
              v-model="form.publisher"
              type="text"
              class="form-input"
              placeholder="出版社名"
            >
          </div>

          <!-- 出版日 -->
          <div>
            <label for="published_date" class="block text-sm font-medium text-gray-700 mb-2">
              出版日
            </label>
            <input
              id="published_date"
              v-model="form.published_date"
              type="date"
              class="form-input"
            >
          </div>

          <!-- ISBN -->
          <div>
            <label for="isbn" class="block text-sm font-medium text-gray-700 mb-2">
              ISBN
            </label>
            <div class="relative">
              <input
                id="isbn"
                ref="isbnInputRef"
                v-model="form.isbn"
                @blur="searchByISBN"
                @keydown.enter.prevent="searchByISBN"
                type="text"
                class="form-input pr-20"
                placeholder="ISBN-13形式（978-）"
              >
              <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <div v-if="isbnSearching" class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                <button
                  v-else
                  @click="searchByISBN"
                  type="button"
                  class="text-blue-600 hover:text-blue-800 text-sm"
                >
                  検索
                </button>
              </div>
            </div>
            <p class="mt-1 text-xs text-gray-500">
              ISBNを入力後、フィールドから出るか検索ボタンで書籍情報を自動取得
            </p>
          </div>

          <!-- ページ数 -->
          <div>
            <label for="pages" class="block text-sm font-medium text-gray-700 mb-2">
              ページ数
            </label>
            <input
              id="pages"
              v-model.number="form.pages"
              type="number"
              min="1"
              class="form-input"
              placeholder="ページ数"
            >
          </div>

          <!-- 価格 -->
          <div>
            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
              価格
            </label>
            <input
              id="price"
              v-model.number="form.price"
              type="number"
              min="0"
              step="1"
              class="form-input"
              placeholder="価格（円）"
            >
          </div>

          <!-- 日本十進分類法 -->
          <div>
            <label for="ndc" class="block text-sm font-medium text-gray-700 mb-2">
              NDC分類
            </label>
            <input
              id="ndc"
              v-model="form.ndc"
              type="text"
              class="form-input"
              placeholder="例: 410"
            >
          </div>

          <!-- 読書状況 -->
          <div>
            <label for="reading_status" class="block text-sm font-medium text-gray-700 mb-2">
              読書状況
            </label>
            <select
              id="reading_status"
              v-model="form.reading_status"
              class="form-select"
              :class="{ 'border-red-500': errors.reading_status }"
            >
              <option value="">選択してください</option>
              <option value="unread">未読</option>
              <option value="reading">読書中</option>
              <option value="read">読了</option>
            </select>
            <p v-if="errors.reading_status" class="mt-1 text-sm text-red-600">{{ errors.reading_status[0] }}</p>
          </div>
        </div>

        <!-- フォームアクション -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
          <div class="flex items-center space-x-4">
            <button
              type="button"
              @click="resetForm"
              class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md"
            >
              リセット
            </button>
          </div>
          
          <div class="flex items-center space-x-4">
            <router-link
              to="/books"
              class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md"
            >
              キャンセル
            </router-link>
            <button
              type="submit"
              :disabled="loading"
              class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md disabled:opacity-50"
            >
              {{ loading ? '保存中...' : '書籍を登録' }}
            </button>
          </div>
        </div>
      </form>

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
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

const loading = ref(false);
const isbnSearching = ref(false);
const lastSearchedISBN = ref(''); // 最後に検索したISBNを記録
const errors = ref({});
const generalError = ref('');
const isbnInputRef = ref(null); // ISBNフィールドへの参照

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
  lastSearchedISBN.value = ''; // 検索履歴もクリア
};

// コンポーネントマウント時にISBNフィールドにフォーカス
onMounted(() => {
  if (isbnInputRef.value) {
    isbnInputRef.value.focus();
  }
});

// ISBN検索機能
const searchByISBN = async () => {
  const isbn = form.isbn?.trim();
  
  // ISBNが空または短すぎる場合はスキップ
  if (!isbn || isbn.length < 10) {
    return;
  }
  
  // ISBN形式の簡易チェック（10桁または13桁）
  const cleanISBN = isbn.replace(/[-\s]/g, '');
  if (!/^\d{10}(\d{3})?$/.test(cleanISBN)) {
    console.warn('無効なISBN形式:', cleanISBN);
    return;
  }
  
  // 既に検索済みのISBNの場合はスキップ
  if (lastSearchedISBN.value === cleanISBN) {
    return;
  }
  
  // 既に検索中の場合はスキップ
  if (isbnSearching.value) {
    return;
  }
  
  lastSearchedISBN.value = cleanISBN;
  isbnSearching.value = true;
  
  try {
    console.log('Searching for ISBN:', cleanISBN);
    
    // Laravel経由で国立国会図書館APIを呼び出し
    const response = await axios.post('/api/books/search-isbn', {
      isbn: cleanISBN
    });
    
    console.log('Search response:', response.data);
    
    if (response.data.success && response.data.data) {
      const bookData = response.data.data;
      
      // フォームに自動入力（既存の値がある場合は確認）
      if (bookData.title && (!form.title || confirm('タイトルを上書きしますか？'))) {
        form.title = bookData.title;
      }
      
      if (bookData.title_transcription && (!form.title_transcription || confirm('タイトルのヨミを上書きしますか？'))) {
        form.title_transcription = bookData.title_transcription;
      }
      
      if (bookData.author && (!form.author || confirm('著者を上書きしますか？'))) {
        form.author = bookData.author;
      }
      
      if (bookData.publisher && (!form.publisher || confirm('出版社を上書きしますか？'))) {
        form.publisher = bookData.publisher;
      }
      
      if (bookData.published_date && (!form.published_date || confirm('出版日を上書きしますか？'))) {
        form.published_date = bookData.published_date;
      }
      
      if (bookData.price && (!form.price || confirm('価格を上書きしますか？'))) {
        form.price = bookData.price;
      }
      
      if (bookData.pages && (!form.pages || confirm('ページ数を上書きしますか？'))) {
        form.pages = bookData.pages;
      }
      
      if (bookData.ndc && (!form.ndc || confirm('NDC分類を上書きしますか？'))) {
        form.ndc = bookData.ndc;
      }
      
      // 成功メッセージ
      let filledFields = [];
      if (bookData.title) filledFields.push('タイトル');
      if (bookData.title_transcription) filledFields.push('タイトルのヨミ');
      if (bookData.author) filledFields.push('著者');
      if (bookData.publisher) filledFields.push('出版社');
      if (bookData.published_date) filledFields.push('出版日');
      if (bookData.pages) filledFields.push('ページ数');
      if (bookData.price) filledFields.push('価格');
      if (bookData.ndc) filledFields.push('NDC分類');
      
      console.log(`書籍情報を自動取得しました！取得項目: ${filledFields.join('、')}`);
      
    } else {
      alert('該当する書籍が見つかりませんでした。手動で入力してください。');
    }
    
  } catch (error) {
    console.error('ISBN検索エラー:', error);
    
    if (error.response?.status === 404) {
      alert('該当する書籍が見つかりませんでした。');
    } else if (error.response?.status === 422) {
      console.warn('ISBNの形式が正しくありません:', cleanISBN);
    } else {
      console.error('書籍情報の取得に失敗しました:', error.message || error);
    }
  } finally {
    isbnSearching.value = false;
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

console.log('BookCreate.vue loaded successfully');
</script>
