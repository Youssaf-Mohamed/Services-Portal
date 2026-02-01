/**
 * Admin ID Card API Layer
 * API module for admin ID Card management
 */

import axios from '@/utils/axios';

const BASE_URL = '/api/admin/id-card';

/**
 * Admin ID Card API
 */
export const adminIdCardApi = {
  /**
   * Get dashboard statistics.
   */
  async getDashboard() {
    try {
      const response = await axios.get(`${BASE_URL}/dashboard`);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.getDashboard error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load dashboard');
    }
  },

  /**
   * Get paginated list of requests with filters.
   * @param {Object} params - Filter parameters
   */
  async getRequests(params = {}) {
    try {
      const response = await axios.get(`${BASE_URL}/requests`, { params });
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.getRequests error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load requests');
    }
  },

  /**
   * Get a single request with full details.
   * @param {number} id - Request ID
   */
  async getRequest(id) {
    try {
      const response = await axios.get(`${BASE_URL}/requests/${id}`);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.getRequest error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load request');
    }
  },

  /**
   * Get attachment URL.
   * @param {number} id - Request ID
   * @param {string} kind - 'screenshot' or 'new_photo'
   */
  getAttachmentUrl(id, kind) {
    return `${BASE_URL}/requests/${id}/attachments/${kind}`;
  },

  /**
   * Download attachment file securely (as blob).
   * @param {number} id - Request ID
   * @param {string} kind - 'screenshot' or 'new_photo'
   */
  async downloadAttachment(id, kind) {
    try {
      const response = await axios.get(`${BASE_URL}/requests/${id}/attachments/${kind}`, {
        responseType: 'blob'
      });
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.downloadAttachment error:', error);
      throw new Error(error.response?.data?.message || 'Failed to download attachment');
    }
  },

  /**
   * Verify payment for a request.
   * @param {number} id - Request ID
   */
  async verifyPayment(id) {
    try {
      const response = await axios.post(`${BASE_URL}/requests/${id}/verify-payment`);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.verifyPayment error:', error);
      throw new Error(error.response?.data?.message || 'Failed to verify payment');
    }
  },

  /**
   * Flag payment for a request.
   * @param {number} id - Request ID
   * @param {string} reason - Flag reason
   */
  async flagPayment(id, reason) {
    try {
      const response = await axios.post(`${BASE_URL}/requests/${id}/flag-payment`, { reason });
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.flagPayment error:', error);
      throw new Error(error.response?.data?.message || 'Failed to flag payment');
    }
  },

  /**
   * Approve a request.
   * @param {number} id - Request ID
   */
  async approve(id) {
    try {
      const response = await axios.post(`${BASE_URL}/requests/${id}/approve`);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.approve error:', error);
      throw new Error(error.response?.data?.message || 'Failed to approve request');
    }
  },

  /**
   * Reject a request.
   * @param {number} id - Request ID
   * @param {string} rejectionReason - Rejection reason
   */
  async reject(id, rejectionReason) {
    try {
      const response = await axios.post(`${BASE_URL}/requests/${id}/reject`, {
        rejection_reason: rejectionReason
      });
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.reject error:', error);
      throw new Error(error.response?.data?.message || 'Failed to reject request');
    }
  },

  /**
   * Mark request as ready for pickup.
   * @param {number} id - Request ID
   */
  async markReady(id) {
    try {
      const response = await axios.post(`${BASE_URL}/requests/${id}/ready`);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.markReady error:', error);
      throw new Error(error.response?.data?.message || 'Failed to mark as ready');
    }
  },

  /**
   * Mark request as delivered.
   * @param {number} id - Request ID
   */
  async markDelivered(id) {
    try {
      const response = await axios.post(`${BASE_URL}/requests/${id}/deliver`);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.markDelivered error:', error);
      throw new Error(error.response?.data?.message || 'Failed to mark as delivered');
    }
  },

  /**
   * Get settings.
   */
  async getSettings() {
    try {
      const response = await axios.get(`${BASE_URL}/settings`);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.getSettings error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load settings');
    }
  },

  /**
   * Update settings.
   * @param {Object} data - Settings data
   */
  async updateSettings(data) {
    try {
      const response = await axios.put(`${BASE_URL}/settings`, data);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.updateSettings error:', error);
      const errorObj = new Error(error.response?.data?.message || 'Failed to update settings');
      errorObj.errors = error.response?.data?.errors;
      throw errorObj;
    }
  },

  /**
   * Get payment methods.
   */
  async getPaymentMethods() {
    try {
      // Pass module=id_card to filter
      const response = await axios.get(`${BASE_URL}/payment-methods`, { params: { module: 'id_card' } });
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.getPaymentMethods error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load payment methods');
    }
  },

  /**
   * Create payment method.
   * @param {Object} data 
   */
  async createPaymentMethod(data) {
    try {
      const response = await axios.post(`${BASE_URL}/payment-methods`, { ...data, module: 'id_card' });
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.createPaymentMethod error:', error);
      throw new Error(error.response?.data?.message || 'Failed to create payment method');
    }
  },

  /**
   * Update payment method.
   * @param {number|string} id 
   * @param {Object} data 
   */
  async updatePaymentMethod(id, data) {
    try {
      const response = await axios.put(`${BASE_URL}/payment-methods/${id}`, data);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.updatePaymentMethod error:', error);
      throw new Error(error.response?.data?.message || 'Failed to update payment method');
    }
  },

  /**
   * Delete payment method.
   * @param {number|string} id 
   */
  async deletePaymentMethod(id) {
    try {
      const response = await axios.delete(`${BASE_URL}/payment-methods/${id}`);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.deletePaymentMethod error:', error);
      throw new Error(error.response?.data?.message || 'Failed to delete payment method');
    }
  },

  /**
   * Get ID card types.
   */
  async getTypes() {
    try {
      const response = await axios.get(`${BASE_URL}/types`);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.getTypes error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load types');
    }
  },

  /**
   * Create ID card type.
   * @param {Object} data
   */
  async createType(data) {
    try {
      const response = await axios.post(`${BASE_URL}/types`, data);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.createType error:', error);
      const errorObj = new Error(error.response?.data?.message || 'Failed to create type');
      errorObj.errors = error.response?.data?.errors;
      throw errorObj;
    }
  },

  /**
   * Update ID card type.
   * @param {number|string} id
   * @param {Object} data
   */
  async updateType(id, data) {
    try {
      const response = await axios.put(`${BASE_URL}/types/${id}`, data);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.updateType error:', error);
      const errorObj = new Error(error.response?.data?.message || 'Failed to update type');
      errorObj.errors = error.response?.data?.errors;
      throw errorObj;
    }
  },

  /**
   * Delete ID card type.
   * @param {number|string} id
   */
  async deleteType(id) {
    try {
      const response = await axios.delete(`${BASE_URL}/types/${id}`);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.deleteType error:', error);
      throw new Error(error.response?.data?.message || 'Failed to delete type');
    }
  },

  /**
   * Toggle ID card type active status.
   * @param {number|string} id
   */
  async toggleTypeActive(id) {
    try {
      const response = await axios.post(`${BASE_URL}/types/${id}/toggle-active`);
      return response.data;
    } catch (error) {
      console.error('adminIdCardApi.toggleTypeActive error:', error);
      throw new Error(error.response?.data?.message || 'Failed to toggle type status');
    }
  }
};
