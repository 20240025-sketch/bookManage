<template>
  <div class="container mx-auto px-4 pb-8">
    <!-- ヘッダー -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">図書当番</h1>
          <p class="mt-1 text-sm text-gray-600">
            図書当番の記録を管理します（昼休み・放課後は別々に管理されます）
          </p>
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
      <!-- 本日の記録：昼休み -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <span class="text-2xl mr-2">🍱</span>
          本日の記録：昼休み（{{ formatDate(todayLunchDuty.duty_date) }}）
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <!-- 生徒利用者数 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              生徒利用者数 *
            </label>
            <input
              v-model.number="todayLunchDuty.student_visitor_count"
              type="number"
              min="0"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="0"
            />
          </div>
          
          <!-- 教員利用者数 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              教員利用者数 *
            </label>
            <input
              v-model.number="todayLunchDuty.teacher_visitor_count"
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
              {{ todayLunchDuty.borrow_count }}人
            </div>
          </div>
        </div>
        
        <!-- 担当者入力 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              担当者1
            </label>
            <input
              v-model="todayLunchDuty.student_name_1"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="担当者名を入力"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              学年1
            </label>
            <input
              v-model="todayLunchDuty.grade_1"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="例：1年"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              クラス1
            </label>
            <input
              v-model="todayLunchDuty.class_1"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="例：A"
            />
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              担当者2
            </label>
            <input
              v-model="todayLunchDuty.student_name_2"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="担当者名を入力"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              学年2
            </label>
            <input
              v-model="todayLunchDuty.grade_2"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="例：1年"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              クラス2
            </label>
            <input
              v-model="todayLunchDuty.class_2"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="例：A"
            />
          </div>
        </div>
        
        <!-- ふりかえり -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            ふりかえり
          </label>
          <textarea
            v-model="todayLunchDuty.reflection"
            rows="3"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="今日の振り返りを入力してください..."
          ></textarea>
        </div>
        
        <!-- 保存ボタン -->
        <div class="flex justify-end">
          <button
            @click="saveLunchDuty"
            :disabled="saving === 'lunch'"
            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md disabled:opacity-50"
          >
            {{ saving === 'lunch' ? '保存中...' : '保存' }}
          </button>
        </div>
      </div>

      <!-- 本日の記録：放課後 -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <span class="text-2xl mr-2">🌆</span>
          本日の記録：放課後（{{ formatDate(todayAfterSchoolDuty.duty_date) }}）
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <!-- 生徒利用者数 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              生徒利用者数 *
            </label>
            <input
              v-model.number="todayAfterSchoolDuty.student_visitor_count"
              type="number"
              min="0"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="0"
            />
          </div>
          
          <!-- 教員利用者数 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              教員利用者数 *
            </label>
            <input
              v-model.number="todayAfterSchoolDuty.teacher_visitor_count"
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
              {{ todayAfterSchoolDuty.borrow_count }}人
            </div>
          </div>
        </div>
        
        <!-- 担当者入力 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              担当者1
            </label>
            <input
              v-model="todayAfterSchoolDuty.student_name_1"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="担当者名を入力"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              学年1
            </label>
            <input
              v-model="todayAfterSchoolDuty.grade_1"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="例：1年"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              クラス1
            </label>
            <input
              v-model="todayAfterSchoolDuty.class_1"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="例：A"
            />
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              担当者2
            </label>
            <input
              v-model="todayAfterSchoolDuty.student_name_2"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="担当者名を入力"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              学年2
            </label>
            <input
              v-model="todayAfterSchoolDuty.grade_2"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="例：1年"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              クラス2
            </label>
            <input
              v-model="todayAfterSchoolDuty.class_2"
              type="text"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="例：A"
            />
          </div>
        </div>
        
        <!-- ふりかえり -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            ふりかえり
          </label>
          <textarea
            v-model="todayAfterSchoolDuty.reflection"
            rows="3"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="今日の振り返りを入力してください..."
          ></textarea>
        </div>
        
        <!-- 保存ボタン -->
        <div class="flex justify-end">
          <button
            @click="saveAfterSchoolDuty"
            :disabled="saving === 'after_school'"
            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md disabled:opacity-50"
          >
            {{ saving === 'after_school' ? '保存中...' : '保存' }}
          </button>
        </div>
      </div>

      <!-- 過去の記録：昼休み -->
      <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center">
            <span class="text-xl mr-2">🍱</span>
            過去の記録：昼休み
          </h2>
          <button
            @click="exportPdf('lunch')"
            :disabled="loading"
            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm disabled:opacity-50"
          >
            PDF出力
          </button>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  日付
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  生徒利用者
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  教員利用者
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  操作
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="duty in lunchDuties" :key="duty.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatDate(duty.duty_date) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ duty.student_visitor_count }}人
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ duty.teacher_visitor_count }}人
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
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                  <button
                    @click="editDuty(duty)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    編集
                  </button>
                  <button
                    @click="confirmDelete(duty)"
                    class="text-red-600 hover:text-red-900"
                  >
                    削除
                  </button>
                </td>
              </tr>
              <tr v-if="lunchDuties.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                  過去の記録がありません
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 過去の記録：放課後 -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center">
            <span class="text-xl mr-2">🌆</span>
            過去の記録：放課後
          </h2>
          <button
            @click="exportPdf('after_school')"
            :disabled="loading"
            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm disabled:opacity-50"
          >
            PDF出力
          </button>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  日付
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  生徒利用者
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  教員利用者
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  操作
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="duty in afterSchoolDuties" :key="duty.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatDate(duty.duty_date) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ duty.student_visitor_count }}人
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ duty.teacher_visitor_count }}人
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
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                  <button
                    @click="editDuty(duty)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    編集
                  </button>
                  <button
                    @click="confirmDelete(duty)"
                    class="text-red-600 hover:text-red-900"
                  >
                    削除
                  </button>
                </td>
              </tr>
              <tr v-if="afterSchoolDuties.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                  過去の記録がありません
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
    
    <!-- 編集モーダル -->
    <div v-if="editingDuty" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">記録の編集</h3>
          <p class="text-sm text-gray-600 mt-1">{{ formatDate(editingDuty.duty_date) }} - {{ editingDuty.shift_type === 'lunch' ? '🍱 昼休み' : '🌆 放課後' }}</p>
        </div>
        
        <div class="px-6 py-4 space-y-4">
          <!-- 利用者数 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              利用者数 *
            </label>
            <input
              v-model.number="editingDuty.visitor_count"
              type="number"
              min="0"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>
          
          <!-- 貸出人数 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              貸出人数（自動計算）
            </label>
            <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-900">
              {{ editingDuty.borrow_count }}人
            </div>
          </div>
          
          <!-- 生徒利用者数と教員利用者数 -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                生徒利用者数
              </label>
              <input
                v-model.number="editingDuty.student_visitor_count"
                type="number"
                min="0"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                教員利用者数
              </label>
              <input
                v-model.number="editingDuty.teacher_visitor_count"
                type="number"
                min="0"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
          </div>
          
          <!-- 担当者1 -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                担当者1
              </label>
              <input
                v-model="editingDuty.student_name_1"
                type="text"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                学年1
              </label>
              <input
                v-model="editingDuty.grade_1"
                type="text"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="例：1年"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                クラス1
              </label>
              <input
                v-model="editingDuty.class_1"
                type="text"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="例：A"
              />
            </div>
          </div>
          
          <!-- 担当者2 -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                担当者2
              </label>
              <input
                v-model="editingDuty.student_name_2"
                type="text"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                学年2
              </label>
              <input
                v-model="editingDuty.grade_2"
                type="text"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="例：1年"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                クラス2
              </label>
              <input
                v-model="editingDuty.class_2"
                type="text"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="例：A"
              />
            </div>
          </div>
          
          <!-- ふりかえり -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              ふりかえり
            </label>
            <textarea
              v-model="editingDuty.reflection"
              rows="4"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            ></textarea>
          </div>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
          <button
            @click="cancelEdit"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            キャンセル
          </button>
          <button
            @click="saveEdit"
            :disabled="saving"
            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50"
          >
            {{ saving ? '保存中...' : '保存' }}
          </button>
        </div>
      </div>
    </div>
    
    <!-- 削除確認ダイアログ -->
    <div v-if="deletingDuty" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="px-6 py-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-2">記録の削除</h3>
          <p class="text-sm text-gray-600">
            {{ formatDate(deletingDuty.duty_date) }}（{{ deletingDuty.shift_type === 'lunch' ? '昼休み' : '放課後' }}）の記録を削除してもよろしいですか？
          </p>
          <p class="text-sm text-red-600 mt-2">
            この操作は取り消せません。
          </p>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
          <button
            @click="cancelDelete"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            キャンセル
          </button>
          <button
            @click="deleteDuty"
            :disabled="deleting"
            class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 disabled:opacity-50"
          >
            {{ deleting ? '削除中...' : '削除' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';

const loading = ref(true);
const saving = ref(null); // 'lunch' | 'after_school' | null
const deleting = ref(false);
const error = ref('');
const successMessage = ref('');

// 編集・削除用の状態
const editingDuty = ref(null);
const deletingDuty = ref(null);

// 本日の記録（2つ保持：昼休みと放課後）
const todayLunchDuty = ref({
  id: null,
  duty_date: null,
  shift_type: 'lunch',
  visitor_count: 0,
  student_visitor_count: 0,
  teacher_visitor_count: 0,
  borrow_count: 0,
  reflection: '',
  student_name_1: '',
  student_name_2: '',
  grade_1: '',
  class_1: '',
  grade_2: '',
  class_2: ''
});

const todayAfterSchoolDuty = ref({
  id: null,
  duty_date: null,
  shift_type: 'after_school',
  visitor_count: 0,
  student_visitor_count: 0,
  teacher_visitor_count: 0,
  borrow_count: 0,
  reflection: '',
  student_name_1: '',
  student_name_2: '',
  grade_1: '',
  class_1: '',
  grade_2: '',
  class_2: ''
});

const duties = ref([]);

// 学年・クラス選択肢
const availableGradeClasses = ref([]);

// 昼休みのデータのみ（本日の編集用レコードは除外しない - 保存済みなら表示）
const lunchDuties = computed(() => {
  return duties.value.filter(duty => duty.shift_type === 'lunch');
});

// 放課後のデータのみ（本日の編集用レコードは除外しない - 保存済みなら表示）
const afterSchoolDuties = computed(() => {
  return duties.value.filter(duty => duty.shift_type === 'after_school');
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
      // 時間帯に応じて適切なrefに保存
      if (shiftType === 'lunch') {
        todayLunchDuty.value = response.data.data;
      } else {
        todayAfterSchoolDuty.value = response.data.data;
      }
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

// 過去の記録を取得（全件取得、ページネーションなし）
const loadDuties = async () => {
  try {
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    const params = {
      current_user_email: currentStudent.email,
      per_page: 1000 // 十分大きい数を指定して全件取得
    };
    
    const response = await axios.get('/api/library-duty', { params });
    
    if (response.data.success) {
      duties.value = response.data.data;
    }
  } catch (err) {
    console.error('Error loading duties:', err);
    error.value = err.response?.data?.message || '記録の取得に失敗しました';
  }
};

// 昼休みの記録を保存
const saveLunchDuty = async () => {
  try {
    saving.value = 'lunch';
    error.value = '';
    successMessage.value = '';
    
    // IDがnullの場合は再取得を試みる
    if (!todayLunchDuty.value.id) {
      console.warn('todayLunchDuty.id is null, reloading...');
      await loadTodayDuty('lunch');
      
      if (!todayLunchDuty.value.id) {
        error.value = '本日の昼休みの記録が見つかりません。ページを再読み込みしてください。';
        saving.value = null;
        return;
      }
    }
    
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    const data = {
      visitor_count: todayLunchDuty.value.visitor_count,
      reflection: todayLunchDuty.value.reflection,
      student_name_1: todayLunchDuty.value.student_name_1,
      student_name_2: todayLunchDuty.value.student_name_2,
      shift_type: 'lunch',
      current_user_email: currentStudent.email
    };
    
    const response = await axios.put(`/api/library-duty/${todayLunchDuty.value.id}`, data);
    
    if (response.data.success) {
      todayLunchDuty.value = response.data.data;
      successMessage.value = '昼休みの記録を保存しました';
      
      // 過去の記録も更新
      await loadDuties();
      
      setTimeout(() => {
        successMessage.value = '';
      }, 3000);
    }
  } catch (err) {
    console.error('Error saving lunch duty:', err);
    error.value = err.response?.data?.message || '昼休みの保存に失敗しました';
  } finally {
    saving.value = null;
  }
};

// 放課後の記録を保存
const saveAfterSchoolDuty = async () => {
  try {
    saving.value = 'after_school';
    error.value = '';
    successMessage.value = '';
    
    // IDがnullの場合は再取得を試みる
    if (!todayAfterSchoolDuty.value.id) {
      console.warn('todayAfterSchoolDuty.id is null, reloading...');
      await loadTodayDuty('after_school');
      
      if (!todayAfterSchoolDuty.value.id) {
        error.value = '本日の放課後の記録が見つかりません。ページを再読み込みしてください。';
        saving.value = null;
        return;
      }
    }
    
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    const data = {
      visitor_count: todayAfterSchoolDuty.value.visitor_count,
      reflection: todayAfterSchoolDuty.value.reflection,
      student_name_1: todayAfterSchoolDuty.value.student_name_1,
      student_name_2: todayAfterSchoolDuty.value.student_name_2,
      shift_type: 'after_school',
      current_user_email: currentStudent.email
    };
    
    const response = await axios.put(`/api/library-duty/${todayAfterSchoolDuty.value.id}`, data);
    
    if (response.data.success) {
      todayAfterSchoolDuty.value = response.data.data;
      successMessage.value = '放課後の記録を保存しました';
      
      // 過去の記録も更新
      await loadDuties();
      
      setTimeout(() => {
        successMessage.value = '';
      }, 3000);
    }
  } catch (err) {
    console.error('Error saving after school duty:', err);
    error.value = err.response?.data?.message || '放課後の保存に失敗しました';
  } finally {
    saving.value = null;
  }
};

// PDF出力（時間帯別）
const exportPdf = async (shiftType) => {
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
      end_date: endDate,
      shift_type: shiftType // 時間帯を追加
    });
    
    const response = await axios.get(`/api/library-duty/pdf?${params.toString()}`, {
      responseType: 'blob'
    });
    
    // PDFをダウンロード
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    const shiftLabel = shiftType === 'lunch' ? '昼休み' : '放課後';
    link.download = `図書当番記録_${shiftLabel}_${new Date().toLocaleDateString('ja-JP').replace(/\//g, '')}.pdf`;
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

// 編集開始
const editDuty = (duty) => {
  editingDuty.value = { ...duty };
};

// 編集キャンセル
const cancelEdit = () => {
  editingDuty.value = null;
};

// 編集保存
const saveEdit = async () => {
  try {
    saving.value = true;
    error.value = '';
    successMessage.value = '';
    
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    const data = {
      visitor_count: editingDuty.value.visitor_count,
      reflection: editingDuty.value.reflection,
      student_name_1: editingDuty.value.student_name_1,
      student_name_2: editingDuty.value.student_name_2,
      shift_type: editingDuty.value.shift_type,
      current_user_email: currentStudent.email
    };
    
    const response = await axios.put(`/api/library-duty/${editingDuty.value.id}`, data);
    
    if (response.data.success) {
      successMessage.value = '更新しました';
      editingDuty.value = null;
      
      // 記録を再読み込み
      const shiftType = response.data.data.shift_type;
      await loadTodayDuty(shiftType);
      await loadDuties();
      
      setTimeout(() => {
        successMessage.value = '';
      }, 3000);
    }
  } catch (err) {
    console.error('Error updating duty:', err);
    error.value = err.response?.data?.message || '更新に失敗しました';
  } finally {
    saving.value = false;
  }
};

// 削除確認
const confirmDelete = (duty) => {
  deletingDuty.value = duty;
};

// 削除キャンセル
const cancelDelete = () => {
  deletingDuty.value = null;
};

// 削除実行
const deleteDuty = async () => {
  try {
    deleting.value = true;
    error.value = '';
    successMessage.value = '';
    
    const currentStudent = JSON.parse(localStorage.getItem('student') || '{}');
    
    const response = await axios.delete(`/api/library-duty/${deletingDuty.value.id}`, {
      params: {
        current_user_email: currentStudent.email
      }
    });
    
    if (response.data.success) {
      successMessage.value = '削除しました';
      deletingDuty.value = null;
      
      // 記録を再読み込み
      await loadDuties();
      
      setTimeout(() => {
        successMessage.value = '';
      }, 3000);
    }
  } catch (err) {
    console.error('Error deleting duty:', err);
    error.value = err.response?.data?.message || '削除に失敗しました';
  } finally {
    deleting.value = false;
  }
};

// 学年・クラスの読み込み
const loadGradeClasses = async () => {
  try {
    const response = await axios.get('/api/students/classes');
    availableGradeClasses.value = response.data.data;
  } catch (err) {
    console.error('Error loading grade classes:', err);
  }
};

onMounted(async () => {
  loadPermissions();
  await loadGradeClasses();
  // 本日の両方の時間帯のデータをロード
  await Promise.all([
    loadTodayDuty('lunch'),
    loadTodayDuty('after_school')
  ]);
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
