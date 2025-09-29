<template>
  <div class="container mx-auto px-4 py-8">
    <!-- ヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">本のリクエスト</h1>
          <p class="mt-1 text-sm text-gray-600">
            読みたい本のリクエストを登録できます
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="showCreateModal = true"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md"
          >
            + 新規リクエスト
          </button>
        </div>
      </div>
    </div>

    <!-- リクエスト一覧 -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <ul class="divide-y divide-gray-200">
        <li v-for="request in requests" :key="request.id" class="px-4 py-4 sm:px-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">{{ request.title }}</h3>
              <div class="mt-2 text-sm text-gray-500">
                <p v-if="request.author">著者: {{ request.author }}</p>
                <p>
                  リクエスト者: {{ request.requester_name || '匿名' }}
                  <span class="text-gray-400 ml-2">{{ formatDate(request.created_at) }}</span>
                </p>
                <p v-if="request.admin_comment" class="mt-1 text-blue-600">
                  管理者コメント: {{ request.admin_comment }}
                </p>
              </div>
            </div>
            <div class="flex flex-col items-end">
              <span :class="{
                'px-2 py-1 text-sm rounded-full': true,
                'bg-yellow-100 text-yellow-800': request.status === 'pending',
                'bg-green-100 text-green-800': request.status === 'approved',
                'bg-red-100 text-red-800': request.status === 'rejected'
              }">
                {{ getStatusText(request.status) }}
              </span>
              <span class="mt-2 text-sm text-gray-500">
                {{ formatDate(request.created_at) }}
              </span>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- 新規リクエストモーダル -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
      <div class="bg-white rounded-lg p-8 max-w-lg w-full">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-bold">新規リクエスト</h2>
          <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-500">
            <span class="sr-only">閉じる</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitRequest" class="space-y-4">
          <div class="flex flex-col gap-4">
            <div>
              <label for="title" class="block text-sm font-medium text-gray-700">本の題名 *</label>
              <input
                type="text"
                id="title"
                v-model="newRequest.title"
                required
                placeholder="読みたい本の題名を入力してください"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div>
              <label for="author" class="block text-sm font-medium text-gray-700">著者</label>
              <input
                type="text"
                id="author"
                v-model="newRequest.author"
                placeholder="著者名（わかる場合）"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div>
              <label for="requester_name" class="block text-sm font-medium text-gray-700">お名前</label>
              <input
                type="text"
                id="requester_name"
                v-model="newRequest.requester_name"
                placeholder="お名前（任意）"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div class="flex justify-end">
              <button
                type="submit"
                class="px-6 py-2 text-sm font-medium text-white bg-blue-500 rounded-md hover:bg-blue-600"
              >
                リクエストする
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();
const showCreateModal = ref(false);
const requests = ref([]);

// 新規リクエスト用のフォームデータ
const newRequest = ref({
  title: '',
  author: '',
  requester_name: '',
});

// リクエスト一覧を取得
const loadRequests = async () => {
  try {
    const response = await axios.get('/api/book-requests');
    if (response.data.data) {
      // 日付で降順ソート（新しい順）
      requests.value = response.data.data.sort((a, b) => 
        new Date(b.created_at) - new Date(a.created_at)
      );
      
      // ステータスに応じて色分けするためのクラスを追加
      requests.value = requests.value.map(request => ({
        ...request,
        statusClass: {
          'px-2 py-1 text-sm rounded-full': true,
          'bg-yellow-100 text-yellow-800': request.status === 'pending',
          'bg-green-100 text-green-800': request.status === 'approved',
          'bg-red-100 text-red-800': request.status === 'rejected'
        }
      }));
    }
  } catch (error) {
    console.error('Load requests error:', error);
    toast.error('リクエスト一覧の取得に失敗しました');
  }
};

// 新規リクエストを送信
const submitRequest = async () => {
  try {
    const response = await axios.post('/api/book-requests', newRequest.value);
    if (response.data.success) {
      toast.success('リクエストを登録しました');
      await loadRequests(); // リスト更新
      showCreateModal.value = false;
      // フォームリセット
      newRequest.value = {
        title: '',
        author: '',
        requester_name: '',
      };
    } else {
      toast.error('リクエストの登録に失敗しました');
    }
  } catch (error) {
    console.error('Request error:', error);
    toast.error(error.response?.data?.message || 'リクエストの登録に失敗しました');
    showCreateModal.value = false;
    await loadRequests();
    // フォームをリセット
    newRequest.value = {
      title: '',
      author: '',
      publisher: '',
      reason: '',
    };
  } catch (error) {
    toast.error('リクエストの登録に失敗しました');
  }
};

// ステータスのテキストを取得
const getStatusText = (status) => {
  const statusMap = {
    pending: '審査中',
    approved: '承認済み',
    rejected: '却下',
  };
  return statusMap[status] || status;
};

// 日付を日本語フォーマットに変換
const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日 ${d.getHours()}:${String(d.getMinutes()).padStart(2, '0')}`;
};

// 日付をフォーマット
const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('ja-JP');
};

// コンポーネントがマウントされたときにリクエスト一覧を読み込む
onMounted(async () => {
  await loadRequests();
});
</script>
