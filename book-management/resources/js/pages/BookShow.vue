<template>
  <div class="max-w-4xl mx-auto">
    <!-- ローディング -->
    <div v-if="loading" class="text-center py-8">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">書籍情報を読み込んでいます...</p>
    </div>

    <!-- エラー -->
    <div v-else-if="error" class="text-center py-8">
      <div class="text-red-600 mb-4">
        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">書籍が見つかりません</h3>
      <p class="text-gray-600 mb-4">{{ error }}</p>
      <router-link to="/books" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
        書籍一覧に戻る
      </router-link>
    </div>

    <!-- 書籍詳細 -->
    <div v-else-if="book">
      <!-- ページヘッダー -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ book.title }}</h1>
            <p v-if="book.title_transcription" class="mt-1 text-lg text-gray-600">{{ book.title_transcription }}</p>
          </div>
          <div class="flex items-center space-x-3">
            <router-link
              :to="`/books/${book.id}/edit`"
              class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-md"
            >
              編集
            </router-link>
            <button
              @click="deleteBook"
              class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md"
            >
              削除
            </button>
            <router-link
              to="/books"
              class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md"
            >
              ← 一覧に戻る
            </router-link>
          </div>
        </div>
      </div>

      <!-- メイン情報 -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 基本情報 -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">基本情報</h2>
            
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">著者</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ book.author }}</dd>
              </div>
              
              <div v-if="book.publisher">
                <dt class="text-sm font-medium text-gray-500">出版社</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ book.publisher }}</dd>
              </div>
              
              <div v-if="book.published_date">
                <dt class="text-sm font-medium text-gray-500">出版日</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(book.published_date) }}</dd>
              </div>
              
              <div v-if="book.isbn">
                <dt class="text-sm font-medium text-gray-500">ISBN</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ book.isbn }}</dd>
              </div>
              
              <div v-if="book.pages">
                <dt class="text-sm font-medium text-gray-500">ページ数</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ book.pages }}ページ</dd>
              </div>
              
              <div v-if="book.price">
                <dt class="text-sm font-medium text-gray-500">価格</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ Math.floor(book.price) }}円</dd>
              </div>
              
              <div v-if="book.ndc">
                <dt class="text-sm font-medium text-gray-500">NDC分類</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ book.ndc }}</dd>
              </div>
            </dl>
          </div>

          <!-- 貸出状態 -->
          <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">貸出状態</h2>
            <div class="mb-4">
              <span
                :class="{
                  'px-3 py-1 rounded-full text-sm font-medium': true,
                  'bg-red-100 text-red-800 border border-red-200': book.is_borrowed,
                  'bg-green-100 text-green-800 border border-green-200': !book.is_borrowed
                }"
              >
                {{ book.is_borrowed ? '貸出中' : '貸出可能' }}
              </span>
              <span 
                v-if="book.is_borrowed && book.current_borrow?.student"
                class="ml-2 text-sm text-gray-600"
              >
                {{ book.current_borrow.student.grade }}年
                {{ book.current_borrow.student.class }}
                {{ book.current_borrow.student.name }}が借りています
              </span>
            </div>
          </div>

          <!-- 貸出履歴 -->
          <div class="bg-white rounded-lg shadow p-6 mt-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">貸出履歴</h2>
              <button
                v-if="!showAllHistory && book.borrow_history?.length > 0"
                @click="loadFullHistory"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                全ての履歴を表示
              </button>
            </div>

            <!-- 初期表示の履歴（最初の数件） -->
            <div v-if="!showAllHistory && book.borrow_history?.length > 0" class="space-y-4">
              <div 
                v-for="history in book.borrow_history" 
                :key="history.id"
                class="bg-gray-50 p-4 rounded-lg"
              >
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center">
                    <span class="font-medium text-gray-900">
                      {{ history.student.grade }}年
                      {{ history.student.class }}
                      {{ history.student.name }}
                    </span>
                  </div>
                  <span class="text-sm text-gray-500">
                    {{ history.duration }}
                  </span>
                </div>
                <div class="text-sm text-gray-600">
                  {{ history.borrowed_date }} 〜 {{ history.returned_date || '貸出中' }}
                </div>
              </div>
            </div>

            <!-- ページネーション付き履歴 -->
            <div v-else-if="showAllHistory">
              <!-- ローディング -->
              <div v-if="historyLoading" class="text-center py-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-sm text-gray-600">履歴を読み込み中...</p>
              </div>

              <!-- 履歴データ -->
              <div v-else-if="historyData.length > 0" class="space-y-4">
                <div 
                  v-for="history in historyData" 
                  :key="history.id"
                  class="bg-gray-50 p-4 rounded-lg"
                >
                  <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                      <span class="font-medium text-gray-900">
                        {{ history.student.grade }}年
                        {{ history.student.class }}
                        {{ history.student.name }}
                      </span>
                      <span v-if="history.is_overdue" class="ml-2 px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                        延滞
                      </span>
                    </div>
                    <span class="text-sm text-gray-500">
                      {{ history.duration }}
                    </span>
                  </div>
                  <div class="text-sm text-gray-600">
                    {{ history.borrowed_date }} 〜 {{ history.returned_date }}
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    返却予定: {{ history.due_date }}
                  </div>
                </div>

                <!-- ページネーション -->
                <div v-if="historyPagination.last_page > 1" class="flex items-center justify-between border-t pt-4 mt-6">
                  <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-700">
                      {{ historyPagination.from || 0 }}-{{ historyPagination.to || 0 }} / {{ historyPagination.total }}件
                    </span>
                  </div>
                  
                  <div class="flex items-center space-x-1">
                    <!-- 前のページ -->
                    <button
                      @click="loadHistory(historyPagination.current_page - 1)"
                      :disabled="historyPagination.current_page <= 1"
                      class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      ←
                    </button>

                    <!-- ページ番号 -->
                    <template v-for="page in getPageRange(historyPagination.current_page, historyPagination.last_page)" :key="page">
                      <button
                        v-if="page !== '...'"
                        @click="loadHistory(page)"
                        :class="[
                          'px-3 py-2 text-sm font-medium rounded-md',
                          page === historyPagination.current_page
                            ? 'bg-blue-600 text-white'
                            : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                        ]"
                      >
                        {{ page }}
                      </button>
                      <span v-else class="px-2 py-2 text-sm text-gray-500">...</span>
                    </template>

                    <!-- 次のページ -->
                    <button
                      @click="loadHistory(historyPagination.current_page + 1)"
                      :disabled="historyPagination.current_page >= historyPagination.last_page"
                      class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      →
                    </button>
                  </div>
                </div>
              </div>

              <!-- データなし -->
              <div v-else class="text-center py-4 text-gray-500">
                貸出履歴はありません
              </div>
            </div>

            <!-- 初期状態でデータなし -->
            <div v-else-if="!book.borrow_history?.length" class="text-center py-4 text-gray-500">
              貸出履歴はありません
            </div>
          </div>

          <!-- 受け入れ・廃棄情報 -->
          <div v-if="hasAcceptanceInfo" class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">受け入れ・廃棄情報</h2>
            
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-if="book.acceptance_date">
                <dt class="text-sm font-medium text-gray-500">受け入れ日</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(book.acceptance_date) }}</dd>
              </div>
              
              <div v-if="book.acceptance_type">
                <dt class="text-sm font-medium text-gray-500">受け入れ種別</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ book.acceptance_type }}</dd>
              </div>
              
              <div v-if="book.acceptance_source">
                <dt class="text-sm font-medium text-gray-500">受け入れ元</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ book.acceptance_source }}</dd>
              </div>
              
              <div v-if="book.storage_location">
                <dt class="text-sm font-medium text-gray-500">保管場所</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ book.storage_location }}</dd>
              </div>
              
              <div v-if="book.discard">
                <dt class="text-sm font-medium text-gray-500">廃棄情報</dt>
                <dd class="mt-1">
                  <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                        :class="getDiscardStatusClass(book.discard)">
                    {{ book.discard }}
                  </span>
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- サイドバー情報 -->
        <div class="space-y-6">
          <!-- メタ情報 -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">登録情報</h2>
            
            <dl class="space-y-3">
              <div>
                <dt class="text-sm font-medium text-gray-500">登録日</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(book.created_at) }}</dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">最終更新</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(book.updated_at) }}</dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">書籍ID</dt>
                <dd class="mt-1 text-sm text-gray-900">#{{ book.id }}</dd>
              </div>
            </dl>
          </div>

          <!-- バーコード情報（独自JANコードの場合のみ表示） -->
          <div v-if="isCustomJanCode" class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
              生成バーコード
            </h2>
            
            <div class="space-y-4">
              <div>
                <dt class="text-sm font-medium text-gray-500 mb-2">JANコード</dt>
                <dd class="text-lg font-mono font-bold text-gray-900 mb-3">{{ book.isbn }}</dd>
              </div>
              
              <!-- バーコード表示エリア -->
              <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                <div class="mb-2">
                  <span class="text-sm font-medium text-gray-700">バーコード</span>
                </div>
                <canvas 
                  ref="barcodeCanvas" 
                  class="mx-auto border bg-white rounded"
                  style="max-width: 100%; height: auto;"
                ></canvas>
              </div>
              
              <!-- PDF出力ボタン -->
              <div class="flex justify-center">
                <button
                  @click="downloadBarcodePdf"
                  :disabled="downloadingPdf"
                  class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                >
                  <svg v-if="!downloadingPdf" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  <svg v-else class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ downloadingPdf ? 'PDF生成中...' : 'バーコードPDFダウンロード' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onActivated, onBeforeUnmount, computed, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const book = ref(null);
const loading = ref(true);
const error = ref('');
const showAllHistory = ref(false);

// バーコード関連の変数
const barcodeCanvas = ref(null);
const downloadingPdf = ref(false);

// ページネーション用の変数
const historyPagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null
});
const historyData = ref([]);
const historyLoading = ref(false);

// ページネーション付き履歴を読み込む
const loadHistory = async (page = 1) => {
  try {
    historyLoading.value = true;
    const response = await axios.get(`/api/books/${route.params.id}/history`, {
      params: {
        page: page,
        per_page: historyPagination.value.per_page
      }
    });
    
    historyData.value = response.data.data;
    historyPagination.value = response.data.pagination;
    showAllHistory.value = true;
  } catch (err) {
    error.value = '履歴の取得に失敗しました';
  } finally {
    historyLoading.value = false;
  }
};

// 全履歴を読み込む（後方互換性のため残す）
const loadFullHistory = () => {
  loadHistory(1);
};

// ページ範囲を計算する関数
const getPageRange = (currentPage, lastPage) => {
  const range = [];
  const delta = 2; // 現在のページの前後に表示するページ数

  // 開始ページ
  let start = Math.max(1, currentPage - delta);
  let end = Math.min(lastPage, currentPage + delta);

  // 範囲を調整
  if (end - start < delta * 2) {
    if (start === 1) {
      end = Math.min(lastPage, start + delta * 2);
    } else if (end === lastPage) {
      start = Math.max(1, end - delta * 2);
    }
  }

  // 最初のページ
  if (start > 1) {
    range.push(1);
    if (start > 2) {
      range.push('...');
    }
  }

  // 中央のページ範囲
  for (let i = start; i <= end; i++) {
    range.push(i);
  }

  // 最後のページ
  if (end < lastPage) {
    if (end < lastPage - 1) {
      range.push('...');
    }
    range.push(lastPage);
  }

  return range;
};

// 受け入れ情報があるかどうか
const hasAcceptanceInfo = computed(() => {
  return book.value && (
    book.value.acceptance_date || 
    book.value.acceptance_type || 
    book.value.acceptance_source || 
    book.value.storage_location ||
    book.value.discard
  );
});

// 独自JANコードかどうかを判定
const isCustomJanCode = computed(() => {
  return book.value && book.value.isbn && book.value.isbn.startsWith('938525');
});

const loadBook = async () => {
  try {
    const response = await axios.get(`/api/books/${route.params.id}`);
    book.value = response.data.data || response.data;
    console.log('Book data loaded:', book.value);
    console.log('Storage location:', book.value.storage_location);
    
    // 独自JANコードの場合、バーコードを生成
    if (book.value && book.value.isbn && book.value.isbn.startsWith('938525')) {
      // DOM更新を待ってからバーコード生成
      await nextTick();
      generateBarcode(book.value.isbn);
    }
  } catch (err) {
    console.error('Error loading book:', err);
    error.value = err.response?.data?.message || '書籍が見つかりませんでした';
  } finally {
    loading.value = false;
  }
};

const deleteBook = async () => {
  if (!confirm('この書籍を削除してもよろしいですか？\nこの操作は取り消せません。')) {
    return;
  }
  
  try {
    await axios.delete(`/api/books/${book.value.id}`);
    
    router.push({
      name: 'BookIndex',
      query: { success: '書籍が削除されました' }
    });
    
  } catch (err) {
    alert('削除に失敗しました: ' + (err.response?.data?.message || 'エラーが発生しました'));
  }
};

const getDiscardStatusClass = (status) => {
  if (!status) return '';
  
  switch (status) {
    case '廃棄予定':
      return 'bg-orange-100 text-orange-800';
    case '廃棄済み':
      return 'bg-red-100 text-red-800';
    case '譲渡予定':
      return 'bg-blue-100 text-blue-800';
    case '譲渡済み':
      return 'bg-green-100 text-green-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ja-JP');
};

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleString('ja-JP');
};

// 初期ロードと、ルートパラメータが変更された時にデータを再読み込み
onMounted(() => {
  loadBook();
});

// ウィンドウにフォーカスが戻った時にデータを更新（他のタブで操作された可能性に対応）
const handleFocus = () => {
  loadBook();
};

onMounted(() => {
  window.addEventListener('focus', handleFocus);
});

onBeforeUnmount(() => {
  window.removeEventListener('focus', handleFocus);
});

// コンポーネントがアクティブになった時（keep-alive使用時やページ遷移から戻った時）
onActivated(() => {
  // データをリセットして強制的に再読み込み
  book.value = null;
  loading.value = true;
  error.value = '';
  showAllHistory.value = false;
  historyData.value = [];
  loadBook();
});

// ルートパラメータの変更を監視してデータを再読み込み
watch(
  () => route.params.id,
  (newId, oldId) => {
    if (newId && newId !== oldId) {
      book.value = null;
      loading.value = true;
      error.value = '';
      showAllHistory.value = false;
      historyData.value = [];
      loadBook();
    }
  }
);

// バーコード生成
const generateBarcode = (code) => {
  if (!barcodeCanvas.value) return;
  
  try {
    // JsBarcode を使用
    if (typeof JsBarcode !== 'undefined') {
      JsBarcode(barcodeCanvas.value, code, {
        format: "EAN13",
        width: 2,
        height: 100,
        displayValue: true,
        fontSize: 14,
        margin: 10,
        background: "#ffffff",
        lineColor: "#000000"
      });
    } else {
      // フォールバック: シンプルなテキスト表示
      const ctx = barcodeCanvas.value.getContext('2d');
      barcodeCanvas.value.width = 300;
      barcodeCanvas.value.height = 120;
      ctx.clearRect(0, 0, barcodeCanvas.value.width, barcodeCanvas.value.height);
      ctx.font = '16px monospace';
      ctx.fillStyle = '#000000';
      ctx.textAlign = 'center';
      ctx.fillText(code, 150, 60);
      ctx.font = '12px sans-serif';
      ctx.fillText('バーコード表示にはJsBarcodeが必要です', 150, 80);
    }
  } catch (error) {
    console.error('バーコード生成エラー:', error);
  }
};

// バーコードPDFダウンロード
const downloadBarcodePdf = async () => {
  if (!book.value || !book.value.isbn) return;
  
  // カスタムJANコード（938525で始まる）以外はPDF生成しない
  if (!book.value.isbn.startsWith('938525')) {
    alert('この書籍はPDFダウンロードに対応していません');
    return;
  }
  
  downloadingPdf.value = true;
  
  try {
    const response = await axios.post('/api/generate-barcode-pdf', {
      jan_code: book.value.isbn
    }, {
      responseType: 'blob'
    });
    
    // PDFファイルをダウンロード
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `barcode_${book.value.isbn}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
  } catch (error) {
    console.error('PDF生成エラー:', error);
    
    // レスポンスがJSONエラーの場合の処理
    if (error.response && error.response.data) {
      const reader = new FileReader();
      reader.onload = function() {
        try {
          const errorData = JSON.parse(reader.result);
          alert(`PDFの生成に失敗しました: ${errorData.message || 'エラーが発生しました'}`);
        } catch {
          alert('PDFの生成に失敗しました');
        }
      };
      reader.readAsText(error.response.data);
    } else {
      alert('PDFの生成に失敗しました');
    }
  } finally {
    downloadingPdf.value = false;
  }
};

console.log('BookShow.vue loaded successfully');
</script>
