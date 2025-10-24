<template>
  <div class="max-w-4xl mx-auto">
    <!-- ページヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">独自コード書籍登録</h1>
          <p class="mt-1 text-sm text-gray-600">
            ISBNコードのない書籍用に独自JANコードを生成して登録します
          </p>
        </div>
        <router-link
          to="/books/create"
          class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md"
        >
          ← 通常の書籍登録
        </router-link>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Step 1: JANコード生成 -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Step 1: JANコード生成</h2>
        
        <div v-if="!generatedJanCode" class="text-center">
          <div class="mb-4">
            <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
          </div>
          <p class="text-sm text-gray-600 mb-4">
            独自JANコードを生成します<br>
            形式: 938525 + 6桁連番 + チェックディジット
          </p>
          <button
            @click="generateJanCode"
            :disabled="generatingJanCode"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-4 py-2 rounded-md transition-colors"
          >
            {{ generatingJanCode ? 'コード生成中...' : 'JANコード生成' }}
          </button>
        </div>

        <div v-else class="text-center">
          <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <h3 class="text-sm font-medium text-green-800 mb-2">生成されたJANコード</h3>
            <div class="text-2xl font-mono font-bold text-green-900">
              {{ generatedJanCode }}
            </div>
          </div>
          
          <!-- バーコード表示エリア -->
          <div class="mb-4 p-4 bg-gray-50 border rounded-lg">
            <div class="mb-2">
              <span class="text-sm font-medium text-gray-700">バーコード</span>
            </div>
            <canvas 
              ref="barcodeCanvas" 
              class="mx-auto border"
              style="max-width: 100%; height: auto;"
            ></canvas>
          </div>

          <div class="space-y-2">
            <button
              @click="downloadBarcodePDF"
              :disabled="!generatedJanCode"
              class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md transition-colors"
            >
              📄 バーコードPDFダウンロード
            </button>
            <button
              @click="resetJanCode"
              class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition-colors"
            >
              🔄 新しいコードを生成
            </button>
          </div>
        </div>
      </div>

      <!-- Step 2: 書籍情報入力 -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Step 2: 書籍情報入力</h2>
        
        <form @submit.prevent="submitBook" class="space-y-4">
          <!-- タイトル -->
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
              タイトル <span class="text-red-500">*</span>
            </label>
            <input
              id="title"
              v-model="bookForm.title"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': errors.title }"
              placeholder="書籍のタイトルを入力"
            >
            <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title[0] }}</p>
          </div>

          <!-- 著者 -->
          <div>
            <label for="author" class="block text-sm font-medium text-gray-700 mb-1">
              著者
            </label>
            <input
              id="author"
              v-model="bookForm.author"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="著者名を入力"
            >
          </div>

          <!-- 出版社 -->
          <div>
            <label for="publisher" class="block text-sm font-medium text-gray-700 mb-1">
              出版社
            </label>
            <input
              id="publisher"
              v-model="bookForm.publisher"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="出版社名を入力"
            >
          </div>

          <!-- 出版日 -->
          <div>
            <label for="published_date" class="block text-sm font-medium text-gray-700 mb-1">
              出版日
            </label>
            <input
              id="published_date"
              v-model="bookForm.published_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
          </div>

          <!-- ページ数 -->
          <div>
            <label for="pages" class="block text-sm font-medium text-gray-700 mb-1">
              ページ数
            </label>
            <input
              id="pages"
              v-model="bookForm.pages"
              type="number"
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="ページ数を入力"
            >
          </div>

          <!-- 価格 -->
          <div>
            <label for="price" class="block text-sm font-medium text-gray-700 mb-1">
              価格（円）
            </label>
            <input
              id="price"
              v-model="bookForm.price"
              type="number"
              min="0"
              step="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="価格を入力"
            >
          </div>

          <!-- NDC分類 -->
          <div>
            <label for="ndc" class="block text-sm font-medium text-gray-700 mb-1">
              NDC分類
            </label>
            <input
              id="ndc"
              v-model="bookForm.ndc"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="NDC分類コードを入力"
            >
          </div>

          <!-- 冊数 -->
          <div>
            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">
              冊数
            </label>
            <input
              id="quantity"
              v-model="bookForm.quantity"
              type="number"
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="冊数を入力"
            >
          </div>

          <!-- 受け入れ日 -->
          <div>
            <label for="acceptance_date" class="block text-sm font-medium text-gray-700 mb-1">
              受け入れ日
            </label>
            <input
              id="acceptance_date"
              v-model="bookForm.acceptance_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
          </div>

          <!-- 受け入れ種別 -->
          <div>
            <label for="acceptance_type" class="block text-sm font-medium text-gray-700 mb-1">
              受け入れ種別
            </label>
            <select
              id="acceptance_type"
              v-model="bookForm.acceptance_type"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">選択してください</option>
              <option value="購入">購入</option>
              <option value="寄贈">寄贈</option>
              <option value="移管">移管</option>
            </select>
          </div>

          <!-- 受け入れ元 -->
          <div>
            <label for="acceptance_source" class="block text-sm font-medium text-gray-700 mb-1">
              受け入れ元
            </label>
            <input
              id="acceptance_source"
              v-model="bookForm.acceptance_source"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="受け入れ元を入力"
            >
          </div>

          <!-- 送信ボタン -->
          <button
            type="submit"
            :disabled="!generatedJanCode || submitting"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-3 rounded-md font-medium transition-colors"
          >
            {{ submitting ? '登録中...' : '📚 書籍を登録' }}
          </button>
        </form>

        <!-- エラー表示 -->
        <div v-if="submitError" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
          <div class="text-sm text-red-600">
            {{ submitError }}
          </div>
        </div>

        <!-- 成功表示 -->
        <div v-if="submitSuccess" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
          <div class="text-sm text-green-600">
            書籍が正常に登録されました！
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

// 状態管理
const generatingJanCode = ref(false);
const generatedJanCode = ref('');
const barcodeCanvas = ref(null);
const submitting = ref(false);
const submitError = ref('');
const submitSuccess = ref(false);
const errors = ref({});

// 権限管理
const userPermissions = ref({
  isAdmin: false
});

// 権限情報をローカルストレージから読み込み
const loadPermissions = () => {
  try {
    const stored = localStorage.getItem('userPermissions')
    if (stored) {
      userPermissions.value = { ...userPermissions.value, ...JSON.parse(stored) }
    }
  } catch (error) {
    console.error('権限情報の読み込みに失敗:', error)
  }
}

// 書籍フォームデータ
const bookForm = reactive({
  title: '',
  author: '',
  publisher: '',
  published_date: '',
  pages: null,
  price: null,
  ndc: '',
  quantity: 1,
  acceptance_date: '',
  acceptance_type: '購入',
  acceptance_source: '文栄堂',
  storage_location: '図書室'
});

// JANコード生成
const generateJanCode = async () => {
  generatingJanCode.value = true;
  try {
    const response = await axios.post('/api/generate-jan-code');
    generatedJanCode.value = response.data.jan_code;
    
    // バーコード生成
    await nextTick();
    generateBarcode(generatedJanCode.value);
    
  } catch (error) {
    console.error('JANコード生成エラー:', error);
    alert('JANコードの生成に失敗しました');
  } finally {
    generatingJanCode.value = false;
  }
};

// バーコード生成
const generateBarcode = (code) => {
  if (!barcodeCanvas.value) return;
  
  try {
    // JsBarcode を使用（CDNから読み込む予定）
    if (typeof JsBarcode !== 'undefined') {
      JsBarcode(barcodeCanvas.value, code, {
        format: "EAN13",
        width: 2,
        height: 100,
        displayValue: true,
        fontSize: 14,
        margin: 10
      });
    } else {
      // フォールバック: シンプルなテキスト表示
      const ctx = barcodeCanvas.value.getContext('2d');
      ctx.clearRect(0, 0, barcodeCanvas.value.width, barcodeCanvas.value.height);
      ctx.font = '16px monospace';
      ctx.fillText(code, 10, 30);
    }
  } catch (error) {
    console.error('バーコード生成エラー:', error);
  }
};

// バーコードPDFダウンロード
const downloadBarcodePDF = async () => {
  if (!generatedJanCode.value) return;
  
  try {
    const response = await axios.post('/api/generate-barcode-pdf', {
      jan_code: generatedJanCode.value
    }, {
      responseType: 'blob'
    });
    
    // PDFファイルをダウンロード
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `barcode_${generatedJanCode.value}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
  } catch (error) {
    console.error('PDF生成エラー:', error);
    alert('PDFの生成に失敗しました');
  }
};

// JANコードリセット
const resetJanCode = () => {
  generatedJanCode.value = '';
  if (barcodeCanvas.value) {
    const ctx = barcodeCanvas.value.getContext('2d');
    ctx.clearRect(0, 0, barcodeCanvas.value.width, barcodeCanvas.value.height);
  }
};

// 書籍登録
const submitBook = async () => {
  if (!generatedJanCode.value) {
    alert('まずJANコードを生成してください');
    return;
  }
  
  submitting.value = true;
  submitError.value = '';
  submitSuccess.value = false;
  errors.value = {};
  
  try {
    const formData = {
      ...bookForm,
      isbn: generatedJanCode.value, // 生成したJANコードをISBNフィールドに設定
      jan_code: generatedJanCode.value
    };
    
    const response = await axios.post('/api/books', formData);
    
    submitSuccess.value = true;
    
    // 登録成功後、保持するフィールドの値を保存
    const preservedFields = {
      acceptance_date: bookForm.acceptance_date,
      acceptance_type: bookForm.acceptance_type,
      acceptance_source: bookForm.acceptance_source,
      storage_location: bookForm.storage_location
    };
    
    // フォームをリセット（保持フィールド以外をクリア）
    Object.keys(bookForm).forEach(key => {
      if (key in preservedFields) {
        // 保持するフィールドはそのまま
        bookForm[key] = preservedFields[key];
      } else if (key === 'pages' || key === 'price') {
        bookForm[key] = null;
      } else if (key === 'quantity') {
        bookForm[key] = 1; // 冊数のデフォルト値
      } else {
        bookForm[key] = '';
      }
    });
    
    // JANコードをリセット
    resetJanCode();
    
    // 成功メッセージを表示
    alert('書籍が正常に登録されました。続けて登録できます。');
    
    // submitSuccessを数秒後にリセット
    setTimeout(() => {
      submitSuccess.value = false;
    }, 3000);
    
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {};
    } else {
      submitError.value = error.response?.data?.message || '書籍の登録に失敗しました';
    }
  } finally {
    submitting.value = false;
  }
};

// ページマウント時の初期化
onMounted(() => {
  loadPermissions();
  
  // メールアドレスが数字で始まるかチェック（管理者以外の利用者の場合）
  if (!userPermissions.value.isAdmin) {
    const student = JSON.parse(localStorage.getItem('student') || '{}')
    const email = student.email || ''
    
    // メールアドレスが数字以外で始まる場合、アクセス拒否
    if (!/^[0-9]/.test(email)) {
      submitError.value = 'この機能にアクセスする権限がありません'
      return
    }
  }
  
  // 今日の日付をデフォルト値に設定
  const today = new Date().toISOString().split('T')[0];
  bookForm.acceptance_date = today;
});
</script>

<style scoped>
.form-input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}
</style>