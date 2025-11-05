<template>
  <div class="container mx-auto px-4 py-8">
    <!-- フィルターセクション -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <!-- 既存の検索フィールド -->
        <div>
          <label for="searchTitle" class="block text-sm font-medium text-gray-700 mb-1">
            タイトル検索
          </label>
          <input
            id="searchTitle"
            v-model="filters.searchTitle"
            type="text"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="タイトルで検索..."
          />
        </div>
        
        <div>
          <label for="searchAuthor" class="block text-sm font-medium text-gray-700 mb-1">
            著者検索
          </label>
          <input
            id="searchAuthor"
            v-model="filters.searchAuthor"
            type="text"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="著者名で検索..."
          />
        </div>

        <!-- NDC分類フィルター -->
        <div>
          <label for="ndcFilter" class="block text-sm font-medium text-gray-700 mb-1">
            NDC分類
          </label>
          <select
            id="ndcFilter"
            v-model="filters.ndc"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">すべて</option>
            <option value="000">000-099: 総記・情報学</option>
            <option value="100">100-199: 哲学・心理学</option>
            <option value="200">200-299: 歴史・地理</option>
            <option value="300">300-399: 社会科学</option>
            <option value="400">400-499: 自然科学・数学</option>
            <option value="500">500-599: 技術・工学</option>
            <option value="600">600-699: 産業・家政学</option>
            <option value="700">700-799: 芸術・美術・音楽</option>
            <option value="800">800-899: 語学</option>
            <option value="900">900-999: 文学</option>
          </select>
        </div>

        <!-- ISBNコード有無フィルター -->
        <div>
          <label for="isbnTypeFilter" class="block text-sm font-medium text-gray-700 mb-1">
            ISBNコード
          </label>
          <select
            id="isbnTypeFilter"
            v-model="filters.isbnType"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">すべて</option>
            <option value="with_isbn">あり</option>
            <option value="without_isbn">なし</option>
          </select>
        </div>

        <!-- 保管場所フィルター -->
        <div>
          <label for="storageLocationFilter" class="block text-sm font-medium text-gray-700 mb-1">
            保管場所
          </label>
          <select
            id="storageLocationFilter"
            v-model="filters.storageLocation"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">すべて</option>
            <option value="職員室">職員室</option>
            <option value="図書室">図書室</option>
            <option value="生物室">生物室</option>
          </select>
        </div>

        <!-- 受入日範囲フィルター -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            受入日範囲
          </label>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <input
                type="date"
                v-model="filters.startDate"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div>
              <input
                type="date"
                v-model="filters.endDate"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- フィルタークリアボタン -->
      <div class="mt-4 flex justify-end">
        <button
          v-if="hasActiveFilters"
          @click="clearFilters"
          class="text-sm text-gray-600 hover:text-gray-900"
        >
          フィルターをクリア
        </button>
      </div>
    </div>

    <!-- 既存のコンテンツ部分 -->
    <div class="max-w-7xl mx-auto">
      <!-- ページヘッダー -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">蔵書一覧</h1>
            <p v-if="userPermissions.isAdmin" class="mt-1 text-sm text-gray-600">
              登録されている書籍の一覧を表示します
            </p>
            <p v-else class="mt-1 text-sm text-gray-600">
              図書室の蔵書を検索・閲覧できます
            </p>
          </div>
          <!-- 管理者のみボタンを表示 -->
          <div v-if="userPermissions.isAdmin" class="flex items-center space-x-3">
            <button
              @click="exportPdf"
              :disabled="pdfExporting"
              class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md disabled:opacity-50"
            >
              {{ pdfExporting ? 'PDF作成中...' : 'PDF出力' }}
            </button>
            <router-link
              to="/books/create"
              class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md"
            >
              + 新規登録
            </router-link>
          </div>
        </div>
      </div>

      <!-- エラー表示 -->
      <div v-if="error" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
          <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <div class="ml-3">
            <p class="text-sm text-red-800">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- 検索・フィルターエリア -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
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
              <option value="created_at_desc">登録日（新しい順）</option>
              <option value="created_at_asc">登録日（古い順）</option>
              <option value="title_asc">タイトル（あいうえお順）</option>
              <option value="title_desc">タイトル（んをわ順）</option>
            </select>
          </div>

          <!-- フィルタークリア -->
          <div class="flex items-end">
            <button
              v-if="hasActiveFilters"
              @click="clearFilters"
              class="w-full px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm"
            >
              フィルターをクリア
            </button>
          </div>
        </div>

        <!-- 統計情報 -->
        <div class="border-t border-gray-200 pt-4">
          <div class="flex items-center justify-between text-sm text-gray-600">
            <div>
              全{{ books.length }}冊中 {{ filteredBooks.length }}冊を表示
            </div>
          </div>
        </div>
      </div>

      <!-- ローディング -->
      <div v-if="loading" class="text-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">書籍一覧を読み込んでいます...</p>
      </div>

      <!-- 書籍一覧 -->
      <div v-else-if="filteredBooks.length > 0" class="space-y-4">
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
                      <span v-if="book.volume_number" class="text-gray-600 ml-1">（{{ book.volume_number }}）</span>
                    </router-link>
                  </h3>
                  <p v-if="book.title_transcription" class="text-sm text-gray-600 mb-2">{{ book.title_transcription }}</p>
                </div>
              </div>

              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 text-sm text-gray-600">
                  <div>
                    <span class="font-medium">著者:</span> {{ book.author || '不明' }}
                  </div>
                  <div v-if="book.publisher">
                    <span class="font-medium">出版社:</span> {{ book.publisher }}
                  </div>
                  <div v-if="book.published_date">
                    <span class="font-medium">出版日:</span> {{ formatDate(book.published_date) }}
                  </div>
                  <div v-if="book.storage_location">
                    <span class="font-medium">保管場所:</span> {{ book.storage_location }}
                  </div>
                  <div>
                    <span class="font-medium">冊数:</span>
                    <span v-if="!editingQuantity[book.id]" class="inline-flex items-center gap-2">
                      {{ book.quantity || 1 }}冊
                      <!-- 管理者のみ冊数編集ボタンを表示 -->
                      <button
                        v-if="userPermissions.isAdmin"
                        @click="startEditQuantity(book)"
                        class="text-blue-500 hover:text-blue-700 text-xs"
                        title="冊数を編集"
                      >
                        ✏️
                      </button>
                    </span>
                    <!-- 管理者のみ冊数編集フォームを表示 -->
                    <div v-else-if="userPermissions.isAdmin" class="inline-flex items-center gap-2">
                      <input
                        v-model.number="tempQuantity[book.id]"
                        type="number"
                        min="1"
                        max="999"
                        class="w-16 px-2 py-1 text-xs border border-gray-300 rounded"
                        @keyup.enter="saveQuantity(book)"
                        @keyup.escape="cancelEditQuantity(book.id)"
                      >
                      <span class="text-xs">冊</span>
                      <button
                        @click="saveQuantity(book)"
                        class="text-green-600 hover:text-green-800 text-xs"
                        title="保存"
                      >
                        ✓
                      </button>
                      <button
                        @click="cancelEditQuantity(book.id)"
                        class="text-red-600 hover:text-red-800 text-xs"
                        title="キャンセル"
                      >
                        ✗
                      </button>
                    </div>
                  </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-gray-600 mt-2">
                  <div>
                    <span class="font-medium">貸出状態:</span>
                    <span
                      :class="{
                        'px-2 py-1 rounded-full text-sm ml-2 font-medium': true,
                        'bg-red-100 text-red-800 border border-red-200': book.is_borrowed,
                        'bg-green-100 text-green-800 border border-green-200': !book.is_borrowed
                      }"
                    >
                      {{ book.is_borrowed ? '貸出中' : '貸出可能' }}
                    </span>
                  </div>
                </div>


              </div>
            </div>

            <!-- 管理者のみ操作ボタンを表示 -->
            <div v-if="userPermissions.isAdmin" class="flex items-center space-x-2 ml-4">
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
              <div
                v-if="book.is_borrowed"
                class="px-3 py-1 rounded text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                title="この本は現在貸出中です"
              >
                貸出不可
              </div>
              <router-link
                v-else
                :to="`/borrows/create?book=${book.id}`"
                class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm"
              >
                貸出
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- 書籍なし -->
      <div v-else class="text-center py-8">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">書籍が見つかりません</h3>
        <p class="text-gray-600 mb-4">
          {{ hasActiveFilters ? '検索条件に一致する書籍がありません。フィルターを調整してみてください。' : 'まだ書籍が登録されていません。' }}
        </p>
        <router-link
          v-if="!hasActiveFilters"
          to="/books/create"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md"
        >
          最初の書籍を登録する
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const books = ref([]);
const loading = ref(true);
const pdfExporting = ref(false);
const error = ref('');

// 冊数編集関連
const editingQuantity = ref({});
const tempQuantity = ref({});

// 権限管理
const userPermissions = ref({
  canCreateBooks: false,
  canEditBooks: false,
  canUseBorrowFeatures: false,
  isAdmin: false
});

// 権限情報をローカルストレージから読み込み
const loadPermissions = () => {
  try {
    const stored = localStorage.getItem('userPermissions');
    if (stored) {
      userPermissions.value = { ...userPermissions.value, ...JSON.parse(stored) };
    }
  } catch (error) {
    console.error('権限情報の読み込みに失敗:', error);
  }
};

const filters = reactive({
  searchTitle: '',
  searchAuthor: '',
  sortBy: 'created_at_desc',
  startDate: '',
  endDate: '',
  ndc: '',
  isbnType: '',
  storageLocation: ''
});

const loadBooks = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    // セッション認証のためのヘッダー設定を確認
    axios.defaults.withCredentials = true;
    console.log('BookIndex loadBooks - Starting API request');
    const params = {};
    if (filters.startDate) params.start_date = filters.startDate;
    if (filters.endDate) params.end_date = filters.endDate;
    if (filters.ndc) params.ndc_category = filters.ndc;
    if (filters.isbnType) params.isbn_type = filters.isbnType;
    if (filters.storageLocation) params.storage_location = filters.storageLocation;
    
    const response = await axios.get('/api/books', { params });
    books.value = response.data.data || response.data;
  } catch (err) {
    console.error('BookIndex loadBooks error:', err);
    if (err.response) {
      console.error('Response status:', err.response.status);
      console.error('Response data:', err.response.data);
      error.value = err.response.data?.message || 'データの読み込みに失敗しました';
    } else if (err.request) {
      error.value = 'サーバーに接続できませんでした';
    } else {
      error.value = '予期しないエラーが発生しました: ' + err.message;
    }
  } finally {
    loading.value = false;
  }
};

const filteredBooks = computed(() => {
  let result = [...books.value];

  // タイトル検索（クライアントサイドでの追加フィルタリング）
  if (filters.searchTitle) {
    const searchTerm = filters.searchTitle.toLowerCase();
    result = result.filter(book => 
      book.title.toLowerCase().includes(searchTerm) ||
      (book.title_transcription && book.title_transcription.toLowerCase().includes(searchTerm))
    );
  }

  // 著者検索（クライアントサイドでの追加フィルタリング）
  if (filters.searchAuthor) {
    const searchTerm = filters.searchAuthor.toLowerCase();
    result = result.filter(book => 
      book.author && book.author.toLowerCase().includes(searchTerm)
    );
  }

  // 受け入れ日フィルター（サーバーサイドで既に処理されているが、念のためクライアントサイドでも確認）
  if (filters.startDate) {
    result = result.filter(book => 
      book.acceptance_date && new Date(book.acceptance_date) >= new Date(filters.startDate)
    );
  }
  
  if (filters.endDate) {
    result = result.filter(book => 
      book.acceptance_date && new Date(book.acceptance_date) <= new Date(filters.endDate)
    );
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
      default:
        return new Date(b.created_at) - new Date(a.created_at);
    }
  });

  return result;
});

// アクティブなフィルターがあるかどうか
const hasActiveFilters = computed(() => {
  return filters.searchTitle || filters.searchAuthor || filters.startDate || filters.endDate || filters.ndc || filters.isbnType;
});

const applyFilters = () => {
  // フィルターが変更された時の処理（リアクティブなのでcomputedが自動更新される）
};

const clearFilters = () => {
  filters.searchTitle = '';
  filters.searchAuthor = '';
  filters.sortBy = 'created_at_desc';
  filters.startDate = '';
  filters.endDate = '';
  filters.ndc = '';
  filters.isbnType = '';
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ja-JP');
};

const exportPdf = async () => {
  try {
    pdfExporting.value = true;
    
    // Build query parameters based on current filters
    const params = new URLSearchParams();
    
    // NDC分類フィルター
    if (filters.ndc) {
      params.append('ndc_category', filters.ndc);
    }

    // 受入日範囲フィルター
    if (filters.startDate) {
      params.append('start_date', filters.startDate);
    }
    if (filters.endDate) {
      params.append('end_date', filters.endDate);
    }

    // ISBNコード有無フィルター
    if (filters.isbnType) {
      params.append('isbn_type', filters.isbnType);
    }

    // タイトルフィルター
    if (filters.searchTitle) {
      params.append('search_title', filters.searchTitle);
    }

    // 著者フィルター
    if (filters.searchAuthor) {
      params.append('search_author', filters.searchAuthor);
    }
    
    const response = await axios.get('/api/books/pdf', {
      params: Object.fromEntries(params),
      responseType: 'blob'
    });

    // Create blob and download
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    
    // Generate filename with timestamp
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
    link.download = `書籍一覧_${timestamp}.pdf`;
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

  } catch (error) {
    console.error('PDF Export Error:', error);
    alert('PDF出力に失敗しました。もう一度お試しください。');
  } finally {
    pdfExporting.value = false;
  }
};

// 冊数編集を開始
const startEditQuantity = (book) => {
  editingQuantity.value[book.id] = true;
  tempQuantity.value[book.id] = book.quantity || 1;
};

// 冊数編集をキャンセル
const cancelEditQuantity = (bookId) => {
  editingQuantity.value[bookId] = false;
  delete tempQuantity.value[bookId];
};

// 冊数を保存
const saveQuantity = async (book) => {
  const newQuantity = tempQuantity.value[book.id];
  
  if (!newQuantity || newQuantity < 1 || newQuantity > 999) {
    alert('冊数は1から999の間で入力してください。');
    return;
  }

  try {
    await axios.put(`/api/books/${book.id}`, {
      ...book,
      quantity: newQuantity
    });

    // 成功したら表示を更新
    book.quantity = newQuantity;
    editingQuantity.value[book.id] = false;
    delete tempQuantity.value[book.id];
  } catch (err) {
    alert('冊数の更新に失敗しました。もう一度お試しください。');
    console.error(err);
  }
};

onMounted(() => {
  loadPermissions();
  loadBooks();
});

watch(
  [() => filters.startDate, () => filters.endDate, () => filters.ndc, () => filters.isbnType],
  () => loadBooks(),
  { deep: true }
);

console.log('BookIndex.vue loaded successfully');
</script>
