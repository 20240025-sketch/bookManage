<template>
  <div class="notifications-page">
    <div class="bg-white rounded-lg shadow-md p-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">📬 通知</h1>
        <button 
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition"
        >
          すべて既読にする
        </button>
      </div>

      <!-- 未読数表示 -->
      <div v-if="unreadCount > 0" class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <p class="text-blue-800">
          <span class="font-bold">{{ unreadCount }}</span> 件の未読通知があります
        </p>
      </div>

      <!-- 通知一覧 -->
      <div v-if="notifications.length > 0" class="space-y-3">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="[
            'p-4 rounded-lg border transition cursor-pointer',
            notification.is_read 
              ? 'bg-gray-50 border-gray-200' 
              : 'bg-blue-50 border-blue-300'
          ]"
          @click="markAsRead(notification)"
        >
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <!-- デバッグ表示 -->
              <div class="bg-red-100 border border-red-300 p-2 mb-2 text-xs">
                <p><strong>DEBUG:</strong></p>
                <p>ID: {{ notification.id }}</p>
                <p>Message: "{{ notification.message }}"</p>
                <p>Message exists: {{ !!notification.message }}</p>
                <p>Message length: {{ notification.message ? notification.message.length : 0 }}</p>
                <p>Book exists: {{ !!notification.book }}</p>
                <p>JSON: {{ JSON.stringify(notification).substring(0, 200) }}</p>
              </div>
              
              <div class="flex items-center gap-2 mb-3">
                <span v-if="!notification.is_read" class="text-blue-600 font-bold text-lg">●</span>
                <p class="font-bold text-lg" style="color: #000000 !important; font-size: 18px;">
                  {{ notification.message || '[メッセージなし]' }}
                </p>
              </div>
              
              <div v-if="notification.book" class="ml-6 space-y-2" style="color: #000000 !important;">
                <p class="text-base"><span class="font-bold">📚 書籍名:</span> {{ notification.book.title }}</p>
                <p v-if="notification.book.author" class="text-base">
                  <span class="font-bold">✍️ 著者:</span> {{ notification.book.author }}
                </p>
                <p v-if="notification.book.isbn" class="text-base">
                  <span class="font-bold">🔢 ISBN:</span> {{ notification.book.isbn }}
                </p>
              </div>

              <p class="text-sm mt-3" style="color: #666666 !important;">
                ⏰ {{ formatDate(notification.created_at) }}
              </p>
            </div>

            <button
              @click.stop="deleteNotification(notification)"
              class="text-red-500 hover:text-red-700 ml-4 text-xl font-bold"
              title="削除"
            >
              ✕
            </button>
          </div>
        </div>
      </div>

      <!-- 通知がない場合 -->
      <div v-else class="text-center py-12 text-gray-500">
        <p class="text-lg">通知はありません</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const notifications = ref([]);

const unreadCount = computed(() => {
  return notifications.value.filter(n => !n.is_read).length;
});

onMounted(() => {
  fetchNotifications();
});

const fetchNotifications = async () => {
  try {
    const response = await axios.get('/api/notifications');
    console.log('📬 通知データ（完全）:', JSON.stringify(response.data, null, 2));
    console.log('📬 通知リスト:', response.data.data);
    notifications.value = response.data.data;
    
    // デバッグ: 各通知の詳細を確認
    notifications.value.forEach((n, index) => {
      console.log(`通知 ${index + 1}:`, JSON.stringify({
        id: n.id,
        message: n.message,
        message_length: n.message ? n.message.length : 0,
        is_read: n.is_read,
        book: n.book,
        book_request: n.book_request,
        created_at: n.created_at
      }, null, 2));
    });
  } catch (error) {
    console.error('通知取得エラー:', error);
    console.error('エラー詳細:', error.response?.data);
  }
};

const markAsRead = async (notification) => {
  if (notification.is_read) return;

  try {
    await axios.patch(`/api/notifications/${notification.id}/read`);
    notification.is_read = true;
  } catch (error) {
    console.error('既読処理エラー:', error);
  }
};

const markAllAsRead = async () => {
  try {
    await axios.patch('/api/notifications/mark-all-read');
    notifications.value.forEach(n => n.is_read = true);
  } catch (error) {
    console.error('一括既読処理エラー:', error);
  }
};

const deleteNotification = async (notification) => {
  if (!confirm('この通知を削除しますか?')) return;

  try {
    await axios.delete(`/api/notifications/${notification.id}`);
    notifications.value = notifications.value.filter(n => n.id !== notification.id);
  } catch (error) {
    console.error('通知削除エラー:', error);
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diff = now - date;
  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(diff / 3600000);
  const days = Math.floor(diff / 86400000);

  if (minutes < 1) return 'たった今';
  if (minutes < 60) return `${minutes}分前`;
  if (hours < 24) return `${hours}時間前`;
  if (days < 7) return `${days}日前`;
  
  return date.toLocaleDateString('ja-JP', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
};
</script>

<style scoped>
.notifications-page {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}
</style>
