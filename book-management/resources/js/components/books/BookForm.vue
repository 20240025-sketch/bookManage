<template>
  <form @submit.prevent="$emit('submit')" class="space-y-6">
    <!-- 基本情報 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- タイトル -->
      <div class="md:col-span-2">
        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
          タイトル <span class="text-red-500">*</span>
        </label>
        <input
          id="title"
          v-model="form.title"
          type="text"
          required
          class="form-input"
          :class="{ 'border-red-500': errors.title }"
          placeholder="書籍のタイトルを入力してください"
        >
        <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title[0] }}</p>
      </div>

      <!-- タイトルのヨミ -->
      <div class="md:col-span-2">
        <label for="title_transcription" class="block text-sm font-medium text-gray-700 mb-2">
          タイトルのヨミ
        </label>
        <input
          id="title_transcription"
          v-model="form.title_transcription"
          type="text"
          class="form-input"
          placeholder="タイトルのヨミ（任意）"
        >
      </div>

      <!-- 著者 -->
      <div>
        <label for="author" class="block text-sm font-medium text-gray-700 mb-2">
          著者
        </label>
        <input
          id="author"
          v-model="form.author"
          type="text"
          class="form-input"
          :class="{ 'border-red-500': errors.author }"
          placeholder="著者名を入力してください（任意）"
        >
        <p v-if="errors.author" class="mt-1 text-sm text-red-600">{{ errors.author[0] }}</p>
      </div>

      <!-- 出版社 -->
      <div>
        <label for="publisher" class="block text-sm font-medium text-gray-700 mb-2">
          出版社
        </label>
        <input
          id="publisher"
          v-model="form.publisher"
          type="text"
          class="form-input"
          placeholder="出版社名"
        >
      </div>

      <!-- 出版日 -->
      <div>
        <label for="published_date" class="block text-sm font-medium text-gray-700 mb-2">
          出版日
        </label>
        <input
          id="published_date"
          v-model="form.published_date"
          type="date"
          class="form-input"
        >
      </div>

      <!-- ISBN -->
      <div v-if="!noIsbnMode">
        <label for="isbn" class="block text-sm font-medium text-gray-700 mb-2">
          ISBN
        </label>
        <div class="relative">
          <input
            id="isbn"
            v-model="form.isbn"
            type="text"
            class="form-input pr-10"
            :class="{ 'border-blue-500': isbnSearching }"
            placeholder="ISBN-13形式（978-）でISBN検索可能"
            @blur="$emit('isbn-blur', form.isbn)"
          >
          <div v-if="isbnSearching" class="absolute inset-y-0 right-0 pr-3 flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
        </div>
        <!-- ISBN重複警告（目立つ表示） -->
        <div v-if="isbnDuplicate" class="mt-2 bg-yellow-100 border-2 border-yellow-500 rounded-lg p-3 animate-pulse">
          <div class="flex items-center">
            <svg class="w-6 h-6 text-yellow-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <div class="flex-1">
              <p class="text-sm font-bold text-yellow-900">
                ⚠️ すでに登録されています
              </p>
              <p class="text-xs text-yellow-800 mt-1">
                {{ isbnSearchMessage }}
              </p>
            </div>
          </div>
        </div>
        
        <!-- 通常のメッセージ -->
        <p v-else-if="isbnSearchMessage" class="mt-1 text-sm" 
           :class="isbnSearchSuccess ? 'text-green-600' : 'text-red-600'">
          {{ isbnSearchMessage }}
        </p>
      </div>
      
      <!-- ISBNなしモード時のメッセージ -->
      <div v-if="noIsbnMode" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center">
          <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
          </svg>
          <p class="text-sm text-blue-800">
            ISBNコードがない本として登録します。書籍情報は手動で入力してください。
          </p>
        </div>
      </div>

      <!-- ページ数 -->
      <div>
        <label for="pages" class="block text-sm font-medium text-gray-700 mb-2">
          ページ数
        </label>
        <input
          id="pages"
          v-model.number="form.pages"
          type="number"
          min="1"
          class="form-input"
          placeholder="ページ数"
        >
      </div>

      <!-- 価格 -->
      <div>
        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
          価格
        </label>
        <input
          id="price"
          v-model.number="form.price"
          type="number"
          min="0"
          step="1"
          class="form-input"
          placeholder="価格（円）"
        >
      </div>

      <!-- 日本十進分類法 -->
      <div>
        <label for="ndc" class="block text-sm font-medium text-gray-700 mb-2">
          NDC分類
        </label>
        <input
          id="ndc"
          v-model="form.ndc"
          type="text"
          class="form-input"
          placeholder="例: 410"
        >
      </div>

      <!-- 冊数 -->
      <div>
        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
          冊数 <span class="text-red-500">*</span>
        </label>
        <input
          id="quantity"
          v-model.number="form.quantity"
          type="number"
          min="1"
          required
          class="form-input"
          :class="{ 'border-red-500': errors.quantity }"
          placeholder="蔵書冊数"
        >
        <p v-if="errors.quantity" class="mt-1 text-sm text-red-600">{{ errors.quantity[0] }}</p>
        <p class="mt-1 text-sm text-gray-500">同じ本の所蔵冊数を入力してください</p>
      </div>
    </div>

    <!-- 受け入れ・廃棄情報セクション -->
    <div class="border-t border-gray-200 pt-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">受け入れ・廃棄情報</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 受け入れ日 -->
        <div>
          <label for="acceptance_date" class="block text-sm font-medium text-gray-700 mb-2">
            受け入れ日
          </label>
          <input
            id="acceptance_date"
            v-model="form.acceptance_date"
            type="date"
            class="form-input"
          >
          <p v-if="errors.acceptance_date" class="mt-1 text-sm text-red-600">{{ errors.acceptance_date[0] }}</p>
        </div>

        <!-- 受け入れ種別 -->
        <div>
          <label for="acceptance_type" class="block text-sm font-medium text-gray-700 mb-2">
            受け入れ種別
          </label>
          <select
            id="acceptance_type"
            v-model="form.acceptance_type"
            class="form-select"
          >
            <option value="">選択してください</option>
            <option value="購入">購入</option>
            <option value="寄贈">寄贈</option>
            <option value="交換">交換</option>
            <option value="移管">移管</option>
            <option value="その他">その他</option>
          </select>
          <p v-if="errors.acceptance_type" class="mt-1 text-sm text-red-600">{{ errors.acceptance_type[0] }}</p>
        </div>

        <!-- 受け入れ元 -->
        <div>
          <label for="acceptance_source" class="block text-sm font-medium text-gray-700 mb-2">
            受け入れ元
            <span class="text-sm font-normal text-gray-500">（選択または自由入力）</span>
          </label>
          
          <!-- 選択モードと入力モードの切り替え -->
          <div class="space-y-2">
            <div class="flex items-center space-x-2 mb-2">
              <label class="inline-flex items-center">
                <input 
                  type="radio" 
                  v-model="acceptanceSourceMode" 
                  value="select"
                  class="form-radio h-4 w-4 text-blue-600"
                >
                <span class="ml-2 text-sm text-gray-700">候補から選択</span>
              </label>
              <label class="inline-flex items-center">
                <input 
                  type="radio" 
                  v-model="acceptanceSourceMode" 
                  value="input"
                  class="form-radio h-4 w-4 text-blue-600"
                >
                <span class="ml-2 text-sm text-gray-700">自由入力</span>
              </label>
            </div>
            
            <!-- 選択モード -->
            <select
              v-if="acceptanceSourceMode === 'select'"
              id="acceptance_source_select"
              v-model="form.acceptance_source"
              class="form-select"
              @focus="loadAcceptanceSources"
            >
              <option value="">選択してください</option>
              <option 
                v-for="source in acceptanceSources" 
                :key="source" 
                :value="source"
              >
                {{ source }}
              </option>
            </select>
            
            <!-- 入力モード -->
            <input
              v-else
              id="acceptance_source"
              v-model="form.acceptance_source"
              type="text"
              placeholder="受け入れ元を入力してください"
              class="form-input"
            />
          </div>
          
          <p v-if="sourcesLoading" class="mt-1 text-xs text-blue-600">
            候補を読み込み中...
          </p>
          <p class="mt-1 text-xs text-gray-500">
            💡 候補から選択するか、自由入力を選んでください（{{ acceptanceSources.length }}件の候補）
          </p>
          <p v-if="errors.acceptance_source" class="mt-1 text-sm text-red-600">{{ errors.acceptance_source[0] }}</p>
        </div>

        <!-- 保管場所 -->
        <div>
          <label for="storage_location" class="block text-sm font-medium text-gray-700 mb-2">
            保管場所
            <span class="text-sm font-normal text-gray-500">（選択または自由入力）</span>
          </label>
          
          <!-- 選択モードと入力モードの切り替え -->
          <div class="space-y-2">
            <div class="flex items-center space-x-2 mb-2">
              <label class="inline-flex items-center">
                <input 
                  type="radio" 
                  v-model="storageLocationMode" 
                  value="select"
                  class="form-radio h-4 w-4 text-blue-600"
                >
                <span class="ml-2 text-sm text-gray-700">候補から選択</span>
              </label>
              <label class="inline-flex items-center">
                <input 
                  type="radio" 
                  v-model="storageLocationMode" 
                  value="input"
                  class="form-radio h-4 w-4 text-blue-600"
                >
                <span class="ml-2 text-sm text-gray-700">自由入力</span>
              </label>
            </div>
            
            <!-- 選択モード -->
            <select
              v-if="storageLocationMode === 'select'"
              id="storage_location_select"
              v-model="form.storage_location"
              class="form-select"
            >
              <option value="">選択してください</option>
              <option value="職員室">職員室</option>
              <option value="図書室">図書室</option>
              <option value="生物室">生物室</option>
            </select>
            
            <!-- 入力モード -->
            <input
              v-else
              id="storage_location"
              v-model="form.storage_location"
              type="text"
              placeholder="保管場所を入力してください"
              class="form-input"
            />
          </div>
          
          <p class="mt-1 text-xs text-gray-500">
            💡 候補から選択するか、自由入力を選んでください
          </p>
          <p v-if="errors.storage_location" class="mt-1 text-sm text-red-600">{{ errors.storage_location[0] }}</p>
        </div>

        <!-- 廃棄情報 -->
        <div>
          <label for="discard" class="block text-sm font-medium text-gray-700 mb-2">
            廃棄情報
          </label>
          <select
            id="discard"
            v-model="form.discard"
            class="form-select"
          >
            <option value="">廃棄予定なし</option>
            <option value="廃棄予定">廃棄予定</option>
            <option value="廃棄済み">廃棄済み</option>
            <option value="譲渡予定">譲渡予定</option>
            <option value="譲渡済み">譲渡済み</option>
          </select>
          <p v-if="errors.discard" class="mt-1 text-sm text-red-600">{{ errors.discard[0] }}</p>
        </div>
      </div>
    </div>

    <!-- フォームアクション -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="flex items-center space-x-4">
        <button
          type="button"
          @click="$emit('reset')"
          class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md"
        >
          リセット
        </button>
      </div>
      
      <div class="flex items-center space-x-4">
        <router-link
          to="/books"
          class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md"
        >
          キャンセル
        </router-link>
        <button
          type="submit"
          :disabled="loading"
          class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md disabled:opacity-50"
        >
          {{ submitLabel || (loading ? '保存中...' : '保存') }}
        </button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  },
  submitLabel: {
    type: String,
    default: ''
  },
  isbnSearching: {
    type: Boolean,
    default: false
  },
  isbnSearchMessage: {
    type: String,
    default: ''
  },
  isbnSearchSuccess: {
    type: Boolean,
    default: false
  },
  isbnDuplicate: {
    type: Boolean,
    default: false
  },
  noIsbnMode: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['submit', 'reset', 'isbn-blur']);

// 受け入れ元候補の管理
const acceptanceSources = ref([]);
const sourcesLoading = ref(false);
const sourcesLoaded = ref(false);
const acceptanceSourceMode = ref('select');
const storageLocationMode = ref('select');

// デフォルトの候補（APIが利用できない場合のフォールバック）
const defaultAcceptanceSources = [
  'Amazon',
  'TSUTAYA', 
  '楽天ブックス',
  '紀伊國屋書店',
  '丸善ジュンク堂書店',
  '文英堂',
  '寄贈',
  '図書館間相互貸借',
  '学校間交換',
  '保護者寄付',
  'その他'
];

// 受け入れ元候補を読み込み
const loadAcceptanceSources = async () => {
  if (sourcesLoaded.value || sourcesLoading.value) {
    return; // 既に読み込み済みまたは読み込み中の場合はスキップ
  }
  
  try {
    sourcesLoading.value = true;
    const response = await axios.get('/api/books/acceptance-sources');
    
    if (response.data && response.data.sources) {
      acceptanceSources.value = response.data.sources;
      console.log('受け入れ元候補を読み込みました:', response.data.sources.length + '件');
      console.log('Loaded acceptance sources:', response.data.sources);
    } else {
      acceptanceSources.value = [...defaultAcceptanceSources];
      console.log('Using default acceptance sources (no API data):', acceptanceSources.value);
    }
    
    sourcesLoaded.value = true;
  } catch (error) {
    console.warn('受け入れ元候補の読み込みに失敗しました、デフォルト候補を使用します:', error);
    acceptanceSources.value = [...defaultAcceptanceSources];
    console.log('Using default acceptance sources:', acceptanceSources.value);
  } finally {
    sourcesLoading.value = false;
  }
};

// 初回ロード時にデフォルト候補をセット
onMounted(() => {
  acceptanceSources.value = [...defaultAcceptanceSources];
  console.log('BookForm mounted, acceptance sources initialized:', acceptanceSources.value.length);
});
</script>
