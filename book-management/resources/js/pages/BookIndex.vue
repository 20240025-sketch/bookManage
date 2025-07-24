<template>
  <div>
    <!-- ページヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">書籍管理</h1>
          <p class="text-gray-600">あなたの本棚を管理しましょう</p>
        </div>
        <router-link
          to="/books/create"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md"
        >
          新しい書籍を追加
        </router-link>
      </div>
    </div>

    <!-- 成功メッセージ -->
    <div v-if="$route.query.success" class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
      <div class="flex">
        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <div class="ml-3">
          <p class="text-sm text-green-700">{{ $route.query.success }}</p>
        </div>
      </div>
    </div>

    <!-- フィルター・検索エリア -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">検索・フィルター</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- タイトル検索 -->
        <div>
          <label for="searchTitle" class="block text-sm font-medium text-gray-700 mb-1">
            タイトル検索
          </label>
          <input
            type="text"
            id="searchTitle"
            v-model="filters.searchTitle"
            @input="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="タイトルで検索"
          />
        </div>

        <!-- 著者検索 -->
        <div>
          <label for="searchAuthor" class="block text-sm font-medium text-gray-700 mb-1">
            著者検索
          </label>
          <input
            type="text"
            id="searchAuthor"
            v-model="filters.searchAuthor"
            @input="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="著者で検索"
          />
        </div>

        <!-- 読書状況フィルター -->
        <div>
          <label for="statusFilter" class="block text-sm font-medium text-gray-700 mb-1">
            読書状況
          </label>
          <select
            id="statusFilter"
            v-model="filters.readingStatus"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">すべて</option>
            <option value="unread">未読</option>
            <option value="reading">読書中</option>
            <option value="read">読了</option>
          </select>
        </div>

        <!-- ソート -->
        <div>
          <label for="sortBy" class="block text-sm font-medium text-gray-700 mb-1">
            並び順
          </label>
          <select
            id="sortBy"
            v-model="filters.sortBy"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="created_at_desc">新しい順</option>
            <option value="created_at_asc">古い順</option>
            <option value="title_asc">タイトル順（A-Z）</option>
            <option value="title_desc">タイトル順（Z-A）</option>
            <option value="author_asc">著者順（A-Z）</option>
            <option value="author_desc">著者順（Z-A）</option>
          </select>
        </div>
      </div>

      <!-- クリアボタン -->
      <div class="mt-4 flex justify-between items-center">
        <button
          @click="clearFilters"
          class="text-sm text-gray-600 hover:text-gray-800"
        >
          フィルターをクリア
        </button>
        
        <div class="text-sm text-gray-600">
          {{ filteredBooks.length }}件中 {{ filteredBooks.length }}件を表示
        </div>
      </div>
    </div>

    <!-- ローディング -->
    <div v-if="loading" class="text-center py-8">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">書籍を読み込んでいます...</p>
    </div>

    <!-- エラー -->
    <div v-else-if="error" class="text-center py-8">
      <div class="text-red-600 mb-4">
        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">読み込みエラー</h3>
      <p class="text-gray-600 mb-4">{{ error }}</p>
      <button
        @click="loadBooks"
        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md"
      >
        再試行
      </button>
    </div>

    <!-- 書籍がない場合 -->
    <div v-else-if="filteredBooks.length === 0" class="text-center py-8">
      <div class="text-gray-400 mb-4">
        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">
        {{ hasAnyFilter ? '該当する書籍が見つかりません' : '書籍がありません' }}
      </h3>
      <p class="text-gray-600 mb-4">
        {{ hasAnyFilter ? 'フィルター条件を変更してください' : '最初の書籍を追加しましょう' }}
      </p>
      <div class="space-x-3">
        <button
          v-if="hasAnyFilter"
          @click="clearFilters"
          class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md"
        >
          フィルターをクリア
        </button>
        <router-link
          to="/books/create"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md"
        >
          書籍を追加
        </router-link>
      </div>
    </div>

    <!-- 書籍リスト -->
    <div v-else class="grid gap-6">
      <div
        v-for="book in filteredBooks"
        :key="book.id"
        class="bg-white rounded-lg shadow hover:shadow-md transition-shadow p-6"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-start justify-between mb-2">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                  <router-link
                    :to="`/books/${book.id}`"
                    class="hover:text-blue-600 transition-colors"
                  >
                    {{ book.title }}
                  </router-link>
                </h3>
                <p v-if="book.title_transcription" class="text-sm text-gray-600 mb-2">{{ book.title_transcription }}</p>
              </div>
              <span
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ml-4"
                :class="getStatusClass(book.reading_status)"
              >
                {{ getStatusLabel(book.reading_status) }}
              </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
              <div>
                <span class="font-medium">著者:</span> {{ book.author }}
              </div>
              <div v-if="book.publisher">
                <span class="font-medium">出版社:</span> {{ book.publisher }}
              </div>
              <div v-if="book.published_date">
                <span class="font-medium">出版日:</span> {{ formatDate(book.published_date) }}
              </div>
            </div>
          </div>

          <div class="flex items-center space-x-2 ml-4">
            <router-link
              :to="`/books/${book.id}`"
              class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm"
            >
              詳細
            </router-link>
            <router-link
              :to="`/books/${book.id}/edit`"
              class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1 rounded text-sm"
            >
              編集
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

const books = ref([]);
const loading = ref(true);
const error = ref('');

const filters = reactive({
  searchTitle: '',
  searchAuthor: '',
  readingStatus: '',
  sortBy: 'created_at_desc'
});

const loadBooks = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    const response = await axios.get('/api/books');
    books.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'データの読み込みに失敗しました';
  } finally {
    loading.value = false;
  }
};

const filteredBooks = computed(() => {
  let result = [...books.value];

  // タイトル検索
  if (filters.searchTitle) {
    const searchTerm = filters.searchTitle.toLowerCase();
    result = result.filter(book => 
      book.title.toLowerCase().includes(searchTerm) ||
      (book.title_transcription && book.title_transcription.toLowerCase().includes(searchTerm))
    );
  }

  // 著者検索
  if (filters.searchAuthor) {
    const searchTerm = filters.searchAuthor.toLowerCase();
    result = result.filter(book => 
      book.author.toLowerCase().includes(searchTerm)
    );
  }

  // 読書状況フィルター
  if (filters.readingStatus) {
    result = result.filter(book => book.reading_status === filters.readingStatus);
  }

  // ソート
  result.sort((a, b) => {
    switch (filters.sortBy) {
      case 'created_at_asc':
        return new Date(a.created_at) - new Date(b.created_at);
      case 'created_at_desc':
        return new Date(b.created_at) - new Date(a.created_at);
      case 'title_asc':
        return a.title.localeCompare(b.title);
      case 'title_desc':
        return b.title.localeCompare(a.title);
      case 'author_asc':
        return a.author.localeCompare(b.author);
      case 'author_desc':
        return b.author.localeCompare(a.author);
      default:
        return new Date(b.created_at) - new Date(a.created_at);
    }
  });

  return result;
});

const hasAnyFilter = computed(() => {
  return filters.searchTitle || filters.searchAuthor || filters.readingStatus;
});

const applyFilters = () => {
  // フィルターが変更された時の処理（リアクティブなのでcomputedが自動更新される）
};

const clearFilters = () => {
  filters.searchTitle = '';
  filters.searchAuthor = '';
  filters.readingStatus = '';
  filters.sortBy = 'created_at_desc';
};

const getStatusClass = (status) => {
  switch (status) {
    case 'unread':
      return 'bg-gray-100 text-gray-800';
    case 'reading':
      return 'bg-blue-100 text-blue-800';
    case 'read':
      return 'bg-green-100 text-green-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const getStatusLabel = (status) => {
  switch (status) {
    case 'unread':
      return '未読';
    case 'reading':
      return '読書中';
    case 'read':
      return '読了';
    default:
      return status;
  }
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ja-JP');
};

onMounted(() => {
  loadBooks();
});

console.log('BookIndex.vue loaded successfully');
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
