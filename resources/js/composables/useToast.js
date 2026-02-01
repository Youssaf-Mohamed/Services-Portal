import { ref, readonly } from 'vue';

/**
 * Toast notification composable
 * Usage: const { showToast, toasts } = useToast();
 */

const toasts = ref([]);
let toastId = 0;

export function useToast() {
  /**
   * Show a toast notification
   * @param {string} message - Toast message
   * @param {string} type - 'success' | 'error' | 'warning' | 'info'
   * @param {number} duration - Duration in ms (default 4000, 0 = no auto-close)
   */
  const showToast = (message, type = 'info', duration = 4000) => {
    const id = ++toastId;
    
    toasts.value.push({
      id,
      message,
      type,
      visible: true,
    });

    if (duration > 0) {
      setTimeout(() => {
        dismissToast(id);
      }, duration);
    }

    return id;
  };

  const dismissToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index !== -1) {
      toasts.value.splice(index, 1);
    }
  };

  // Convenience methods
  const success = (message, duration) => showToast(message, 'success', duration);
  const error = (message, duration) => showToast(message, 'error', duration || 6000);
  const warning = (message, duration) => showToast(message, 'warning', duration);
  const info = (message, duration) => showToast(message, 'info', duration);

  return {
    toasts: readonly(toasts),
    showToast,
    dismissToast,
    success,
    error,
    warning,
    info,
  };
}
