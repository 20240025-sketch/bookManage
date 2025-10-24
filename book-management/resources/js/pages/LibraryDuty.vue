<template>
  <div class="container mx-auto px-4 pb-8">
    <!-- ヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">図書当番</h1>
          <p class="mt-1 text-sm text-gray-600">
            図書当番の記録を管理します（昼休み・放課後）
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="exportPdf"
            :disabled="loading"
            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md flex items-center space-x-2 disabled:opacity-50"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span>PDF出力</span>
          </button>
        </div>
      </div>
    </div>

    <!-- エラーメッセージ -->
    <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">エラーが発生しました</h3>
          <div class="mt-2 text-sm text-red-700">
            {{ error }}
          </div>
        </div>
      </div>
    </div>

    <!-- 成功メッセージ -->
    <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
      <div class="flex">
        <div class="ml-3">
          <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
        </div>
      </div>
    </div>

    <!-- 読み込み中 -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
      <p class="mt-2 text-gray-600">読み込み中...</p>
    </div>

    <template v-else>
      <!-- 本日の記録カード -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          本日の記録（{{ formatDate(todayDuty.duty_date) }}）
        </h2>
        
        <!-- タブ切り替え -->
        <div class="mb-6 border-b border-gray-200">
          <nav class="-mb-px flex space-x-8">
            <button
              @click="currentShiftType = 'lunch'"
              :class="[
                currentShiftType === 'lunch'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              🍱 昼休み
            </button>
            <button
              @click="currentShiftType = 'after_school'"
              :class="[
                currentShiftType === 'after_school'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              🌆 放課後
            </button>
          </nav>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <!-- 利用者数入力 -->
          <div>
            <label for="visitorCount" class="block text-sm font-medium text-gray-700 mb-1">
              利用者数 *
            </label>
            <input
              id="visitorCount"
              v-model.number="todayDuty.visitor_count"
              type="number"
              min="0"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="0"
            />
          </div>
          
          <!-- 貸出人数表示 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              貸出人数（自動計算）
            </label>
            <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-900">
              {{ todayDuty.borrow_count }}人
            </div>
          </div>
        </div>
        
        <!-- 担当者入力 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <!-- 担当者1 -->
          <div>
            <label for="student1" class="block text-sm font-medium text-gray-700 mb-1">
              担当者1
            </label>
            <input
              id="student1"
              v-model="todayDuty.student_name_1"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="担当者名を入力"
            />
          </div>
          
          <!-- 担当者2 -->
          <div>
            <label for="student2" class="block text-sm font-medium text-gray-700 mb-1">
              担当者2
            </label>
            <input
              id="student2"
              v-model="todayDuty.student_name_2"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="担当者名を入力"
            />
          </div>
        </div>
        
        <!-- ふりかえり -->
        <div class="mb-4">
          <label for="reflection" class="block text-sm font-medium text-gray-700 mb-1">
            ふりかえり
          </label>
          <textarea
            id="reflection"
            v-model="todayDuty.reflection"
            rows="4"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="今日の振り返りを入力してください..."
          ></textarea>
        </div>
        
        <!-- 保存ボタン -->
        <div class="flex justify-end">
          <button
            @click="saveTodayDuty"
            :disabled="saving"
            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md disabled:opacity-50"
          >
            {{ saving ? '保存中...' : '保存' }}
          </button>
        </div>
      </div>

      <!-- 過去の記録 -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">過去の記録</h2>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  日付
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  時間帯
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  利用者数
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  貸出人数
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  担当者
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  ふりかえり
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="duty in pastDuties" :key="duty.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatDate(duty.duty_date) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span 
                    :class="[
                      'px-2 py-1 rounded-full text-xs font-medium',
                      duty.shift_type === 'lunch' 
                        ? 'bg-yellow-100 text-yellow-800' 
                        : 'bg-purple-100 text-purple-800'
                    ]"
                  >
                    {{ duty.shift_type === 'lunch' ? '🍱 昼休み' : '🌆 放課後' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ duty.visitor_count }}人
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ duty.borrow_count }}人
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatStudentNames(duty) }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900 max-w-md">
                  <div class="line-clamp-2">
                    {{ duty.reflection || '-' }}
                  </div>
                </td>
              </tr>
              <tr v-if="pastDuties.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                  過去の記録がありません
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- ページネーション -->
        <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              全{{ pagination.total }}件中 {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }}～{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}件を表示
            </div>
            <div class="flex space-x-2">
              <button
                @click="prevPage"
                :disabled="pagination.current_page === 1"
                class="px-3 py-1 border rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
              >
                前へ
              </button>
              <button
                @click="nextPage"
                :disabled="pagination.current_page === pagination.last_page"
                class="px-3 py-1 border rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
              >
                次へ
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const successMessage = ref('');

// 現在選択されている時間帯
const currentShiftType = ref('lunch'); // 'lunch' または 'after_school'

const todayDuty = ref({
  id: null,
  duty_date: null,
  shift_type: 'lunch',
  visitor_count: 0,
  borrow_count: 0,
  reflection: '',
  student_name_1: '',
  student_name_2: ''
});

const duties = ref([]);

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 30,
  total: 0
});

// 権限管理
const userPermissions = ref({
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

// 本日の記録を除く過去の記録
const pastDuties = computed(() => {
  return duties.value.filter(duty => duty.id !== todayDuty.value.id);
});

// 担当者名のフォーマット（2名まで表示）
const formatStudentNames = (duty) => {
  const names = [];
  if (duty.student_name_1) {
    names.push(duty.student_name_1);
  }
  if (duty.student_name_2) {
    names.push(duty.student_name_2);
  }
  return names.length > 0 ? names.join('、') : '-';
};

// 日付フォーマット
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    weekday: 'short'
  });
};

// 本日の記録を取得
const loadTodayDuty = async (shiftType = 'lunch') => {
  try {
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    axios.defaults.withCredentials = true;
    
    const params = {
      current_user_email: currentStudent.email,
      shift_type: shiftType
    };
    
    const response = await axios.get('/api/library-duty/today', { params });
    
    if (response.data.success) {
      todayDuty.value = response.data.data;
    }
  } catch (err) {
    console.error('Error loading today duty:', err);
    if (err.response?.status === 403) {
      error.value = 'この機能は管理者のみ利用できます。';
    } else {
      error.value = err.response?.data?.message || '本日の記録の取得に失敗しました';
    }
  }
};

// 時間帯が変更されたら本日の記録を再取得
watch(currentShiftType, async (newShiftType) => {
  await loadTodayDuty(newShiftType);
});

// 過去の記録を取得
const loadDuties = async (page = 1) => {
  try {
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    const params = {
      current_user_email: currentStudent.email,
      page: page,
      per_page: pagination.value.per_page
    };
    
    const response = await axios.get('/api/library-duty', { params });
    
    if (response.data.success) {
      duties.value = response.data.data;
      pagination.value = response.data.pagination;
    }
  } catch (err) {
    console.error('Error loading duties:', err);
    error.value = err.response?.data?.message || '記録の取得に失敗しました';
  }
};

// 本日の記録を保存
const saveTodayDuty = async () => {
  try {
    saving.value = true;
    error.value = '';
    successMessage.value = '';
    
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    const data = {
      visitor_count: todayDuty.value.visitor_count,
      reflection: todayDuty.value.reflection,
      student_name_1: todayDuty.value.student_name_1,
      student_name_2: todayDuty.value.student_name_2,
      shift_type: todayDuty.value.shift_type,
      current_user_email: currentStudent.email
    };
    
    const response = await axios.put(`/api/library-duty/${todayDuty.value.id}`, data);
    
    if (response.data.success) {
      todayDuty.value = response.data.data;
      successMessage.value = `保存しました（${todayDuty.value.shift_type === 'lunch' ? '昼休み' : '放課後'}）`;
      
      // 過去の記録も更新
      await loadDuties(pagination.value.current_page);
      
      // 成功メッセージを3秒後に消す
      setTimeout(() => {
        successMessage.value = '';
      }, 3000);
    }
  } catch (err) {
    console.error('Error saving duty:', err);
    error.value = err.response?.data?.message || '保存に失敗しました';
  } finally {
    saving.value = false;
  }
};

// PDF出力
const exportPdf = async () => {
  try {
    loading.value = true;
    error.value = '';
    
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    // 今月の開始日と終了日
    const today = new Date();
    const startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
    const endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
    
    const params = new URLSearchParams({
      current_user_email: currentStudent.email,
      start_date: startDate,
      end_date: endDate
    });
    
    const response = await axios.get(`/api/library-duty/pdf?${params.toString()}`, {
      responseType: 'blob'
    });
    
    // PDFをダウンロード
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `図書当番記録_${new Date().toLocaleDateString('ja-JP').replace(/\//g, '')}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    
  } catch (err) {
    console.error('Error exporting PDF:', err);
    error.value = 'PDF出力に失敗しました';
  } finally {
    loading.value = false;
  }
};

// ページネーション
const nextPage = () => {
  if (pagination.value.current_page < pagination.value.last_page) {
    loadDuties(pagination.value.current_page + 1);
  }
};

const prevPage = () => {
  if (pagination.value.current_page > 1) {
    loadDuties(pagination.value.current_page - 1);
  }
};

onMounted(async () => {
  loadPermissions();
  await loadTodayDuty();
  await loadDuties();
  loading.value = false;
});
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
