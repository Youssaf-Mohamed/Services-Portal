import axios from '@/utils/axios';

/**
 * Admin Transport API Layer
 * Centralizes all admin transport management API calls
 */
export const adminTransportApi = {
  // Dashboard
  getDashboard: () => axios.get('/api/admin/transport/dashboard'),

  // Requests
  getRequests: (params = {}) => axios.get('/api/admin/transport/requests', { params }),
  getRequest: (id) => axios.get(`/api/admin/transport/requests/${id}`),
  approveRequest: (id, payload = {}) => axios.post(`/api/admin/transport/requests/${id}/approve`, payload),
  rejectRequest(id, reason) {
    return axios.post(`/api/admin/transport/requests/${id}/reject`, {
      rejection_reason: reason
    });
  },
  
  // Payment Verification
  verifyPayment: (id) => axios.post(`/api/admin/transport/requests/${id}/verify-payment`),
  flagPayment: (id, reason) => axios.post(`/api/admin/transport/requests/${id}/flag-payment`, { reason }),

  /**
   * Bulk approve requests.
   * @param {Array<number>} ids
   * @param {string|null} startDate
   */
  bulkApprove(ids, startDate = null) {
      return axios.post('/api/admin/transport/requests/bulk-approve', {
          ids,
          start_date: startDate
      });
  },

  /**
   * Bulk reject requests.
   * @param {Array<number>} ids
   * @param {string} reason
   */
  bulkReject(ids, reason) {
      return axios.post('/api/admin/transport/requests/bulk-reject', {
          ids,
          rejection_reason: reason
      });
  },

  /**
   * Download proof file securely.
   */
  getProofUrl: (id) => `/api/admin/transport/requests/${id}/proof`,
  downloadProof: (id) => axios.get(`/api/admin/transport/requests/${id}/proof`, { responseType: 'arraybuffer' }),

  // Routes
  getRoutes: (params = {}) => axios.get('/api/admin/transport/routes', { params }),
  getRoute: (id) => axios.get(`/api/admin/transport/routes/${id}`),
  createRoute: (payload) => axios.post('/api/admin/transport/routes', payload),
  updateRoute: (id, payload) => axios.put(`/api/admin/transport/routes/${id}`, payload),
  updateStops: (routeId, payload) => axios.put(`/api/admin/transport/routes/${routeId}/stops`, payload),

  // Slots
  getSlots: (params = {}) => axios.get('/api/admin/transport/slots', { params }),
  createSlot: (payload) => axios.post('/api/admin/transport/slots', payload),
  updateSlot: (id, payload) => axios.put(`/api/admin/transport/slots/${id}`, payload),

  // Stops
  getStops: (params = {}) => axios.get('/api/admin/transport/stops', { params }),
  createStop: (payload) => axios.post('/api/admin/transport/stops', payload),
  updateStop: (id, payload) => axios.put(`/api/admin/transport/stops/${id}`, payload),
  deleteStop: (id) => axios.delete(`/api/admin/transport/stops/${id}`),

  // Manifest
  getManifest: (params) => axios.get('/api/admin/transport/manifest', { params }),
  getManifestExportUrl: (params) => {
    const queryString = new URLSearchParams(params).toString();
    return `/api/admin/transport/manifest/export?${queryString}`;
  },
  
  // Reports
  getActiveSubscriptionsReportUrl: () => '/api/admin/transport/reports/active-subscriptions',
  getWaitlistReportUrl: () => '/api/admin/transport/reports/waitlist',

  // Settings
  getSettings: () => axios.get('/api/admin/transport/settings'),
  updateSettings: (payload) => axios.put('/api/admin/transport/settings', payload),

  // Payment Methods
  getPaymentMethods: () => axios.get('/api/admin/transport/payment-methods'),
  createPaymentMethod: (payload) => axios.post('/api/admin/transport/payment-methods', payload),
  updatePaymentMethod: (id, payload) => axios.put(`/api/admin/transport/payment-methods/${id}`, payload),
  deletePaymentMethod: (id) => axios.delete(`/api/admin/transport/payment-methods/${id}`),
};

export default adminTransportApi;
