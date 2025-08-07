<template>
  <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-6">
    <!-- 書籍情報ヘッダー -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex-1">
        <h3 class="text-lg font-semibold text-gray-900 mb-1 line-clamp-2">
          {{ book.title }}
        </h3>
        <p v-if="book.title_transcription" class="text-sm text-gray-500 mb-2">
          {{ book.title_transcription }}
        </p>
        <p class="text-sm text-gray-600">
          著者: {{ book.author }}
        </p>
      </div>
      
      <!-- 読書ステータスバッジ -->
      <div class="ml-4">
        <span :class="statusBadgeClass">
          {{ book.reading_status_label }}
        </span>
      </div>
    </div>

    <!-- 書籍詳細情報 -->
    <div class="space-y-2 mb-4">
      <div v-if="book.publisher" class="text-sm text-gray-600">
        <span class="font-medium">出版社:</span> {{ book.publisher }}
      </div>
      
      <div v-if="book.published_date" class="text-sm text-gray-600">
        <span class="font-medium">出版日:</span> {{ formatDate(book.published_date) }}
      </div>
      
      <div v-if="book.isbn" class="text-sm text-gray-600">
        <span class="font-medium">ISBN:</span> {{ book.isbn }}
      </div>
      
      <div class="flex space-x-4 text-sm text-gray-600">
        <div v-if="book.pages">
          <span class="font-medium">ページ数:</span> {{ book.pages }}p
        </div>
        <div v-if="book.price">
          <span class="font-medium">価格:</span> ¥{{ Number(book.price).toLocaleString() }}
        </div>
      </div>

      <div v-if="book.ndc" class="text-sm text-gray-600">
        <span class="font-medium">NDC:</span> {{ book.ndc }}
      </div>
    </div>

    <!-- 受け入れ・廃棄情報 -->
    <div v-if="hasAcceptanceInfo" class="border-t border-gray-200 pt-3 mb-4">
      <h4 class="text-sm font-medium text-gray-700 mb-2">受け入れ・廃棄情報</h4>
      
      <div class="space-y-1 text-sm text-gray-600">
        <div v-if="book.acceptance_date" class="flex justify-between">
          <span class="font-medium">受け入れ日:</span>
          <span>{{ formatDate(book.acceptance_date) }}</span>
        </div>
        
        <div v-if="book.acceptance_type" class="flex justify-between">
          <span class="font-medium">受け入れ種別:</span>
          <span>{{ book.acceptance_type }}</span>
        </div>
        
        <div v-if="book.acceptance_source" class="flex justify-between">
          <span class="font-medium">受け入れ元:</span>
          <span>{{ book.acceptance_source }}</span>
        </div>
        
        <div v-if="book.discard" class="flex justify-between">
          <span class="font-medium">廃棄情報:</span>
          <span :class="discardStatusClass">{{ book.discard }}</span>
        </div>
      </div>
    </div>

    <!-- アクションボタン -->
    <div class="flex items-center justify-between pt-3 border-t border-gray-200">
      <div class="text-xs text-gray-500">
        登録: {{ formatDate(book.created_at) }}
      </div>
      
      <div class="flex space-x-2">
        <router-link
          :to="`/books/${book.id}`"
          class="text-blue-600 hover:text-blue-800 text-sm font-medium"
        >
          詳細
        </router-link>
        
        <router-link
          :to="`/books/${book.id}/edit`"
          class="text-green-600 hover:text-green-800 text-sm font-medium"
        >
          編集
        </router-link>
        
        <button
          @click="$emit('delete', book.id)"
          class="text-red-600 hover:text-red-800 text-sm font-medium"
        >
          削除
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  book: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['delete']);

// 読書ステータスバッジのスタイル
const statusBadgeClass = computed(() => {
  const baseClass = "px-2 py-1 text-xs font-medium rounded-full";
  
  switch (props.book.reading_status) {
    case 'unread':
      return `${baseClass} bg-gray-100 text-gray-800`;
    case 'reading':
      return `${baseClass} bg-orange-100 text-orange-800`;
    case 'read':
      return `${baseClass} bg-green-100 text-green-800`;
    default:
      return `${baseClass} bg-gray-100 text-gray-800`;
  }
});

// 廃棄ステータスのスタイル
const discardStatusClass = computed(() => {
  if (!props.book.discard) return '';
  
  switch (props.book.discard) {
    case '廃棄予定':
      return 'text-orange-600 font-medium';
    case '廃棄済み':
      return 'text-red-600 font-medium';
    case '譲渡予定':
      return 'text-blue-600 font-medium';
    case '譲渡済み':
      return 'text-green-600 font-medium';
    default:
      return 'text-gray-600';
  }
});

// 受け入れ情報があるかどうか
const hasAcceptanceInfo = computed(() => {
  return props.book.acceptance_date || 
         props.book.acceptance_type || 
         props.book.acceptance_source || 
         props.book.discard;
});

// 日付フォーマット
const formatDate = (dateString) => {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  
  // 無効な日付の場合は元の文字列をそのまま返す
  if (isNaN(date.getTime())) {
    return dateString;
  }
  
  return date.toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
