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
              
              <div>
                <dt class="text-sm font-medium text-gray-500">読書状況</dt>
                <dd class="mt-1">
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                        :class="getStatusClass(book.reading_status)">
                    {{ getStatusLabel(book.reading_status) }}
                  </span>
                </dd>
              </div>
            </dl>
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

          <!-- アクション -->
          <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">クイックアクション</h2>
            
            <div class="space-y-3">
              <button
                v-if="book.reading_status !== 'reading'"
                @click="updateStatus('reading')"
                class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm"
              >
                読書開始
              </button>
              
              <button
                v-if="book.reading_status !== 'read'"
                @click="updateStatus('read')"
                class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm"
              >
                読了にする
              </button>
              
              <button
                v-if="book.reading_status !== 'unread'"
                @click="updateStatus('unread')"
                class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm"
              >
                未読に戻す
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const book = ref(null);
const loading = ref(true);
const error = ref('');

// 受け入れ情報があるかどうか
const hasAcceptanceInfo = computed(() => {
  return book.value && (
    book.value.acceptance_date || 
    book.value.acceptance_type || 
    book.value.acceptance_source || 
    book.value.discard
  );
});

const loadBook = async () => {
  try {
    const response = await axios.get(`/api/books/${route.params.id}`);
    book.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || '書籍が見つかりませんでした';
  } finally {
    loading.value = false;
  }
};

const updateStatus = async (status) => {
  try {
    console.log('Updating status to:', status);
    console.log('Book ID:', book.value.id);
    console.log('Request payload:', { reading_status: status });
    
    const response = await axios.patch(`/api/books/${book.value.id}`, {
      reading_status: status
    });
    
    console.log('Update response:', response.data);
    
    book.value.reading_status = status;
    
    // 成功通知（将来的に通知システムを追加）
    alert(`読書状況を「${getStatusLabel(status)}」に更新しました`);
    
  } catch (err) {
    console.error('Update error:', err);
    console.error('Error response:', err.response);
    console.error('Error data:', err.response?.data);
    
    let errorMessage = 'エラーが発生しました';
    if (err.response?.data?.errors) {
      errorMessage = Object.values(err.response.data.errors).flat().join(', ');
    } else if (err.response?.data?.message) {
      errorMessage = err.response.data.message;
    }
    
    alert('更新に失敗しました: ' + errorMessage);
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

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleString('ja-JP');
};

onMounted(() => {
  loadBook();
});

console.log('BookShow.vue loaded successfully');
</script>
