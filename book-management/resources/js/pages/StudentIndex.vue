<template>
  <div class="container mx-auto px-4 py-8">
    <!-- ヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">生徒一覧</h1>
          <p class="mt-1 text-sm text-gray-600">
            登録されている生徒の一覧を表示します
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="showCreateModal = true"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md"
          >
            + 新規登録
          </button>
        </div>
      </div>
    </div>

    <!-- 検索フィルター -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label for="searchName" class="block text-sm font-medium text-gray-700 mb-1">
            名前で検索
          </label>
          <input
            type="text"
            id="searchName"
            v-model="filters.name"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="名前を入力..."
          />
        </div>
        <div>
          <label for="searchGrade" class="block text-sm font-medium text-gray-700 mb-1">
            学年
          </label>
          <select
            id="searchGrade"
            v-model="filters.grade"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">すべて</option>
            <option value="1">1年</option>
            <option value="2">2年</option>
            <option value="3">3年</option>
          </select>
        </div>
        <div>
          <label for="searchClass" class="block text-sm font-medium text-gray-700 mb-1">
            クラス
          </label>
          <select
            id="searchClass"
            v-model="filters.class"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">すべて</option>
            <option value="特別進学">特別進学</option>
            <option value="進学">進学</option>
            <option value="総合１">総合１</option>
            <option value="総合２">総合２</option>
            <option value="総合３">総合３</option>
            <option value="情報会計">情報会計</option>
            <option value="工業">工業</option>
          </select>
        </div>
      </div>
    </div>

    <!-- 生徒一覧 -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              学籍番号
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              名前
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              学年・クラス
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              メールアドレス
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              貸出中の本
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              操作
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="student in filteredStudents" :key="student.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ student.student_number }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ student.name }}
              <span v-if="student.name_transcription" class="text-gray-500 text-xs ml-2">
                ({{ student.name_transcription }})
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ student.grade }}年{{ student.class }}組
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ student.email }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ student.active_borrows_count || 0 }}冊
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                @click="showBorrowHistory(student)"
                class="text-indigo-600 hover:text-indigo-900 mr-4"
              >
                貸出履歴
              </button>
              <button
                @click="editStudent(student)"
                class="text-blue-600 hover:text-blue-900"
              >
                編集
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 生徒登録/編集モーダル -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
      <div class="bg-white rounded-lg p-8 max-w-lg w-full">
        <h2 class="text-xl font-bold mb-4">
          {{ showEditModal ? '生徒情報の編集' : '新規生徒登録' }}
        </h2>
        <form @submit.prevent="showEditModal ? updateStudent() : createStudent()">
          <div class="space-y-4">
            <div>
              <label for="studentNumber" class="block text-sm font-medium text-gray-700">
                学籍番号 *
              </label>
              <input
                id="studentNumber"
                v-model="form.student_number"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700">
                名前 *
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div>
              <label for="nameTranscription" class="block text-sm font-medium text-gray-700">
                フリガナ
              </label>
              <input
                id="nameTranscription"
                v-model="form.name_transcription"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">
                メールアドレス *
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="grade" class="block text-sm font-medium text-gray-700">
                  学年 *
                </label>
                <select
                  id="grade"
                  v-model="form.grade"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                  <option value="1">1年</option>
                  <option value="2">2年</option>
                  <option value="3">3年</option>
                </select>
              </div>
              <div>
                <label for="class" class="block text-sm font-medium text-gray-700">
                  クラス *
                </label>
                <select
                  id="class"
                  v-model="form.class"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                  <option value="特別進学">特別進学</option>
                  <option value="進学">進学</option>
                  <option value="総合１">総合１</option>
                  <option value="総合２">総合２</option>
                  <option value="総合３">総合３</option>
                  <option value="情報会計">情報会計</option>
                  <option value="工業">工業</option>
                </select>
              </div>
            </div>
          </div>
          <div class="mt-6 flex justify-end space-x-3">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50"
            >
              キャンセル
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700"
            >
              {{ showEditModal ? '更新' : '登録' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- 貸出履歴モーダル -->
    <div v-if="showBorrowModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
      <div class="bg-white rounded-lg p-8 max-w-4xl w-full max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-bold">
            {{ selectedStudent?.name }}さんの貸出履歴
          </h2>
          <button
            @click="showBorrowModal = false"
            class="text-gray-400 hover:text-gray-500"
          >
            <span class="sr-only">閉じる</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <div v-if="selectedStudent?.active_borrows?.length > 0" class="border-b pb-4">
            <h3 class="text-lg font-medium mb-3">現在借りている本</h3>
            <ul class="space-y-2">
              <li v-for="borrow in selectedStudent.active_borrows" :key="borrow.id" class="flex justify-between items-center">
                <div>
                  <span class="font-medium">{{ borrow.book.title }}</span>
                  <span class="text-sm text-gray-500">
                    ({{ formatDate(borrow.borrowed_date) }}から)
                  </span>
                </div>
                <button
                  @click="returnBook(borrow)"
                  class="px-3 py-1 text-sm text-white bg-green-600 rounded-md hover:bg-green-700"
                >
                  返却
                </button>
              </li>
            </ul>
          </div>

          <div>
            <h3 class="text-lg font-medium mb-3">貸出履歴</h3>
            <div class="bg-white shadow overflow-hidden rounded-md">
              <ul class="divide-y divide-gray-200">
                <li v-for="borrow in selectedStudent?.borrow_history" :key="borrow.id" class="px-4 py-3">
                  <div class="flex justify-between items-start">
                    <div>
                      <p class="font-medium">{{ borrow.book.title }}</p>
                      <p class="text-sm text-gray-500">
                        {{ formatDate(borrow.borrowed_date) }} - 
                        {{ borrow.returned_date ? formatDate(borrow.returned_date) : '未返却' }}
                      </p>
                    </div>
                    <span
                      :class="[
                        'px-2 py-1 text-xs rounded-full',
                        borrow.returned_date
                          ? 'bg-green-100 text-green-800'
                          : 'bg-yellow-100 text-yellow-800'
                      ]"
                    >
                      {{ borrow.returned_date ? '返却済み' : '貸出中' }}
                    </span>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const students = ref([]);
const loading = ref(true);
const error = ref('');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showBorrowModal = ref(false);
const selectedStudent = ref(null);

const filters = ref({
  name: '',
  grade: '',
  class: ''
});

const form = ref({
  student_number: '',
  name: '',
  name_transcription: '',
  email: '',
  grade: '1',
  class: '特別進学'
});

// フィルター適用後の生徒リスト
const filteredStudents = computed(() => {
  return students.value.filter(student => {
    if (filters.value.name && !student.name.includes(filters.value.name)) {
      return false;
    }
    if (filters.value.grade && student.grade !== filters.value.grade) {
      return false;
    }
    if (filters.value.class && student.class !== filters.value.class) {
      return false;
    }
    return true;
  });
});

// 生徒一覧の取得
const loadStudents = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/students');
    students.value = response.data.data;
  } catch (err) {
    error.value = '生徒情報の取得に失敗しました';
    console.error(err);
  } finally {
    loading.value = false;
  }
};

// 生徒の登録
const createStudent = async () => {
  try {
    const response = await axios.post('/api/students', form.value);
    students.value.push(response.data.data);
    closeModal();
  } catch (err) {
    error.value = '生徒の登録に失敗しました';
    console.error(err);
  }
};

// 生徒情報の更新
const updateStudent = async () => {
  try {
    const response = await axios.put(`/api/students/${selectedStudent.value.id}`, form.value);
    const index = students.value.findIndex(s => s.id === selectedStudent.value.id);
    if (index !== -1) {
      students.value[index] = response.data.data;
    }
    closeModal();
  } catch (err) {
    error.value = '生徒情報の更新に失敗しました';
    console.error(err);
  }
};

// 編集モーダルを開く
const editStudent = (student) => {
  selectedStudent.value = student;
  form.value = { ...student };
  showEditModal.value = true;
};

// 貸出履歴モーダルを開く
const showBorrowHistory = async (student) => {
  try {
    const response = await axios.get(`/api/students/${student.id}/borrows`);
    selectedStudent.value = {
      ...student,
      active_borrows: response.data.active_borrows,
      borrow_history: response.data.borrow_history
    };
    showBorrowModal.value = true;
  } catch (err) {
    error.value = '貸出履歴の取得に失敗しました';
    console.error(err);
  }
};

// 本の返却処理
const returnBook = async (borrow) => {
  try {
    await axios.patch(`/api/borrows/${borrow.id}/return`);
    // 貸出履歴を再読み込み
    await showBorrowHistory(selectedStudent.value);
  } catch (err) {
    error.value = '本の返却処理に失敗しました';
    console.error(err);
  }
};

// モーダルを閉じる
const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedStudent.value = null;
  form.value = {
    student_number: '',
    name: '',
    name_transcription: '',
    email: '',
    grade: '1',
    class: 'A'
  };
};

// 日付のフォーマット
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ja-JP');
};

// コンポーネントのマウント時に生徒一覧を取得
onMounted(() => {
  loadStudents();
});
</script>