/**
 * Transport API Layer
 * Centralized API module for transport feature
 * 
 * Features:
 * - Real backend integration for READ operations
 * - Subscription request submission
 * - Student status endpoints
 */

import axios from '@/utils/axios';

/**
 * Transport API
 */
export const transportApi = {
  /**
   * Get all available routes from database.
   * Returns routes with stops and slots grouped by day.
   */
  async getRoutes() {
    try {
      const response = await axios.get('/api/transport/routes');
      return response.data;
    } catch (error) {
      console.error('transportApi.getRoutes error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load routes');
    }
  },

  /**
   * Get active payment methods from database.
   */
  async getPaymentMethods() {
    try {
      const response = await axios.get('/api/transport/payment-methods');
      return response.data;
    } catch (error) {
      console.error('transportApi.getPaymentMethods error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load payment methods');
    }
  },

  /**
   * Get transport settings (days/week, weeks/month, weeks/term).
   */
  async getSettings() {
    try {
      const response = await axios.get('/api/transport/settings');
      return response.data;
    } catch (error) {
      console.error('transportApi.getSettings error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load settings');
    }
  },

  /**
   * Get active transport plans.
   */
  async getPlans() {
    try {
      const response = await axios.get('/api/transport/plans');
      return response.data;
    } catch (error) {
      console.error('transportApi.getPlans error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load plans');
    }
  },

  /**
   * Submit subscription request with FormData.
   * @param {FormData} formData - FormData containing:
   *   route_id, day_of_week, time, plan_type, plan_id, selected_days,
   *   payment_method_id, paid_from_number, paid_at, amount_paid, proof (file)
   * @returns {Promise<object>} API response with created request data
   */
  async submitSubscriptionRequest(formData) {
    try {
      const response = await axios.post('/api/transport/subscription-requests', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
      return response.data;
    } catch (error) {
      console.error('transportApi.submitSubscriptionRequest error:', error);
      
      // Extract error details for UI
      const errorData = error.response?.data;
      const errorObj = new Error(errorData?.message || 'Failed to submit request');
      errorObj.status = error.response?.status;
      errorObj.errors = errorData?.errors; // Validation errors
      errorObj.data = errorData?.data; // Additional data if any
      throw errorObj;
    }
  },

  /**
   * Update (resubmit) a rejected subscription request.
   * @param {number} id - Request ID
   * @param {FormData} formData - Updated form data
   * @returns {Promise<object>} API response
   */
  async updateSubscriptionRequest(id, formData) {
    try {
      const response = await axios.post(`/api/transport/subscription-requests/${id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
      return response.data;
    } catch (error) {
      console.error('transportApi.updateSubscriptionRequest error:', error);
      
      const errorData = error.response?.data;
      const errorObj = new Error(errorData?.message || 'Failed to update request');
      errorObj.status = error.response?.status;
      errorObj.errors = errorData?.errors;
      throw errorObj;
    }
  },

  /**
   * Get student's subscription requests (paginated).
   * @returns {Promise<object>} API response with requests array
   */
  async getMyRequests() {
    try {
      const response = await axios.get('/api/transport/my-requests');
      return response.data;
    } catch (error) {
      console.error('transportApi.getMyRequests error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load requests');
    }
  },

  /**
   * Get student's active subscription.
   * @returns {Promise<object>} API response with subscription or null
   */
  async getMySubscription() {
    try {
      const response = await axios.get('/api/transport/my-subscription');
      return response.data;
    } catch (error) {
      console.error('transportApi.getMySubscription error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load subscription');
    }
  }
};
