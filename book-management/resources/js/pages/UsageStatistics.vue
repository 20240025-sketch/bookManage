<template>
  <div class="container mx-auto px-4 pb-8">
    <!-- ヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">利用状況</h1>
          <p class="mt-1 text-sm text-gray-600">
            図書館の利用統計とトレンドを確認できます
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="exportPdf"
            :disabled="loading || !chartRendered"
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
      <!-- 人気書籍セクション -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- 年間で一番借りられた本 -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
            </svg>
            年間で最も借りられた本
          </h2>
          <div v-if="statistics.top_book_yearly">
            <div class="font-medium text-gray-900 text-lg mb-2">
              {{ statistics.top_book_yearly.book.title }}
            </div>
            <div class="text-sm text-gray-600 mb-1">
              著者: {{ statistics.top_book_yearly.book.author }}
            </div>
            <div class="text-sm font-semibold text-blue-600">
              貸出回数: {{ statistics.top_book_yearly.borrow_count }}回
            </div>
          </div>
          <div v-else class="text-gray-500">
            データがありません
          </div>
        </div>

        <!-- 月間で一番借りられた本 -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
            </svg>
            月間で最も借りられた本
          </h2>
          <div v-if="statistics.top_book_monthly">
            <div class="font-medium text-gray-900 text-lg mb-2">
              {{ statistics.top_book_monthly.book.title }}
            </div>
            <div class="text-sm text-gray-600 mb-1">
              著者: {{ statistics.top_book_monthly.book.author }}
            </div>
            <div class="text-sm font-semibold text-blue-600">
              貸出回数: {{ statistics.top_book_monthly.borrow_count }}回
            </div>
          </div>
          <div v-else class="text-gray-500">
            データがありません
          </div>
        </div>
      </div>

      <!-- 統計情報カード -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- 本日 -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">本日</dt>
                <dd class="text-lg font-semibold text-gray-900">
                  貸出: {{ statistics.daily_stats.borrow_count }}件
                </dd>
                <dd class="text-sm text-gray-600">
                  利用者: {{ statistics.daily_stats.user_count }}人
                </dd>
              </dl>
            </div>
          </div>
        </div>

        <!-- 今月 -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">今月</dt>
                <dd class="text-lg font-semibold text-gray-900">
                  貸出: {{ statistics.monthly_stats.borrow_count }}件
                </dd>
                <dd class="text-sm text-gray-600">
                  利用者: {{ statistics.monthly_stats.user_count }}人
                </dd>
              </dl>
            </div>
          </div>
        </div>

        <!-- 今年 -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">今年</dt>
                <dd class="text-lg font-semibold text-gray-900">
                  貸出: {{ statistics.yearly_stats.borrow_count }}件
                </dd>
                <dd class="text-sm text-gray-600">
                  利用者: {{ statistics.yearly_stats.user_count }}人
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- グラフ設定 -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">推移グラフ設定</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="period" class="block text-sm font-medium text-gray-700 mb-1">
              期間
            </label>
            <select
              id="period"
              v-model="chartSettings.period"
              @change="updateChart"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="daily">日間（過去30日）</option>
              <option value="monthly">月間（過去12ヶ月）</option>
              <option value="yearly">年間（過去5年）</option>
            </select>
          </div>
          <div>
            <label for="dataType" class="block text-sm font-medium text-gray-700 mb-1">
              表示データ
            </label>
            <select
              id="dataType"
              v-model="chartSettings.dataType"
              @change="updateChart"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="borrow_count">貸出件数のみ</option>
              <option value="user_count">利用者数のみ</option>
              <option value="both">両方（混合）</option>
            </select>
          </div>
        </div>
      </div>

      <!-- グラフ表示 -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">利用推移グラフ</h2>
        <div class="relative" style="height: 400px;">
          <canvas ref="chartCanvas"></canvas>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const loading = ref(true);
const error = ref('');
const chartRendered = ref(false);

const statistics = ref({
  top_book_yearly: null,
  top_book_monthly: null,
  daily_stats: { borrow_count: 0, user_count: 0 },
  monthly_stats: { borrow_count: 0, user_count: 0 },
  yearly_stats: { borrow_count: 0, user_count: 0 }
});

const chartSettings = ref({
  period: 'monthly',
  dataType: 'both'
});

const chartCanvas = ref(null);
let chartInstance = null;

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

// 統計データを取得
const loadStatistics = async () => {
  try {
    loading.value = true;
    error.value = '';
    
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    axios.defaults.withCredentials = true;
    
    const params = {
      current_user_email: currentStudent.email
    };
    
    const response = await axios.get('/api/usage-statistics', { params });
    
    if (response.data.success) {
      statistics.value = response.data.data;
    }
  } catch (err) {
    console.error('Error loading statistics:', err);
    if (err.response?.status === 403) {
      error.value = 'この機能は管理者のみ利用できます。';
    } else {
      error.value = err.response?.data?.message || '利用状況の取得に失敗しました';
    }
  } finally {
    loading.value = false;
  }
};

// グラフデータを取得して更新
const updateChart = async () => {
  try {
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    const params = {
      current_user_email: currentStudent.email,
      period: chartSettings.value.period,
      data_type: chartSettings.value.dataType
    };
    
    const response = await axios.get('/api/usage-statistics/chart', { params });
    
    if (response.data.success) {
      renderChart(response.data.data);
    }
  } catch (err) {
    console.error('Error loading chart data:', err);
    error.value = 'グラフデータの取得に失敗しました';
  }
};

// グラフを描画
const renderChart = (data) => {
  if (!chartCanvas.value) return;
  
  // 既存のチャートを破棄
  if (chartInstance) {
    chartInstance.destroy();
  }
  
  const ctx = chartCanvas.value.getContext('2d');
  
  // 混合表示の場合、利用者数を棒グラフにするためデータセットを調整
  const datasets = data.datasets.map(dataset => {
    if (chartSettings.value.dataType === 'both' && dataset.label === '利用者数') {
      return {
        ...dataset,
        type: 'bar', // 利用者数は棒グラフ
        backgroundColor: 'rgba(16, 185, 129, 0.5)',
        borderColor: 'rgb(16, 185, 129)',
        borderWidth: 1
      };
    }
    return {
      ...dataset,
      type: chartSettings.value.dataType === 'both' ? 'line' : undefined // 貸出件数は折れ線グラフ
    };
  });
  
  chartInstance = new Chart(ctx, {
    type: chartSettings.value.dataType === 'both' ? 'line' : 'line', // 混合の場合はベースをline
    data: {
      labels: data.labels,
      datasets: datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top',
        },
        tooltip: {
          mode: 'index',
          intersect: false,
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          },
          title: {
            display: true,
            text: '人数'
          }
        },
        x: {
          title: {
            display: true,
            text: chartSettings.value.period === 'daily' ? '日付' : 
                  chartSettings.value.period === 'monthly' ? '月' : '年'
          }
        }
      },
      interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
      }
    }
  });
  
  chartRendered.value = true;
};

// PDF出力
const exportPdf = async () => {
  try {
    if (!chartInstance || !chartRendered.value) {
      error.value = 'グラフが描画されていません';
      return;
    }
    
    loading.value = true;
    error.value = '';
    
    // グラフをBase64画像に変換
    const chartImage = chartInstance.toBase64Image();
    
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    const params = {
      current_user_email: currentStudent.email,
      period: chartSettings.value.period,
      data_type: chartSettings.value.dataType,
      chart_image: chartImage
    };
    
    const response = await axios.post('/api/usage-statistics/pdf', params, {
      responseType: 'blob'
    });
    
    // PDFをダウンロード
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `利用状況_${new Date().toLocaleDateString('ja-JP').replace(/\//g, '')}.pdf`;
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

onMounted(async () => {
  loadPermissions();
  await loadStatistics();
  await nextTick();
  await updateChart();
});

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy();
  }
});
</script>
