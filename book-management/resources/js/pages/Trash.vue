<template>
  <div class="min-h-screen bg-gray-100">
    <!-- ヘッダー -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <span class="text-4xl mr-3">🗑️</span>
            <h1 class="text-3xl font-bold text-gray-900">ゴミ箱</h1>
          </div>
          <router-link
            to="/books"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            ← 蔵書一覧に戻る
          </router-link>
        </div>
        <p class="text-gray-600 mt-2">削除された本の一覧です。復元または完全に削除できます。</p>
      </div>
    </div>

    <!-- メインコンテンツ -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- エラー表示 -->
      <div v-if="error" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
        {{ error }}
      </div>

      <!-- 成功メッセージ -->
      <div v-if="successMessage" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
        {{ successMessage }}
      </div>

      <!-- ローディング -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
        <p class="mt-4 text-gray-600">読み込み中...</p>
      </div>

      <!-- テーブル -->
      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <div v-if="trashedBooks.length === 0" class="px-6 py-12 text-center">
          <span class="text-6xl mb-4 block">📚</span>
          <p class="text-gray-600 text-lg">ゴミ箱は空です</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  削除日時
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  ISBN / JAN
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  書名
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  著者
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  出版社
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  操作
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="book in trashedBooks" :key="book.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatDate(book.deleted_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ book.isbn || book.jan_code || '-' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">
                  <div class="font-medium">{{ book.title }}</div>
                  <div class="text-gray-500 text-xs mt-1">受入日: {{ formatDate(book.acceptance_date) }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">
                  {{ book.author || '-' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">
                  {{ book.publisher || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                  <button
                    @click="restoreBook(book.id)"
                    :disabled="restoring"
                    class="text-blue-600 hover:text-blue-900 disabled:text-gray-400"
                  >
                    復元
                  </button>
                  <button
                    @click="confirmDelete(book)"
                    :disabled="deleting"
                    class="text-red-600 hover:text-red-900 disabled:text-gray-400"
                  >
                    削除
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 削除確認ダイアログ -->
    <div v-if="deletingBook" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="px-6 py-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-2">本を完全に削除しますか？</h3>
          <p class="text-sm text-gray-600 mb-4">
            「{{ deletingBook.title }}」を完全に削除します。この操作は取り消せません。
          </p>
          <div class="bg-gray-50 p-3 rounded text-sm text-gray-700 mb-4">
            <div><strong>ISBN/JAN:</strong> {{ deletingBook.isbn || deletingBook.jan_code || '-' }}</div>
            <div><strong>著者:</strong> {{ deletingBook.author || '-' }}</div>
            <div><strong>出版社:</strong> {{ deletingBook.publisher || '-' }}</div>
          </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
          <button
            @click="cancelDelete"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            キャンセル
          </button>
          <button
            @click="permanentDelete"
            :disabled="deleting"
            class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 disabled:opacity-50"
          >
            {{ deleting ? '削除中...' : '完全に削除' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const trashedBooks = ref([]);
const loading = ref(true);
const error = ref('');
const successMessage = ref('');
const restoring = ref(false);
const deleting = ref(false);
const deletingBook = ref(null);

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const loadTrashedBooks = async () => {
  try {
    loading.value = true;
    error.value = '';
    const response = await axios.get('/api/books/trash');
    trashedBooks.value = response.data.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'ゴミ箱の読み込みに失敗しました';
    console.error('Error loading trash:', err);
  } finally {
    loading.value = false;
  }
};

const restoreBook = async (bookId) => {
  try {
    restoring.value = true;
    error.value = '';
    successMessage.value = '';
    
    await axios.post(`/api/books/${bookId}/restore`);
    
    successMessage.value = '本を復元しました';
    await loadTrashedBooks();
    
    // 3秒後にメッセージを消す
    setTimeout(() => {
      successMessage.value = '';
    }, 3000);
  } catch (err) {
    error.value = err.response?.data?.message || '復元に失敗しました';
    console.error('Error restoring book:', err);
  } finally {
    restoring.value = false;
  }
};

const confirmDelete = (book) => {
  deletingBook.value = book;
};

const cancelDelete = () => {
  deletingBook.value = null;
};

const permanentDelete = async () => {
  if (!deletingBook.value) return;

  try {
    deleting.value = true;
    error.value = '';
    successMessage.value = '';
    
    await axios.delete(`/api/books/${deletingBook.value.id}/permanent`);
    
    successMessage.value = '本を完全に削除しました';
    deletingBook.value = null;
    await loadTrashedBooks();
    
    // 3秒後にメッセージを消す
    setTimeout(() => {
      successMessage.value = '';
    }, 3000);
  } catch (err) {
    error.value = err.response?.data?.message || '削除に失敗しました';
    console.error('Error permanently deleting book:', err);
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  loadTrashedBooks();
});
</script>
