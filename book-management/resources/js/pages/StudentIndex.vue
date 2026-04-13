<template>
  <div class="container mx-auto px-4 pb-8">
    <!-- ヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ userPermissions.isAdmin ? '生徒一覧' : '自分の情報' }}</h1>
          <p class="mt-1 text-sm text-gray-600">
            {{ userPermissions.isAdmin ? '登録されている生徒の一覧を表示します' : '自分の生徒情報を確認できます' }}
          </p>
        </div>
        <!-- 管理者のみ新規登録ボタンを表示 -->
        <div v-if="userPermissions.isAdmin" class="flex items-center space-x-3">
          <button
            @click="showCreateModal = true"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md"
          >
            + 新規登録
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

    <!-- 生徒登録・編集モーダル -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-8 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-bold">{{ showEditModal ? '生徒情報編集' : '新規生徒登録' }}</h2>
          <button
            @click="closeModal"
            class="text-gray-400 hover:text-gray-500"
          >
            <span class="sr-only">閉じる</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="showEditModal ? updateStudent() : createStudent()">
          <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">名前</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
            />
          </div>
          <div class="mb-4">
            <label for="grade" class="block text-sm font-medium text-gray-700">学年</label>
            <select
              id="grade"
              v-model="form.grade"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
            >
              <option value="1">1年</option>
              <option value="2">2年</option>
              <option value="3">3年</option>
            </select>
          </div>
          <div class="mb-6">
            <label for="class" class="block text-sm font-medium text-gray-700">クラス</label>
            <select
              id="class"
              v-model="form.class"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
            >
              <option value="A">A組</option>
              <option value="B">B組</option>
              <option value="C">C組</option>
            </select>
          </div>
          <div class="flex justify-end space-x-3">
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

    <!-- 検索フィルター -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label for="searchName" class="block text-sm font-medium text-gray-700 mb-1">
            名前
          </label>
          <input
            id="searchName"
            v-model="filters.name"
            @input="applyFilters"
            type="text"
            placeholder="生徒名を入力"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          />
        </div>
        <div>
          <label for="searchISBN" class="block text-sm font-medium text-gray-700 mb-1">
            ISBNコード
          </label>
          <input
            id="searchISBN"
            v-model="filters.isbn"
            @input="applyFilters"
            type="text"
            placeholder="ISBNコードを入力"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          />
        </div>
        <div v-if="userPermissions.isAdmin">
          <label for="searchGradeClass" class="block text-sm font-medium text-gray-700 mb-1">
            学年・クラス
          </label>
          <select
            id="searchGradeClass"
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
          <!-- 空のカラム（将来的な拡張用） -->
        </div>
      </div>
    </div>

    <!-- 利用者向けメッセージ -->
    <div v-if="!userPermissions.isAdmin" class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
      <div class="flex">
        <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-blue-800">自分の情報</h3>
          <p class="mt-1 text-sm text-blue-700">
            以下に表示されるのはあなたの生徒情報です。貸出履歴や現在の貸出状況を確認できます。
          </p>
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
          <tr v-for="student in students" :key="student.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ student.student_number }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              <div>{{ student.name }}</div>
              <span v-if="student.name_transcription" class="text-gray-500 text-xs block mt-1">
                ({{ student.name_transcription }})
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              <template v-if="student.school_class">
                {{ student.school_class.grade }}年 {{ student.school_class.name }}
              </template>
              <template v-else>
                <span class="text-gray-400">クラス未設定</span>
              </template>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ student.email }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              <div class="flex items-center gap-2">
                <!-- 期限切れ警告アイコン -->
                <svg v-if="student.overdue_borrows_count > 0" 
                     class="h-5 w-5 text-red-500 flex-shrink-0" 
                     fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                
                <div>
                  <div class="font-medium">{{ student.active_borrows_count || 0 }}冊</div>
                  <div v-if="student.overdue_borrows_count > 0" class="text-xs text-red-600 font-medium">
                    期限切れ{{ student.overdue_borrows_count }}冊
                  </div>
                </div>
              </div>
            </td>
            <!-- 管理者のみ編集ボタンを含むすべての操作を表示 -->
            <td v-if="userPermissions.isAdmin" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
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
            <!-- 利用者の場合は貸出履歴の閲覧のみ可能（返却・編集操作は不可） -->
            <td v-else class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                @click="showBorrowHistory(student)"
                class="text-indigo-600 hover:text-indigo-900"
              >
                貸出履歴
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ページネーション -->
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="prevPage"
          :disabled="pagination.current_page <= 1"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          前
        </button>
        <button
          @click="nextPage"
          :disabled="pagination.current_page >= pagination.last_page"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          次
        </button>
      </div>
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            <span class="font-medium">{{ pagination.from }}</span>
            -
            <span class="font-medium">{{ pagination.to }}</span>
            件 / 全
            <span class="font-medium">{{ pagination.total }}</span>
            件
          </p>
        </div>
        <div>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <button
              @click="prevPage"
              :disabled="pagination.current_page <= 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            
            <!-- ページ番号ボタン -->
            <template v-for="page in getVisiblePages()" :key="page">
              <button
                v-if="page !== '...'"
                @click="changePage(page)"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  pagination.current_page === page
                    ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                ]"
              >
                {{ page }}
              </button>
              <span
                v-else
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
              >
                ...
              </span>
            </template>
            
            <button
              @click="nextPage"
              :disabled="pagination.current_page >= pagination.last_page"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
          </nav>
        </div>
      </div>
    </div>

    <!-- 生徒登録/編集モーダル -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
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
                  <option value="調理">調理</option>
                  <option value="福祉">福祉</option>
                  <option value="情報会計">情報会計</option>
                  <option value="総合1">総合1</option>
                  <option value="総合2">総合2</option>
                  <option value="総合3">総合3</option>
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
    <div v-if="showBorrowModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-8 max-w-4xl w-full max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h2 class="text-xl font-bold">
              {{ selectedStudent?.name }}さんの貸出履歴
            </h2>
            <p class="text-sm text-gray-600 mt-1">
              総貸出冊数: {{ selectedStudent?.total_borrows_count || 0 }}冊
            </p>
          </div>
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

        <!-- アチーブメント表示（貸出履歴の上に独立表示） -->
        <div v-if="shouldShowAchievement || shouldShowNDCAchievement" class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-4 border border-purple-200 mb-6">
                <h3 class="text-lg font-semibold text-purple-800 mb-3 flex items-center gap-2">
                  🏆 アチーブメント
                </h3>
                
                <div class="flex flex-wrap gap-4">
                  <!-- 読書ランク アチーブメント -->
                  <div v-if="shouldShowAchievement" class="bg-white rounded-lg p-4 border shadow-sm hover:shadow-md transition-shadow flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-3">
                      <!-- エンブレムアイコン -->
                      <div class="relative flex-shrink-0">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-lg border-2 text-white transition-all duration-300 hover:scale-105"
                             :class="[
                               'bg-gradient-to-br', 
                               getRankStyling.bgGradient,
                               getRankStyling.borderColor,
                               getRankStyling.effects
                             ]">
                          <!-- 特別キラキラエフェクト -->
                          <div v-if="getRankStyling.sparkleEffect" 
                               class="absolute -inset-2 bg-gradient-to-r from-yellow-200 via-yellow-400 to-orange-300 rounded-full opacity-75 blur-sm animate-pulse"></div>
                          
                          <span class="text-2xl z-10 relative">{{ getRankStyling.icon }}</span>
                        </div>
                        
                        <!-- ランクバッジ -->
                        <div class="absolute -top-1 -right-1 w-6 h-6 rounded-full flex items-center justify-center shadow-lg border-2 text-white text-xs font-bold"
                             :class="[
                               'bg-gradient-to-r',
                               getRankStyling.badgeGradient,
                               getRankStyling.badgeBorder
                             ]">
                          {{ getCurrentRank }}
                        </div>
                      </div>
                      
                      <!-- ランク情報 -->
                      <div class="min-w-0 flex-1">
                        <h4 class="font-semibold text-gray-900 mb-1">{{ getRankTitle.title }}</h4>
                        <p class="text-sm text-gray-600 mb-2">{{ getRankTitle.subtitle }}</p>
                        <div class="flex items-center gap-2 text-xs">
                          <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full font-medium">
                            Rank {{ getCurrentRank }}
                          </span>
                          <span class="text-gray-500">{{ selectedStudent?.total_borrows_count }}冊読破</span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- プログレスバー -->
                    <div class="space-y-2">
                      <div class="flex justify-between text-xs text-gray-600">
                        <span>次のランクまで</span>
                        <span>{{ 50 - ((selectedStudent?.total_borrows_count || 0) % 50) }}冊</span>
                      </div>
                      <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-300"
                             :class="getRankStyling.bgGradient.replace('bg-gradient-to-br', 'bg-gradient-to-r')"
                             :style="{ width: `${((selectedStudent?.total_borrows_count || 0) % 50) / 50 * 100}%` }"></div>
                      </div>
                    </div>
                    
                    <!-- 特別メッセージ -->
                    <div v-if="(selectedStudent?.total_borrows_count || 0) >= 100" 
                         class="mt-3 p-2 rounded-lg border text-center"
                         :class="[
                           (selectedStudent?.total_borrows_count || 0) >= 500 
                             ? 'bg-gradient-to-r from-purple-100 to-pink-100 border-purple-200 text-purple-800'
                             : 'bg-gradient-to-r from-yellow-100 to-orange-100 border-yellow-200 text-yellow-800'
                         ]">
                      <div class="font-bold text-sm animate-pulse">
                        {{ (selectedStudent?.total_borrows_count || 0) >= 500 ? '🔮 ULTIMATE SAGE 🔮' : '🌟 READING MASTER 🌟' }}
                      </div>
                      <div class="text-xs mt-1">
                        {{ (selectedStudent?.total_borrows_count || 0) >= 500 
                           ? '500冊の偉業達成！知識の頂点に立つ究極の賢者です！'
                           : '100冊の大台突破！あなたは真の読書マスターです！' }}
                      </div>
                    </div>
                  </div>

                  <!-- NDCジャンル アチーブメント -->
                  <div v-if="shouldShowNDCAchievement" class="bg-white rounded-lg p-4 border shadow-sm hover:shadow-md transition-shadow flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-3">
                      <!-- ジャンルアイコン -->
                      <div class="relative flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-300 via-purple-300 to-indigo-300 border-2 border-pink-200 shadow-lg flex items-center justify-center">
                          <div class="absolute -inset-1 bg-gradient-to-r from-pink-200 via-purple-200 to-indigo-200 rounded-full opacity-50 blur-sm animate-pulse"></div>
                          <span class="text-2xl z-10 relative animate-bounce">🌈</span>
                        </div>
                        
                        <!-- 完了度バッジ -->
                        <div class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-gradient-to-r from-pink-500 to-purple-500 border-2 border-white flex items-center justify-center shadow-sm">
                          <span class="text-xs text-white font-bold">{{ getNDCAchievements.completedCount }}</span>
                        </div>
                      </div>
                      
                      <!-- ジャンル情報 -->
                      <div class="min-w-0 flex-1">
                        <h4 class="font-semibold text-gray-900 mb-1">📖 ジャンルマスター</h4>
                        <p class="text-sm text-gray-600 mb-2">多様なジャンルを読破</p>
                        <div class="flex items-center gap-2 text-xs">
                          <span class="px-2 py-1 bg-pink-100 text-pink-800 rounded-full font-medium">
                            {{ getNDCAchievements.completedCount }}/10 ジャンル
                          </span>
                          <span class="text-gray-500">完成度: {{ getNDCAchievements.completionRate }}%</span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- ジャンル進捗 -->
                    <div class="space-y-3">
                      <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-pink-400 to-purple-400 h-2 rounded-full transition-all duration-300"
                             :style="{ width: `${getNDCAchievements.completionRate}%` }"></div>
                      </div>
                      
                      <!-- ジャンル一覧（コンパクト表示） -->
                      <div class="grid grid-cols-5 gap-1">
                        <div v-for="(category, key) in ndcCategories" :key="key"
                             class="flex flex-col items-center p-1 rounded-lg text-xs transition-all duration-200"
                             :class="[
                               getNDCAchievements.completedCategories.includes(key)
                                 ? 'bg-gradient-to-br from-green-100 to-emerald-100 text-green-700 shadow-sm'
                                 : 'bg-gray-50 text-gray-400'
                             ]"
                             :title="category.name">
                          <span class="text-sm" :class="getNDCAchievements.completedCategories.includes(key) ? 'animate-pulse' : ''">
                            {{ category.icon }}
                          </span>
                          <span v-if="getNDCAchievements.completedCategories.includes(key)" class="text-green-500 text-xs">✓</span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- 完成メッセージ -->
                    <div v-if="getNDCAchievements.completedCount === 10" 
                         class="mt-3 p-2 bg-gradient-to-r from-yellow-100 to-pink-100 rounded-lg border border-yellow-200 text-center">
                      <div class="text-yellow-700 font-bold text-sm animate-pulse">
                        🎉 全ジャンル制覇！ 🎉
                      </div>
                      <div class="text-yellow-600 text-xs mt-1">
                        あなたは真の読書家です！
                      </div>
                    </div>
                  </div>
                </div>
        </div>

        <div class="space-y-6">
          <!-- 現在借りている本（改善版） -->
          <div v-if="selectedStudent?.active_borrows?.length > 0" class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                📖 現在借りている本
                <span class="bg-blue-600 text-white text-sm px-2 py-1 rounded-full">{{ selectedStudent.active_borrows.length }}冊</span>
              </h3>
              <div v-if="userPermissions.isAdmin" class="flex items-center gap-2">
                <!-- 全選択/全解除ボタン（管理者のみ） -->
                <button
                  @click="toggleSelectAll"
                  class="text-sm text-blue-600 hover:text-blue-800 px-3 py-1 rounded-md border border-blue-300 hover:bg-blue-100 transition-colors"
                >
                  {{ selectedBorrows.length === selectedStudent.active_borrows.length ? '全解除' : '全選択' }}
                </button>
                <!-- まとめて返却ボタン（管理者のみ） -->
                <button
                  v-if="selectedBorrows.length > 0"
                  @click="batchReturnBooks"
                  :disabled="processingReturn"
                  class="px-3 py-2 text-sm text-white bg-green-600 rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-all"
                >
                  {{ processingReturn ? '処理中...' : `選択した${selectedBorrows.length}冊を返却` }}
                </button>
              </div>
            </div>
            
            <div class="grid gap-3">
              <div v-for="borrow in selectedStudent.active_borrows" :key="borrow.id" 
                  class="bg-white rounded-lg p-4 border shadow-sm hover:shadow-md transition-shadow"
                  :class="[
                    isOverdue(borrow.due_date) ? 'border-red-300 bg-red-50' : 
                    getDaysUntilDue(borrow.due_date) <= 3 && getDaysUntilDue(borrow.due_date) >= 0 ? 'border-yellow-300 bg-yellow-50' : 
                    'border-gray-200',
                    selectedBorrows.includes(borrow.id) ? 'ring-2 ring-blue-300' : ''
                  ]">
                <div class="flex items-start justify-between">
                  <div class="flex items-start gap-3 flex-1">
                    <!-- チェックボックス（管理者のみ） -->
                    <input 
                      v-if="userPermissions.isAdmin"
                      type="checkbox" 
                      :value="borrow.id" 
                      v-model="selectedBorrows" 
                      class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                    
                    <!-- 本の情報 -->
                    <div class="flex-1">
                      <div class="flex items-center gap-2 mb-2">
                        <!-- ステータスアイコン -->
                        <div v-if="isOverdue(borrow.due_date)" class="flex items-center gap-1 text-red-600">
                          <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                          </svg>
                          <span class="text-xs font-medium">期限超過</span>
                        </div>
                        <div v-else-if="getDaysUntilDue(borrow.due_date) <= 3 && getDaysUntilDue(borrow.due_date) >= 0" class="flex items-center gap-1 text-yellow-600">
                          <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                          </svg>
                          <span class="text-xs font-medium">要注意</span>
                        </div>
                      </div>
                      
                      <h4 class="font-semibold text-gray-900 mb-1">{{ borrow.book.title }}</h4>
                      <p class="text-sm text-gray-600 mb-2">{{ borrow.book.author }} / {{ borrow.book.publisher }}</p>
                      
                      <!-- 日付情報 -->
                      <div class="space-y-1 text-xs">
                        <div class="text-gray-500">
                          📅 貸出日: {{ formatDate(borrow.borrowed_date) }}
                        </div>
                        <div :class="[
                          'font-medium',
                          isOverdue(borrow.due_date) ? 'text-red-600' : 
                          getDaysUntilDue(borrow.due_date) <= 3 && getDaysUntilDue(borrow.due_date) >= 0 ? 'text-yellow-600' : 
                          'text-gray-600'
                        ]">
                          ⏰ 返却期限: {{ formatDate(borrow.due_date) }}
                          <span v-if="isOverdue(borrow.due_date)" class="ml-1 text-red-700 font-bold">
                            ({{ Math.abs(getDaysUntilDue(borrow.due_date)) }}日超過)
                          </span>
                          <span v-else-if="getDaysUntilDue(borrow.due_date) <= 3 && getDaysUntilDue(borrow.due_date) >= 0" class="ml-1 text-yellow-700 font-bold">
                            (あと{{ getDaysUntilDue(borrow.due_date) }}日)
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- 返却ボタン（管理者のみ） -->
                  <button
                    v-if="userPermissions.isAdmin"
                    @click="() => { console.log('返却ボタンクリック:', borrow); returnBook(borrow); }"
                    :disabled="processingReturn"
                    class="px-3 py-1 text-sm text-white bg-green-600 rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-all"
                  >
                    返却
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- 貸出履歴（改善版） -->
          <div class="bg-gradient-to-r from-gray-50 to-slate-50 rounded-lg p-4 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              📚 貸出履歴 
              <span class="bg-gray-600 text-white text-sm px-2 py-1 rounded-full">{{ selectedStudent?.borrow_history?.length || 0 }}冊</span>
            </h3>
            
            <div class="space-y-3 max-h-96 overflow-y-auto">
              <div v-for="borrow in selectedStudent?.borrow_history" :key="borrow.id" 
                  class="bg-white rounded-lg p-3 border shadow-sm hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                      <h4 class="font-medium text-gray-900">{{ borrow.book.title }}</h4>
                      <span class="px-2 py-1 text-xs rounded-full"
                            :class="[
                              borrow.returned_date
                                ? 'bg-green-100 text-green-800'
                                : isOverdue(borrow.due_date)
                                  ? 'bg-red-100 text-red-800'
                                  : getDaysUntilDue(borrow.due_date) <= 3 && getDaysUntilDue(borrow.due_date) >= 0
                                    ? 'bg-yellow-100 text-yellow-800'
                                    : 'bg-blue-100 text-blue-800'
                            ]">
                        {{ borrow.returned_date ? '✅ 返却済み' : 
                           isOverdue(borrow.due_date) ? '⚠️ 期限切れ' :
                           getDaysUntilDue(borrow.due_date) <= 3 && getDaysUntilDue(borrow.due_date) >= 0 ? '⏰ 要注意' : 
                           '📖 貸出中' }}
                      </span>
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-2">{{ borrow.book.author }}</p>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs text-gray-500">
                      <div>📅 貸出: {{ formatDate(borrow.borrowed_date) }}</div>
                      <div>⏰ 期限: {{ formatDate(borrow.due_date) }}</div>
                      <div v-if="borrow.returned_date" class="text-green-600">✅ 返却: {{ formatDate(borrow.returned_date) }}</div>
                      <div v-else-if="!borrow.returned_date && isOverdue(borrow.due_date)" class="text-red-600">
                        ⚠️ {{ Math.abs(getDaysUntilDue(borrow.due_date)) }}日超過
                      </div>
                      <div v-else-if="!borrow.returned_date && getDaysUntilDue(borrow.due_date) <= 3 && getDaysUntilDue(borrow.due_date) >= 0" class="text-yellow-600">
                        ⏰ あと{{ getDaysUntilDue(borrow.due_date) }}日
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- 貸出履歴が空の場合のメッセージ -->
            <div v-if="!selectedStudent?.borrow_history || selectedStudent.borrow_history.length === 0" 
                 class="text-center py-8 text-gray-500">
              <div class="text-4xl mb-2">📚</div>
              <p>まだ貸出履歴がありません</p>
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

// プロキシ経由でAPIにアクセス（ベースURLは不要）
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';

const students = ref([]);
const loading = ref(true);
const error = ref('');
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  from: 0,
  to: 0
});

// 権限管理
const userPermissions = ref({
  isAdmin: false,
  canViewStudents: false
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

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showBorrowModal = ref(false);
const selectedStudent = ref(null);
const selectedBorrows = ref([]);
const processingReturn = ref(false);

const filters = ref({
  name: '',
  gradeClass: '',
  isbn: ''
});

const availableGradeClasses = ref([]);

const form = ref({
  student_number: '',
  name: '',
  name_transcription: '',
  email: '',
  grade: '1',
  class: '特別進学'
});

// フィルター変更時に生徒リストを再読み込み
const applyFilters = () => {
  console.log('applyFilters called with filters:', filters.value);
  loadStudents(1); // 1ページ目から開始
};

// アチーブメント表示判定（10冊以上読んだ生徒は表示）
const shouldShowAchievement = computed(() => {
  const count = selectedStudent.value?.total_borrows_count || 0;
  return count >= 10;
});

// 50の倍数かどうかを判定（祝福メッセージ用）
const shouldShowCongratulation = computed(() => {
  const count = selectedStudent.value?.total_borrows_count || 0;
  return count > 0 && count % 50 === 0;
});

// ランク計算（50冊ごと）
const getCurrentRank = computed(() => {
  return Math.floor((selectedStudent.value?.total_borrows_count || 0) / 50);
});

// ランクタイトル取得（50冊ベース）
const getRankTitle = computed(() => {
  const rank = getCurrentRank.value;
  const count = selectedStudent.value?.total_borrows_count || 0;
  
  if (rank >= 10) return { title: '【究極の賢者】', subtitle: '知識の頂点に立つ者' }; // 500冊+
  if (rank >= 8) return { title: '【伝説の賢者】', subtitle: '知識の守護者となりし者' }; // 400冊+
  if (rank >= 6) return { title: '【真なる探求者】', subtitle: '叡智の深淵に触れし者' }; // 300冊+
  if (rank >= 4) return { title: '【知識の使徒】', subtitle: '学びの道を極めし者' }; // 200冊+
  if (rank >= 2) return { title: '【学問の徒】', subtitle: '知の探求に身を捧げし者' }; // 100冊+
  if (rank >= 1) return { title: '【読書の達人】', subtitle: '本との深い絆を築きし者' }; // 50冊+
  return { title: '【知識の探求者】', subtitle: '読書への情熱を燃やす者よ' }; // 10-49冊
});

// ランクによる色とスタイル設定（50冊ベース）
const getRankStyling = computed(() => {
  const count = selectedStudent.value?.total_borrows_count || 0;
  const rank = getCurrentRank.value;
  
  // 500冊超え（ランク10+）の究極装飾
  if (rank >= 10) {
    return {
      bgGradient: 'from-purple-400 via-pink-500 to-red-500',
      borderColor: 'border-purple-300/80',
      shadowColor: 'shadow-purple-500/50',
      textColor: 'text-purple-100',
      badgeGradient: 'from-purple-500 via-pink-600 to-red-600',
      badgeBorder: 'border-purple-400',
      effects: 'animate-pulse',
      icon: '⚔️',
      auraColor: 'bg-purple-400/40',
      sparkleEffect: true
    };
  }
  
  // 400冊超え（ランク8-9）- 伝説級
  if (rank >= 8) {
    return {
      bgGradient: 'from-indigo-500 via-purple-600 to-pink-700',
      borderColor: 'border-indigo-400/70',
      shadowColor: 'shadow-indigo-500/40',
      textColor: 'text-indigo-100',
      badgeGradient: 'from-indigo-500 via-purple-600 to-pink-600',
      badgeBorder: 'border-indigo-400',
      effects: 'animate-pulse',
      icon: '🐉',
      auraColor: 'bg-indigo-400/30',
      sparkleEffect: true
    };
  }
  
  // 300冊超え（ランク6-7）- 真なる探求者
  if (rank >= 6) {
    return {
      bgGradient: 'from-blue-500 via-cyan-600 to-teal-700',
      borderColor: 'border-blue-400/60',
      shadowColor: 'shadow-blue-500/30',
      textColor: 'text-blue-100',
      badgeGradient: 'from-blue-500 via-cyan-600 to-teal-600',
      badgeBorder: 'border-blue-400',
      effects: '',
      icon: '🔱',
      auraColor: 'bg-blue-400/25'
    };
  }
  
  // 200冊超え（ランク4-5）- 知識の使徒
  if (rank >= 4) {
    return {
      bgGradient: 'from-emerald-500 via-green-600 to-teal-700',
      borderColor: 'border-emerald-400/60',
      shadowColor: 'shadow-emerald-500/30',
      textColor: 'text-emerald-100',
      badgeGradient: 'from-emerald-500 via-green-600 to-teal-600',
      badgeBorder: 'border-emerald-400',
      effects: '',
      icon: '🛡️',
      auraColor: 'bg-emerald-400/25'
    };
  }
  
  // 100冊超え（ランク2-3）- 学問の徒（特別装飾）
  if (rank >= 2) {
    return {
      bgGradient: 'from-yellow-400 via-amber-500 to-orange-600',
      borderColor: 'border-yellow-300/70',
      shadowColor: 'shadow-yellow-500/40',
      textColor: 'text-yellow-100',
      badgeGradient: 'from-yellow-500 via-amber-500 to-orange-500',
      badgeBorder: 'border-yellow-400',
      effects: 'animate-pulse',
      icon: '⚡',
      auraColor: 'bg-yellow-400/30',
      sparkleEffect: true
    };
  }
  
  // 50冊超え（ランク1）- 読書の達人
  if (rank >= 1) {
    return {
      bgGradient: 'from-orange-500 via-red-600 to-pink-700',
      borderColor: 'border-orange-400/60',
      shadowColor: 'shadow-orange-500/30',
      textColor: 'text-orange-100',
      badgeGradient: 'from-orange-500 via-red-600 to-pink-600',
      badgeBorder: 'border-orange-400',
      effects: '',
      icon: '👑',
      auraColor: 'bg-orange-400/25'
    };
  }
  
  // 初心者ランク（10-49冊）
  return {
    bgGradient: 'from-slate-500 via-gray-600 to-slate-700',
    borderColor: 'border-slate-400/60',
    shadowColor: 'shadow-slate-500/30',
    textColor: 'text-slate-200',
    badgeGradient: 'from-slate-500 via-gray-600 to-slate-600',
    badgeBorder: 'border-slate-400',
    effects: '',
    icon: '🌱',
    auraColor: 'bg-slate-400/20'
  };
});

// NDC分類のジャンル定義（可愛いアイコン付き）
const ndcCategories = {
  '0': { name: '総記', icon: '📖', color: 'pink' },
  '1': { name: '哲学', icon: '🤔', color: 'purple' },
  '2': { name: '歴史', icon: '🏛️', color: 'amber' },
  '3': { name: '社会科学', icon: '👥', color: 'blue' },
  '4': { name: '自然科学', icon: '🔬', color: 'green' },
  '5': { name: '技術', icon: '⚙️', color: 'gray' },
  '6': { name: '産業', icon: '🏭', color: 'orange' },
  '7': { name: '芸術', icon: '🎨', color: 'red' },
  '8': { name: '言語', icon: '💬', color: 'cyan' },
  '9': { name: '文学', icon: '📚', color: 'indigo' }
};

// NDCジャンル達成度を計算
const getNDCAchievements = computed(() => {
  if (!selectedStudent.value?.ndc_achievements) {
    return {
      completedCategories: [],
      completedCount: 0,
      totalCategories: 10,
      completionRate: 0
    };
  }

  // バックエンドから送信されたndc_achievementsデータを使用
  const ndcAchievements = selectedStudent.value.ndc_achievements;
  const completedCategories = ndcAchievements.map(achievement => achievement.ndc.charAt(0));
  
  return {
    completedCategories,
    completedCount: ndcAchievements.length,
    totalCategories: 10,
    completionRate: Math.round((ndcAchievements.length / 10) * 100)
  };
});

// NDC達成アチーブメントの表示判定
const shouldShowNDCAchievement = computed(() => {
  return selectedStudent.value?.ndc_achievements?.length >= 1; // 1ジャンル以上で表示
});

// 生徒一覧の取得
const loadStudents = async (page = 1) => {
  try {
    loading.value = true;
    console.log('Loading students from API...');
    console.log('Current user permissions:', userPermissions.value);
    console.log('LocalStorage userPermissions:', localStorage.getItem('userPermissions'));
    
    // ローカルストレージから現在ログイン中の生徒情報を取得
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    console.log('Current student from localStorage:', currentStudent);
    
    // セッション認証のためのヘッダー設定を確認
    axios.defaults.withCredentials = true;
    
    const params = {
      page: page,
      per_page: pagination.value.per_page
    };
    
    // 利用者の場合は、現在ログイン中の生徒のemailをパラメータとして送信
    if (!userPermissions.value.isAdmin && currentStudent.email) {
      params.current_user_email = currentStudent.email;
      console.log('Non-admin user - sending current_user_email:', currentStudent.email);
    }
    
    // フィルター条件を追加
    if (filters.value.name) {
      params.search = filters.value.name;
      console.log('Adding search filter:', filters.value.name);
    }
    
    if (filters.value.gradeClass) {
      const [grade, className] = filters.value.gradeClass.split('-');
      params.grade = grade;
      params.class = className;
      console.log('Adding grade/class filters:', { grade, className });
    }
    
    if (filters.value.isbn) {
      params.isbn = filters.value.isbn;
      console.log('Adding ISBN filter:', filters.value.isbn);
    }
    
    console.log('Final request params:', params);
    const response = await axios.get('/api/students', { params });
    console.log('API Response:', response.data);
    console.log('Number of students returned:', response.data.data.length);
    students.value = response.data.data;
    pagination.value = response.data.pagination;
    console.log('Students loaded:', students.value.length);
  } catch (err) {
    console.error('Error loading students:', err);
    if (err.response) {
      console.error('Response status:', err.response.status);
      console.error('Response data:', err.response.data);
      if (err.response.status === 401) {
        error.value = '認証エラー: ログインし直してください';
      } else {
        error.value = err.response.data.message || '生徒情報の取得に失敗しました';
      }
    } else if (err.request) {
      error.value = 'サーバーに接続できませんでした';
    } else {
      error.value = '予期しないエラーが発生しました: ' + err.message;
    }
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

// ページネーション関数
const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadStudents(page);
  }
};

const nextPage = () => {
  if (pagination.value.current_page < pagination.value.last_page) {
    changePage(pagination.value.current_page + 1);
  }
};

const prevPage = () => {
  if (pagination.value.current_page > 1) {
    changePage(pagination.value.current_page - 1);
  }
};

// 表示するページ番号を計算
const getVisiblePages = () => {
  const current = pagination.value.current_page;
  const last = pagination.value.last_page;
  const delta = 2; // 現在ページの前後に表示するページ数
  
  if (last <= 7) {
    // ページ数が少ない場合は全て表示
    return Array.from({ length: last }, (_, i) => i + 1);
  }
  
  const pages = [];
  
  // 最初のページ
  if (current - delta > 1) {
    pages.push(1);
    if (current - delta > 2) {
      pages.push('...');
    }
  }
  
  // 現在ページ周辺
  for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
    pages.push(i);
  }
  
  // 最後のページ
  if (current + delta < last) {
    if (current + delta < last - 1) {
      pages.push('...');
    }
    pages.push(last);
  }
  
  return pages;
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
    
    // デバッグ：学生データとNDCアチーブメントの確認
    console.log('Selected Student:', student);
    console.log('NDC Achievements:', student.ndc_achievements);
    console.log('Should Show NDC Achievement:', student.ndc_achievements?.length >= 1);
    
    selectedStudent.value = {
      ...student,
      active_borrows: response.data.active_borrows,
      borrow_history: response.data.borrow_history,
      total_borrows_count: response.data.total_borrows_count
    };
    showBorrowModal.value = true;
  } catch (err) {
    error.value = '貸出履歴の取得に失敗しました';
    console.error(err);
  }
};

// 本の返却処理
const returnBook = async (borrow) => {
  console.log('返却処理開始:', borrow);
  try {
    processingReturn.value = true;
    console.log('返却API呼び出し:', `/api/borrows/${borrow.id}/return`);
    const response = await axios.patch(`/api/borrows/${borrow.id}/return`);
    console.log('返却API成功:', response.data);
    // 貸出履歴を再読み込み
    await showBorrowHistory(selectedStudent.value);
    // 選択状態をクリア
    selectedBorrows.value = [];
    console.log('返却処理完了');
  } catch (err) {
    error.value = '本の返却処理に失敗しました: ' + (err.response?.data?.message || err.message);
    console.error('返却処理エラー:', err);
    console.error('エラーレスポンス:', err.response?.data);
  } finally {
    processingReturn.value = false;
  }
};

// まとめて返却処理
const batchReturnBooks = async () => {
  if (selectedBorrows.value.length === 0) {
    error.value = '返却する本を選択してください';
    return;
  }

  try {
    processingReturn.value = true;
    const response = await axios.post('/api/borrows/batch-return', {
      borrow_ids: selectedBorrows.value
    });

    // 成功メッセージを表示
    if (response.data.message) {
      // 簡易的な成功通知（実際のプロジェクトでは適切な通知システムを使用）
      alert(response.data.message);
    }

    // 貸出履歴を再読み込み
    await showBorrowHistory(selectedStudent.value);
    // 選択状態をクリア
    selectedBorrows.value = [];
  } catch (err) {
    const errorMessage = err.response?.data?.message || 'まとめて返却処理に失敗しました';
    error.value = errorMessage;
    console.error(err);
  } finally {
    processingReturn.value = false;
  }
};

// 全選択/全解除のトグル
const toggleSelectAll = () => {
  if (selectedBorrows.value.length === selectedStudent.value?.active_borrows?.length) {
    // 全て選択されている場合は全解除
    selectedBorrows.value = [];
  } else {
    // 全選択
    selectedBorrows.value = selectedStudent.value?.active_borrows?.map(borrow => borrow.id) || [];
  }
};

// モーダルを閉じる
const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  showBorrowModal.value = false;
  selectedStudent.value = null;
  selectedBorrows.value = [];
  processingReturn.value = false;
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

// 返却期限日を過ぎているかチェック
const isOverdue = (dueDate) => {
  const due = new Date(dueDate);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  due.setHours(0, 0, 0, 0);
  return due < today;
};

// 期限切れまでの日数を計算
const getDaysUntilDue = (dueDate) => {
  const due = new Date(dueDate);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  due.setHours(0, 0, 0, 0);
  const diffTime = due - today;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays;
};

// コンポーネントのマウント時に生徒一覧を取得
onMounted(() => {
  loadPermissions();
  
  // メールアドレスが数字で始まるかチェック（管理者以外の利用者の場合）
  if (!userPermissions.value.isAdmin) {
    const student = JSON.parse(localStorage.getItem('student') || '{}')
    const email = student.email || ''
    
    // メールアドレスが数字以外で始まる場合、アクセス拒否
    if (!/^[0-9]/.test(email)) {
      error.value = 'この機能にアクセスする権限がありません'
      loading.value = false
      return
    }
  }
  
  loadStudents();
  loadGradeClasses();
});
</script>

<style scoped>
@keyframes spin-slow {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin-slow {
  animation: spin-slow 3s linear infinite;
}
</style>