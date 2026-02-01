/**
 * ID Card Services API Layer
 * Centralized API module for ID Card services feature
 */

import axios from '@/utils/axios';

/**
 * ID Card API
 */
export const idCardApi = {
  /**
   * Get all available ID card service types.
   * @returns {Promise<object>} API response with types array
   */
  async getTypes() {
    try {
      const response = await axios.get('/api/id-card/types');
      return response.data;
    } catch (error) {
      console.error('idCardApi.getTypes error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load service types');
    }
  },

  /**
   * Get ID card service settings (payment destination).
   * @returns {Promise<object>} API response with settings
   */
  async getSettings() {
    try {
      const response = await axios.get('/api/id-card/settings');
      return response.data;
    } catch (error) {
      console.error('idCardApi.getSettings error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load settings');
    }
  },

  /**
   * Submit a new ID card request.
   * @param {FormData} formData - FormData containing:
   *   type_code, transaction_number, transfer_time, transfer_screenshot, new_photo?, issue_description?
   * @returns {Promise<object>} API response with created request data
   */
  async submitRequest(formData) {
    try {
      const response = await axios.post('/api/id-card/requests', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
      return response.data;
    } catch (error) {
      console.error('idCardApi.submitRequest error:', error);
      
      // Extract error details for UI
      const errorData = error.response?.data;
      const errorObj = new Error(errorData?.message || 'Failed to submit request');
      errorObj.status = error.response?.status;
      errorObj.errors = errorData?.errors; // Validation errors
      throw errorObj;
    }
  },

  /**
   * Update (resubmit) a rejected ID card request.
   * @param {number} id - Request ID
   * @param {FormData} formData - Updated form data
   * @returns {Promise<object>} API response
   */
  async updateRequest(id, formData) {
    try {
      const response = await axios.post(`/api/id-card/requests/${id}`, formData, {
        headers: {
            'Content-Type': undefined
        }
      });
      return response.data;
    } catch (error) {
      console.error('idCardApi.updateRequest error:', error);
      
      const errorData = error.response?.data;
      const errorObj = new Error(errorData?.message || 'Failed to update request');
      errorObj.status = error.response?.status;
      errorObj.errors = errorData?.errors;
      throw errorObj;
    }
  },

  /**
   * Get student's ID card requests.
   * @returns {Promise<object>} API response with requests array
   */
  async getMyRequests() {
    try {
      const response = await axios.get('/api/id-card/my-requests');
      return response.data;
    } catch (error) {
      console.error('idCardApi.getMyRequests error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load requests');
    }
  },

  /**
   * Get a single ID card request by ID.
   * @param {number} id - Request ID
   * @returns {Promise<object>} API response with request data
   */
  async getRequest(id) {
    try {
      const response = await axios.get(`/api/id-card/my-requests/${id}`);
      return response.data;
    } catch (error) {
      console.error('idCardApi.getRequest error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load request');
    }
  }
};

/**
 * Unified Requests API (across all modules)
 */
export const unifiedRequestsApi = {
  /**
   * Get unified list of all requests for the student.
   * @returns {Promise<object>} API response with normalized requests array
   */
  async getAll() {
    try {
      const response = await axios.get('/api/student/my-requests');
      return response.data;
    } catch (error) {
      console.error('unifiedRequestsApi.getAll error:', error);
      throw new Error(error.response?.data?.message || 'Failed to load requests');
    }
  }
};
