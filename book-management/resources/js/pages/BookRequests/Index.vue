<template>
  <div class="container mx-auto px-4 py-8">
    <!-- ヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">本のリクエスト</h1>
          <p class="mt-1 text-sm text-gray-600">
            読みたい本のリクエストを登録・確認できます
          </p>
          <!-- デバッグ用：現在のタブ状態を表示 -->
          <p class="text-xs text-gray-400 mt-1">現在のタブ: {{ activeTab }}</p>
        </div>
      </div>
    </div>

    <!-- タブナビゲーション -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="flex space-x-8">
        <button
          @click="switchTab('request')"
          :class="{
            'border-blue-500 text-blue-600 bg-blue-50': activeTab === 'request',
            'border-transparent text-gray-500 hover:text-gray-700': activeTab !== 'request'
          }"
          class="py-2 px-4 border-b-2 font-medium text-sm rounded-t-lg transition-colors"
          type="button"
        >
          📝 リクエスト申請
        </button>
        <!-- 管理者のみ履歴タブを表示 -->
        <button
          v-if="userPermissions.canViewBookRequestHistory"
          @click="switchTab('history')"
          :class="{
            'border-blue-500 text-blue-600 bg-blue-50': activeTab === 'history',
            'border-transparent text-gray-500 hover:text-gray-700': activeTab !== 'history'
          }"
          class="py-2 px-4 border-b-2 font-medium text-sm rounded-t-lg transition-colors"
          type="button"
        >
          📋 リクエスト履歴
        </button>
      </nav>
    </div>

    <!-- リクエスト申請タブ -->
    <div v-show="activeTab === 'request'" class="bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-medium text-gray-900 mb-4">新しいリクエスト</h2>
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

    <!-- リクエスト履歴タブ (管理者のみ) -->
    <div v-show="activeTab === 'history'" class="bg-white shadow overflow-hidden sm:rounded-lg">
      <!-- 管理者の場合は履歴を表示 -->
      <div v-if="userPermissions.canViewBookRequestHistory">
        <div class="px-4 py-3 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">リクエスト履歴</h2>
          <p class="mt-1 text-sm text-gray-600">過去のリクエストと現在の状況を確認できます</p>
        </div>
        <ul class="divide-y divide-gray-200">
        <li v-if="requests.length === 0" class="px-4 py-8 text-center text-gray-500">
          まだリクエストがありません
        </li>
        <li v-for="request in requests" :key="request.id" class="px-4 py-4">
          <div class="flex items-center justify-between">
            <div class="flex-1">
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
            <div class="flex items-center gap-3">
              <span :class="{
                'px-2 py-1 text-sm rounded-full': true,
                'bg-yellow-100 text-yellow-800': request.status === 'pending',
                'bg-green-100 text-green-800': request.status === 'approved',
                'bg-red-100 text-red-800': request.status === 'rejected'
              }">
                {{ getStatusText(request.status) }}
              </span>
              
              <!-- アクションボタン (管理者のみ) -->
              <div v-if="userPermissions.isAdmin && request.status === 'pending'" class="flex gap-2">
                <button
                  @click="approveRequest(request)"
                  class="px-3 py-1 text-xs font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors"
                  title="承認"
                >
                  ✓ 承認
                </button>
                <button
                  @click="rejectRequest(request)"
                  class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors"
                  title="却下"
                >
                  ✗ 却下
                </button>
              </div>
              
              <!-- 削除ボタン (管理者のみ) -->
              <button
                v-if="userPermissions.isAdmin"
                @click="deleteRequest(request)"
                class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
                title="削除"
              >
                🗑 削除
              </button>
            </div>
          </div>
        </li>
        </ul>
      </div>
      
      <!-- 利用者の場合はアクセス制限メッセージを表示 -->
      <div v-else class="px-4 py-8 text-center">
        <div class="text-gray-500">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">履歴の閲覧は制限されています</h3>
          <p class="text-sm text-gray-600">リクエスト履歴の確認は管理者のみ利用可能です。</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const requests = ref([]);
const activeTab = ref('request'); // デフォルトでリクエスト申請タブを表示

// 権限管理
const userPermissions = ref({
  canViewBookRequestHistory: false,
  isAdmin: false
});

// 新規リクエスト用のフォームデータ
const newRequest = ref({
  title: '',
  author: '',
  requester_name: '',
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

// タブ切り替え関数
const switchTab = (tab) => {
  console.log('Switching to tab:', tab);
  activeTab.value = tab;
};

// リクエスト一覧を取得
const loadRequests = async () => {
  try {
    const response = await axios.get('/api/book-requests');
    if (response.data.data) {
      // 日付で降順ソート（新しい順）
      requests.value = response.data.data.sort((a, b) => 
        new Date(b.created_at) - new Date(a.created_at)
      );
    }
  } catch (error) {
    console.error('Load requests error:', error);
    alert('リクエスト一覧の取得に失敗しました');
  }
};

// 新規リクエストを送信
const submitRequest = async () => {
  try {
    const response = await axios.post('/api/book-requests', newRequest.value);
    if (response.data.success || response.data.data) {
      alert('リクエストを登録しました');
      await loadRequests(); // リスト更新
      // フォームリセット
      newRequest.value = {
        title: '',
        author: '',
        requester_name: '',
      };
      // 管理者の場合のみ履歴タブに自動切り替え
      if (userPermissions.value.canViewBookRequestHistory) {
        activeTab.value = 'history';
      }
    } else {
      alert('リクエストの登録に失敗しました');
    }
  } catch (error) {
    console.error('Request error:', error);
    alert(error.response?.data?.message || 'リクエストの登録に失敗しました');
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

// リクエストを承認
const approveRequest = async (request) => {
  if (!confirm(`「${request.title}」のリクエストを承認しますか？`)) {
    return;
  }

  try {
    const response = await axios.patch(`/api/book-requests/${request.id}/status`, {
      status: 'approved',
      admin_comment: '承認されました。'
    });
    
    if (response.data) {
      alert('リクエストを承認しました');
      await loadRequests(); // リスト更新
    }
  } catch (error) {
    console.error('Approve request error:', error);
    alert(error.response?.data?.message || '承認に失敗しました');
  }
};

// リクエストを却下
const rejectRequest = async (request) => {
  const reason = prompt(`「${request.title}」のリクエストを却下します。理由を入力してください（任意）:`, '');
  if (reason === null) {
    return; // キャンセルされた場合
  }

  try {
    const response = await axios.patch(`/api/book-requests/${request.id}/status`, {
      status: 'rejected',
      admin_comment: reason || '却下されました。'
    });
    
    if (response.data) {
      alert('リクエストを却下しました');
      await loadRequests(); // リスト更新
    }
  } catch (error) {
    console.error('Reject request error:', error);
    alert(error.response?.data?.message || '却下に失敗しました');
  }
};

// リクエストを削除
const deleteRequest = async (request) => {
  if (!confirm(`「${request.title}」のリクエストを削除しますか？この操作は取り消せません。`)) {
    return;
  }

  try {
    const response = await axios.delete(`/api/book-requests/${request.id}`);
    
    if (response.data.success) {
      alert('リクエストを削除しました');
      await loadRequests(); // リスト更新
    }
  } catch (error) {
    console.error('Delete request error:', error);
    alert(error.response?.data?.message || '削除に失敗しました');
  }
};

// 日付を日本語フォーマットに変換
const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日 ${d.getHours()}:${String(d.getMinutes()).padStart(2, '0')}`;
};

// コンポーネントがマウントされたときにリクエスト一覧を読み込む
onMounted(async () => {
  loadPermissions();
  // 管理者のみ履歴を読み込む
  if (userPermissions.value.canViewBookRequestHistory) {
    await loadRequests();
  }
});
</script>
