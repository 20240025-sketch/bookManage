<template>
  <div class="container mx-auto px-4 py-8">
    <!-- ヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">本の貸し出し</h1>
          <p class="mt-1 text-sm text-gray-600">
            生徒に本を貸し出します
          </p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- 本の選択 -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-medium mb-4">本の選択</h2>
        <div class="space-y-4">
          <div>
            <label for="bookSearch" class="block text-sm font-medium text-gray-700 mb-1">
              本を検索
            </label>
            <input
              type="text"
              id="bookSearch"
              v-model="bookSearch"
              @input="searchBooks"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="タイトルまたはISBNで検索..."
            />
          </div>

          <div v-if="searchResults.length > 0" class="border rounded-md divide-y">
            <div
              v-for="book in searchResults"
              :key="book.id"
              @click="selectBook(book)"
              class="p-4 hover:bg-gray-50 cursor-pointer"
              :class="{ 'bg-blue-50': selectedBook?.id === book.id }"
            >
              <div class="font-medium">{{ book.title }}</div>
              <div class="text-sm text-gray-500">
                {{ book.author }} | ISBN: {{ book.isbn || 'なし' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 生徒の選択 -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-medium mb-4">生徒の選択</h2>
        <div class="space-y-4">
          <div>
            <label for="studentSearch" class="block text-sm font-medium text-gray-700 mb-1">
              生徒を検索
            </label>
            <input
              type="text"
              id="studentSearch"
              v-model="studentSearch"
              @input="searchStudents"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="名前または学籍番号で検索..."
            />
          </div>

          <div v-if="studentResults.length > 0" class="border rounded-md divide-y">
            <div
              v-for="student in studentResults"
              :key="student.id"
              @click="selectStudent(student)"
              class="p-4 hover:bg-gray-50 cursor-pointer"
              :class="{ 'bg-blue-50': selectedStudent?.id === student.id }"
            >
              <div class="font-medium">{{ student.name }}</div>
              <div class="text-sm text-gray-500">
                {{ student.grade }}年{{ student.class }}組 | 学籍番号: {{ student.student_number }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 貸出フォーム -->
    <div class="mt-8 bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-medium mb-4">貸出情報</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-sm font-medium text-gray-700 mb-2">選択された本</h3>
          <div v-if="selectedBook" class="p-4 bg-gray-50 rounded-md">
            <div class="font-medium">{{ selectedBook.title }}</div>
            <div class="text-sm text-gray-500">
              {{ selectedBook.author }} | ISBN: {{ selectedBook.isbn || 'なし' }}
            </div>
          </div>
          <div v-else class="text-sm text-gray-500">
            本が選択されていません
          </div>
        </div>

        <div>
          <h3 class="text-sm font-medium text-gray-700 mb-2">選択された生徒</h3>
          <div v-if="selectedStudent" class="p-4 bg-gray-50 rounded-md">
            <div class="font-medium">{{ selectedStudent.name }}</div>
            <div class="text-sm text-gray-500">
              {{ selectedStudent.grade }}年{{ selectedStudent.class }}組 | 
              学籍番号: {{ selectedStudent.student_number }}
            </div>
          </div>
          <div v-else class="text-sm text-gray-500">
            生徒が選択されていません
          </div>
        </div>
      </div>

      <div class="mt-6 space-y-4">
        <div>
          <label for="borrowedDate" class="block text-sm font-medium text-gray-700 mb-1">
            貸出日 *
          </label>
          <input
            type="date"
            id="borrowedDate"
            v-model="borrowedDate"
            required
            :max="today"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          />
        </div>

        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="resetForm"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50"
          >
            クリア
          </button>
          <button
            type="button"
            @click="handleBorrow"
            :disabled="!canBorrow"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            貸し出す
          </button>
        </div>
      </div>
    </div>

    <!-- エラーメッセージ -->
    <div
      v-if="error"
      class="mt-4 bg-red-50 border border-red-200 rounded-md p-4 text-sm text-red-600"
    >
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();

const bookSearch = ref('');
const studentSearch = ref('');
const searchResults = ref([]);
const studentResults = ref([]);
const selectedBook = ref(null);
const selectedStudent = ref(null);
const borrowedDate = ref(new Date().toISOString().split('T')[0]);
const error = ref('');

// 本日の日付（貸出日の上限として使用）
const today = new Date().toISOString().split('T')[0];

// 貸出可能かどうかをチェック
const canBorrow = computed(() => {
  return selectedBook.value && selectedStudent.value && borrowedDate.value;
});

// 本を検索
const searchBooks = async () => {
  if (!bookSearch.value) {
    searchResults.value = [];
    return;
  }

  try {
    const response = await axios.get('/api/books', {
      params: { search: bookSearch.value }
    });
    searchResults.value = response.data.data;
  } catch (err) {
    error.value = '本の検索中にエラーが発生しました';
    console.error(err);
  }
};

// 生徒を検索
const searchStudents = async () => {
  if (!studentSearch.value) {
    studentResults.value = [];
    return;
  }

  try {
    const response = await axios.get('/api/students', {
      params: { search: studentSearch.value }
    });
    studentResults.value = response.data.data;
  } catch (err) {
    error.value = '生徒の検索中にエラーが発生しました';
    console.error(err);
  }
};

// 本を選択
const selectBook = (book) => {
  selectedBook.value = book;
};

// 生徒を選択
const selectStudent = (student) => {
  selectedStudent.value = student;
};

// 貸出処理
const handleBorrow = async () => {
  if (!canBorrow.value) return;

  try {
    await axios.post('/api/borrows', {
      book_id: selectedBook.value.id,
      student_id: selectedStudent.value.id,
      borrowed_date: borrowedDate.value
    });

    // 成功したら生徒の詳細ページに遷移
    router.push(`/students/${selectedStudent.value.id}`);
  } catch (err) {
    error.value = '貸出処理中にエラーが発生しました';
    console.error(err);
  }
};

// フォームをリセット
const resetForm = () => {
  bookSearch.value = '';
  studentSearch.value = '';
  searchResults.value = [];
  studentResults.value = [];
  selectedBook.value = null;
  selectedStudent.value = null;
  borrowedDate.value = new Date().toISOString().split('T')[0];
  error.value = '';
};
</script>