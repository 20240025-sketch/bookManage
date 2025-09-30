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
          <!-- ISBN検索 -->
          <div>
            <label for="isbnSearch" class="block text-sm font-medium text-gray-700 mb-1">
              ISBN検索 🔍
            </label>
            <div class="relative">
              <input
                type="text"
                id="isbnSearch"
                ref="isbnInput"
                v-model="isbnSearch"
                @input="searchBooksByISBN"
                @focus="onIsbnFocus"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pl-10"
                placeholder="ISBN-13形式（978-）またはISBN-10で検索"
              />
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <div v-if="isbnSearching" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </div>
            </div>
            <p class="mt-1 text-xs text-gray-500">
              バーコードリーダーでの読み取り対応
            </p>
          </div>

          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-200" />
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-gray-500">または</span>
            </div>
          </div>

          <div>
            <label for="bookSearch" class="block text-sm font-medium text-gray-700 mb-1">
              タイトル・著者で検索
            </label>
            <input
              type="text"
              id="bookSearch"
              v-model="bookSearch"
              @input="searchBooks"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="タイトルまたは著者名で検索..."
            />
          </div>

          <div>
            <label for="ndcSearch" class="block text-sm font-medium text-gray-700 mb-1">
              NDC分類で検索
            </label>
            <select
              id="ndcSearch"
              v-model="ndcCategory"
              @change="searchBooksByNDC"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">すべてのカテゴリ</option>
              <option value="000">000-099: 総記（図書館、百科事典など）</option>
              <option value="100">100-199: 哲学・心理学・倫理学</option>
              <option value="200">200-299: 歴史・地理</option>
              <option value="300">300-399: 社会科学</option>
              <option value="400">400-499: 自然科学・数学</option>
              <option value="500">500-599: 技術・工学</option>
              <option value="600">600-699: 産業・家政学</option>
              <option value="700">700-799: 芸術・美術・音楽</option>
              <option value="800">800-899: 語学</option>
              <option value="900">900-999: 文学</option>
            </select>
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              @click="clearSearch"
              class="px-3 py-1 text-sm text-gray-600 bg-gray-100 rounded hover:bg-gray-200"
            >
              検索クリア
            </button>
            <button
              v-if="searchResults.length > 0"
              type="button"
              @click="selectAllAvailableBooks"
              class="px-3 py-1 text-sm text-blue-600 bg-blue-50 rounded hover:bg-blue-100"
            >
              利用可能な本を全選択
            </button>
            <button
              v-if="selectedBooks.length > 0"
              type="button"
              @click="clearAllSelections"
              class="px-3 py-1 text-sm text-red-600 bg-red-50 rounded hover:bg-red-100"
            >
              選択をクリア
            </button>
          </div>

          <div v-if="searchResults.length > 0" class="border rounded-md divide-y">
            <div
              v-for="book in searchResults"
              :key="book.id"
              class="p-4 hover:bg-gray-50"
            >
              <div class="flex items-start space-x-3">
                <input
                  type="checkbox"
                  :id="`book-${book.id}`"
                  :value="book.id"
                  :checked="isBookSelected(book.id)"
                  @change="toggleBookSelection(book)"
                  :disabled="book.is_fully_borrowed"
                  class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                />
                <div class="flex-1 min-w-0">
                  <label :for="`book-${book.id}`" class="cursor-pointer">
                    <div class="font-medium" :class="{ 'text-gray-400': book.is_fully_borrowed }">
                      {{ book.title }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ book.author }} | ISBN: {{ book.isbn || 'なし' }}
                      <span v-if="book.ndc" class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                        NDC: {{ book.ndc }}
                      </span>
                    </div>
                    <div class="text-sm mt-1">
                      <span class="font-medium">在庫状況:</span>
                      <span :class="{
                        'text-green-600': book.available_quantity > 0,
                        'text-red-500': book.available_quantity <= 0
                      }">
                        {{ book.available_quantity }}冊利用可能
                      </span>
                      <span class="text-gray-500 ml-1">
                        (全{{ book.quantity }}冊中{{ book.current_borrowed_count }}冊貸出中)
                      </span>
                    </div>
                    <div v-if="book.is_fully_borrowed" class="text-sm text-red-500 font-medium mt-1">
                      ※全冊貸出中のため選択できません
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 生徒の選択 -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-medium mb-4">生徒の選択</h2>
        <div class="space-y-4">
          <!-- 検索条件 -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="studentSearch" class="block text-sm font-medium text-gray-700 mb-1">
                名前で検索
              </label>
              <input
                type="text"
                id="studentSearch"
                v-model="studentSearch"
                @input="searchStudents"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="生徒の名前を入力..."
              />
            </div>
            <div>
              <label for="gradeClassSelect" class="block text-sm font-medium text-gray-700 mb-1">
                学年・クラス
              </label>
              <select
                id="gradeClassSelect"
                v-model="studentSearchFilters.gradeClass"
                @change="searchStudents"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">すべて</option>
                <option v-for="gradeClass in availableGradeClasses" :key="gradeClass.value" :value="gradeClass.value">
                  {{ gradeClass.label }}
                </option>
              </select>
            </div>
          </div>
          
          <div>
            <label for="studentNumber" class="block text-sm font-medium text-gray-700 mb-1">
              出席番号
            </label>
            <input
              type="number"
              id="studentNumber"
              v-model="studentSearchFilters.student_number"
              @input="searchStudents"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="番号"
              min="1"
              max="50"
            />
          </div>
          
          <!-- 検索条件のクリアボタン -->
          <div class="flex justify-between items-center">
            <div v-if="studentResults.length > 0" class="text-sm text-gray-600">
              {{ studentResults.length }}件の生徒が見つかりました
            </div>
            <button
              type="button"
              @click="clearStudentSearch"
              class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
            >
              検索クリア
            </button>
          </div>
        </div>

        <div v-if="studentResults.length > 0" class="mt-4">
          <div class="border rounded-md divide-y max-h-60 overflow-y-auto">
            <div
              v-for="student in studentResults"
              :key="student.id"
              @click="selectStudent(student)"
              class="p-4 hover:bg-gray-50 cursor-pointer transition-colors"
              :class="{ 'bg-blue-50 border-blue-200': selectedStudent?.id === student.id }"
            >
              <div class="font-medium text-gray-900">{{ student.name }}</div>
              <div class="text-sm text-gray-500 mt-1">
                <span class="inline-flex items-center">
                  <span class="font-medium">{{ student.grade }}年{{ student.class }}</span>
                  <span class="mx-2">•</span>
                  <span>学籍番号: {{ student.student_number }}</span>
                  <span v-if="student.active_borrows_count > 0" class="mx-2">•</span>
                  <span v-if="student.active_borrows_count > 0" class="text-orange-600 font-medium">
                    貸出中: {{ student.active_borrows_count }}冊
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else-if="(studentSearch || studentSearchFilters.student_number || studentSearchFilters.gradeClass) && studentResults.length === 0" class="mt-4 text-center py-8">
          <div class="text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">生徒が見つかりません</h3>
            <p class="mt-1 text-sm text-gray-500">検索条件を変更してお試しください</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 貸出フォーム -->
    <div class="mt-8 bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-medium mb-4">貸出情報</h2>
      
      <!-- エラーメッセージ表示 -->
      <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded relative">
        {{ error }}
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-sm font-medium text-gray-700 mb-2">
            選択された本 ({{ selectedBooks.length }}冊)
          </h3>
          <div v-if="selectedBooks.length > 0" class="space-y-2 max-h-32 overflow-y-auto">
            <div 
              v-for="book in selectedBooks" 
              :key="book.id"
              class="flex items-start justify-between p-3 bg-gray-50 rounded-md"
            >
              <div class="flex-1 min-w-0">
                <div class="font-medium text-sm">{{ book.title }}</div>
                <div class="text-xs text-gray-500 truncate">
                  {{ book.author }} | ISBN: {{ book.isbn || 'なし' }}
                </div>
              </div>
              <button
                @click="removeBookFromSelection(book.id)"
                class="ml-2 text-gray-400 hover:text-red-500 focus:outline-none"
                title="選択を解除"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
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
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();

const bookSearch = ref('');
const isbnSearch = ref('');
const isbnSearching = ref(false);
const studentSearch = ref('');
const studentSearchFilters = ref({
  student_number: '',
  gradeClass: ''
});
const availableGradeClasses = ref([]);
const ndcCategory = ref('');
const searchResults = ref([]);
const studentResults = ref([]);
const selectedBooks = ref([]);
const selectedStudent = ref(null);
const borrowedDate = ref(new Date().toISOString().split('T')[0]);
const error = ref('');

// 本日の日付（貸出日の上限として使用）
const today = new Date().toISOString().split('T')[0];

// 貸出可能かどうかをチェック
const canBorrow = computed(() => {
  return selectedBooks.value.length > 0 && 
         selectedStudent.value && 
         borrowedDate.value;
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

// NDC分類で本を検索
const searchBooksByNDC = async () => {
  if (!ndcCategory.value) {
    searchResults.value = [];
    return;
  }

  try {
    const response = await axios.get('/api/books', {
      params: { ndc_category: ndcCategory.value }
    });
    searchResults.value = response.data.data;
    // テキスト検索をクリア
    bookSearch.value = '';
  } catch (err) {
    error.value = 'NDC分類での検索中にエラーが発生しました';
    console.error(err);
  }
};

// 検索をクリア
const clearSearch = () => {
  bookSearch.value = '';
  isbnSearch.value = '';
  ndcCategory.value = '';
  searchResults.value = [];
  isbnSearching.value = false;
};

// ISBN検索
const searchBooksByISBN = async () => {
  if (!isbnSearch.value) {
    searchResults.value = [];
    isbnSearching.value = false;
    return;
  }

  // ISBN-13の場合はハイフンを除去
  const cleanedIsbn = isbnSearch.value.replace(/[-\s]/g, '');
  
  if (cleanedIsbn.length < 10) {
    return; // 最低10文字必要
  }

  isbnSearching.value = true;
  
  try {
    const response = await axios.get('/api/books', {
      params: { isbn: cleanedIsbn }
    });
    searchResults.value = response.data.data;
    
    // 他の検索フィールドをクリア
    bookSearch.value = '';
    ndcCategory.value = '';
  } catch (err) {
    error.value = 'ISBN検索中にエラーが発生しました';
    console.error(err);
  } finally {
    isbnSearching.value = false;
  }
};

// ISBN入力フィールドにフォーカスした時の処理
const onIsbnFocus = () => {
  // 他の検索結果をクリア（必要に応じて）
  if (bookSearch.value || ndcCategory.value) {
    bookSearch.value = '';
    ndcCategory.value = '';
  }
};

// 生徒検索をクリア
const clearStudentSearch = () => {
  studentSearch.value = '';
  studentSearchFilters.value.student_number = '';
  studentSearchFilters.value.gradeClass = '';
  studentResults.value = [];
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

// 生徒を検索
const searchStudents = async () => {
  // 検索条件が何も入力されていない場合は結果をクリア
  if (!studentSearch.value && 
      !studentSearchFilters.value.student_number &&
      !studentSearchFilters.value.gradeClass) {
    studentResults.value = [];
    return;
  }

  try {
    const params = {};
    
    // 名前検索
    if (studentSearch.value) {
      params.search = studentSearch.value;
    }
    
    // 学年・クラスでの検索
    if (studentSearchFilters.value.gradeClass) {
      const [grade, className] = studentSearchFilters.value.gradeClass.split('-');
      params.grade = grade;
      params.class = className;
    }
    
    // 出席番号フィルター（学籍番号での検索として処理）
    if (studentSearchFilters.value.student_number) {
      params.student_number = studentSearchFilters.value.student_number;
    }

    const response = await axios.get('/api/students', { params });
    studentResults.value = response.data.data;
  } catch (err) {
    error.value = '生徒の検索中にエラーが発生しました';
    console.error(err);
  }
};

// 本が選択されているかチェック
const isBookSelected = (bookId) => {
  return selectedBooks.value.some(book => book.id === bookId);
};

// 本の選択を切り替え
const toggleBookSelection = (book) => {
  if (book.is_fully_borrowed) {
    return; // 全冊貸出中の本は選択できない
  }
  
  const index = selectedBooks.value.findIndex(b => b.id === book.id);
  if (index > -1) {
    // 既に選択されている場合は削除
    selectedBooks.value.splice(index, 1);
  } else {
    // 選択されていない場合は在庫チェック
    const currentlySelected = selectedBooks.value.filter(b => b.id === book.id).length;
    
    if (currentlySelected >= book.available_quantity) {
      error.value = `「${book.title}」は利用可能な冊数(${book.available_quantity}冊)を超えて選択できません。`;
      return;
    }
    
    selectedBooks.value.push(book);
    error.value = ''; // エラーをクリア
  }
  
  // 本を選択した後、ISBN検索をクリアしてフォーカスを移動
  nextTick(() => {
    isbnSearch.value = '';
    searchResults.value = [];
    const isbnInput = document.getElementById('isbnSearch');
    if (isbnInput) {
      isbnInput.focus();
    }
  });
};

// 選択から本を削除
const removeBookFromSelection = (bookId) => {
  const index = selectedBooks.value.findIndex(book => book.id === bookId);
  if (index > -1) {
    selectedBooks.value.splice(index, 1);
  }
};

// 利用可能な本を全選択
const selectAllAvailableBooks = () => {
  const availableBooks = searchResults.value.filter(book => !book.is_fully_borrowed);
  availableBooks.forEach(book => {
    const currentlySelected = selectedBooks.value.filter(b => b.id === book.id).length;
    const canSelectMore = book.available_quantity - currentlySelected;
    
    // 利用可能な冊数分だけ選択
    for (let i = 0; i < canSelectMore; i++) {
      selectedBooks.value.push(book);
    }
  });
};

// 選択をクリア
const clearAllSelections = () => {
  selectedBooks.value = [];
};

// 生徒を選択
const selectStudent = (student) => {
  selectedStudent.value = student;
};

// 貸出処理
const handleBorrow = async () => {
  if (!canBorrow.value) {
    error.value = '本と生徒を選択し、貸出日を入力してください';
    return;
  }

  try {
    // 複数の本を一括で貸出
    await axios.post('/api/borrows/batch', {
      book_ids: selectedBooks.value.map(book => book.id),
      student_id: selectedStudent.value.id,
      borrowed_date: borrowedDate.value
    });

    // 成功したら生徒の詳細ページに遷移
    router.push(`/students/${selectedStudent.value.id}`);
  } catch (err) {
    if (err.response?.data?.message) {
      error.value = err.response.data.message;
    } else {
      error.value = '貸出処理中にエラーが発生しました';
    }
    console.error(err);
  }
};

// フォームをリセット
const resetForm = () => {
  bookSearch.value = '';
  isbnSearch.value = '';
  studentSearch.value = '';
  ndcCategory.value = '';
  studentSearchFilters.value.student_number = '';
  studentSearchFilters.value.gradeClass = '';
  searchResults.value = [];
  studentResults.value = [];
  selectedBooks.value = [];
  selectedStudent.value = null;
  borrowedDate.value = new Date().toISOString().split('T')[0];
  error.value = '';
  isbnSearching.value = false;
};

// ページマウント時にISBN検索フィールドにフォーカス
onMounted(() => {
  loadGradeClasses(); // 学年・クラス一覧を読み込み
  nextTick(() => {
    const isbnInput = document.getElementById('isbnSearch');
    if (isbnInput) {
      isbnInput.focus();
    }
  });
});
</script>