import axios from '@/utils/axios';

export const notificationsApi = {
  getNotifications: (params = {}) => axios.get('/api/notifications', { params }),
  getUnreadCount: () => axios.get('/api/notifications/unread-count'),
  markAsRead: (id) => axios.post(`/api/notifications/${id}/read`),
  markAllAsRead: () => axios.post('/api/notifications/mark-all-read'),
};
