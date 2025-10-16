<template>
  <div class="container mx-auto px-4 pb-8">
    <!-- ヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">貸出状況</h1>
          <p class="mt-1 text-sm text-gray-600">
            現在貸出されている本の一覧と滞納状況を確認できます
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

    <!-- 読み込み中 -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
      <p class="mt-2 text-gray-600">読み込み中...</p>
    </div>

    <template v-else>
      <!-- 統計情報 -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">総貸出数</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ statistics.total }}件</dd>
              </dl>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">滞納</dt>
                <dd class="text-lg font-semibold text-red-600">{{ statistics.overdue }}件</dd>
              </dl>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">期限内</dt>
                <dd class="text-lg font-semibold text-green-600">{{ statistics.not_overdue }}件</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- フィルター・検索 -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
              検索
            </label>
            <input
              id="search"
              v-model="filters.search"
              @input="applyFilters"
              type="text"
              placeholder="書籍名、著者名、生徒名、学籍番号"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>
          <div>
            <label for="filterGradeClass" class="block text-sm font-medium text-gray-700 mb-1">
              学年・クラス
            </label>
            <select
              id="filterGradeClass"
              v-model="filters.gradeClass"
              @change="applyFilters"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">すべて</option>
              <option v-for="gradeClass in availableGradeClasses" :key="gradeClass.value" :value="gradeClass.value">
                {{ gradeClass.label }}
              </option>
            </select>
          </div>
          <div>
            <label for="filterStatus" class="block text-sm font-medium text-gray-700 mb-1">
              状況でフィルター
            </label>
            <select
              id="filterStatus"
              v-model="filters.status"
              @change="applyFilters"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">すべて</option>
              <option value="overdue">滞納のみ</option>
              <option value="not_overdue">期限内のみ</option>
            </select>
          </div>
        </div>
      </div>

      <!-- 貸出一覧テーブル -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                書籍名
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                著者
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                借りている人
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                学籍番号
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                学年・クラス
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                貸出日
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                返却期限
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                状況
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="borrow in filteredBorrows" :key="borrow.id" :class="borrow.is_overdue ? 'bg-red-50' : ''">
              <td class="px-6 py-4 text-sm text-gray-900">
                <div class="font-medium">{{ borrow.book.title }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ borrow.book.author }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div class="font-medium">{{ borrow.student.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ borrow.student.student_number }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <span v-if="borrow.student.school_class">
                  {{ borrow.student.school_class.grade }}年{{ borrow.student.school_class.name }}
                </span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(borrow.borrowed_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm" :class="borrow.is_overdue ? 'text-red-600 font-medium' : 'text-gray-500'">
                {{ formatDate(borrow.due_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span v-if="borrow.is_overdue" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                  滞納 {{ borrow.overdue_days }}日
                </span>
                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  期限内
                </span>
              </td>
            </tr>
            <tr v-if="filteredBorrows.length === 0">
              <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                貸出中の本はありません
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const borrows = ref([]);
const loading = ref(true);
const error = ref('');

const filters = ref({
  search: '',
  gradeClass: '',
  status: ''
});

const availableGradeClasses = ref([]);

const statistics = ref({
  total: 0,
  overdue: 0,
  not_overdue: 0
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

// フィルター適用後のデータ
const filteredBorrows = computed(() => {
  return borrows.value;
});

// 日付フォーマット
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });
};

// 貸出状況を取得
const loadBorrowStatus = async () => {
  try {
    loading.value = true;
    error.value = '';
    
    // ローカルストレージから現在ログイン中の生徒情報を取得
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    axios.defaults.withCredentials = true;
    
    const params = {
      current_user_email: currentStudent.email
    };
    
    // フィルター条件を追加
    if (filters.value.search) {
      params.search = filters.value.search;
    }
    
    if (filters.value.gradeClass) {
      const [grade, className] = filters.value.gradeClass.split('-');
      params.grade = grade;
      params.class = className;
    }
    
    if (filters.value.status) {
      params.filter = filters.value.status;
    }
    
    const response = await axios.get('/api/borrow-status', { params });
    
    if (response.data.success) {
      borrows.value = response.data.data;
      statistics.value = response.data.statistics;
    }
  } catch (err) {
    console.error('Error loading borrow status:', err);
    if (err.response?.status === 403) {
      error.value = 'この機能は管理者のみ利用できます。';
    } else {
      error.value = err.response?.data?.message || '貸出状況の取得に失敗しました';
    }
  } finally {
    loading.value = false;
  }
};

// フィルター変更時に再読み込み
const applyFilters = () => {
  loadBorrowStatus();
};

// PDF出力
const exportPdf = async () => {
  try {
    loading.value = true;
    error.value = '';
    
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    const params = new URLSearchParams({
      current_user_email: currentStudent.email
    });
    
    if (filters.value.search) {
      params.append('search', filters.value.search);
    }
    
    if (filters.value.gradeClass) {
      const [grade, className] = filters.value.gradeClass.split('-');
      params.append('grade', grade);
      params.append('class', className);
    }
    
    if (filters.value.status) {
      params.append('filter', filters.value.status);
    }
    
    const response = await axios.get(`/api/borrow-status/pdf?${params.toString()}`, {
      responseType: 'blob'
    });
    
    // PDFをダウンロード
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `貸出状況_${new Date().toLocaleDateString('ja-JP').replace(/\//g, '')}.pdf`;
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

// クラス一覧の取得
const loadGradeClasses = async () => {
  try {
    console.log('Loading grade classes from API...');
    const response = await axios.get('/api/students/classes');
    console.log('Grade Classes API Response:', response.data);
    availableGradeClasses.value = response.data.data;
    console.log('Grade classes loaded:', availableGradeClasses.value.length);
  } catch (err) {
    console.error('Error loading grade classes:', err);
  }
};

onMounted(() => {
  loadPermissions();
  loadGradeClasses();
  loadBorrowStatus();
});
</script>
