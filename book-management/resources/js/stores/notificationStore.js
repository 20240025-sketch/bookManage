import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref([]);

  const addNotification = (notification) => {
    const id = Date.now();
    const newNotification = {
      id,
      title: notification.title,
      message: notification.message || '',
      type: notification.type || 'info',
      duration: notification.duration || 5000
    };

    notifications.value.push(newNotification);

    // 自動削除
    if (newNotification.duration > 0) {
      setTimeout(() => {
        removeNotification(id);
      }, newNotification.duration);
    }

    return id;
  };

  const removeNotification = (id) => {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index > -1) {
      notifications.value.splice(index, 1);
    }
  };

  const clearAll = () => {
    notifications.value = [];
  };

  return {
    notifications,
    addNotification,
    removeNotification,
    clearAll
  };
});
