<template>
  <div class="file-upload">
    <label v-if="label" :for="inputId" class="upload-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    
    <div class="upload-wrapper">
      <input
        :id="inputId"
        ref="fileInput"
        type="file"
        :accept="accept"
        :disabled="disabled"
        @change="handleFileChange"
        class="file-input"
      />
      
      <!-- Preview -->
      <div v-if="preview" class="preview-container">
        <img
          v-if="isImage"
          :src="preview"
          :alt="fileName || 'Preview'"
          class="preview-image"
        />
        <div v-else class="file-info">
          <FileIcon class="file-icon" />
          <span class="file-name">{{ fileName }}</span>
        </div>
        <button
          type="button"
          class="remove-btn"
          @click="removeFile"
          :disabled="disabled"
          aria-label="Remove file"
        >
          <X class="remove-icon" />
        </button>
      </div>
    </div>
    
    <span v-if="helpText" class="help-text">{{ helpText }}</span>
    <span v-if="error" class="error-text">{{ error }}</span>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { File as FileIcon, X } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: File,
    default: null
  },
  label: {
    type: String,
    default: ''
  },
  accept: {
    type: String,
    default: 'image/*'
  },
  maxSize: {
    type: Number,
    default: 5 * 1024 * 1024 // 5MB default
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  helpText: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'change', 'error']);

const fileInput = ref(null);
const preview = ref(null);
const fileName = ref('');

const inputId = computed(() => `file-upload-${Math.random().toString(36).substr(2, 9)}`);
const isImage = computed(() => props.accept.includes('image'));

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Validate file size
  if (file.size > props.maxSize) {
    const maxMB = (props.maxSize / (1024 * 1024)).toFixed(1);
    const errorMsg = `File size must be less than ${maxMB}MB`;
    emit('error', errorMsg);
    return;
  }

  // Set file name
  fileName.value = file.name;

  // Generate preview for images
  if (isImage.value) {
    const reader = new FileReader();
    reader.onload = (e) => {
      preview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    preview.value = file.name;
  }

  emit('update:modelValue', file);
  emit('change', file);
};

const removeFile = () => {
  preview.value = null;
  fileName.value = '';
  if (fileInput.value) {
    fileInput.value.value = '';
  }
  emit('update:modelValue', null);
  emit('change', null);
};

// Watch for external clear
watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    removeFile();
  }
});
</script>

<style scoped>
.file-upload {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.upload-label {
  font-weight: var(--fw-medium);
  color: var(--color-textMain);
  font-size: var(--font-base);
}

.required {
  color: var(--color-danger);
  margin-left: 2px;
}

.upload-wrapper {
  position: relative;
}

.file-input {
  width: 100%;
  padding: var(--spacing-md);
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-background);
  cursor: pointer;
  transition: all var(--transition-fast);
  font-size: var(--font-sm);
  color: var(--color-textSecondary);
}

.file-input:hover:not(:disabled) {
  border-color: var(--color-primary);
  background: var(--color-surfaceHighlight);
}

.file-input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.file-input:focus-visible {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-focusRing);
}

.preview-container {
  position: relative;
  margin-top: var(--spacing-md);
  display: inline-block;
  max-width: 100%;
}

.preview-image {
  max-width: 200px;
  max-height: 200px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  display: block;
}

.file-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--color-surfaceHighlight);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.file-icon {
  width: 20px;
  height: 20px;
  color: var(--color-primary);
}

.file-name {
  font-size: var(--font-sm);
  color: var(--color-textMain);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.remove-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--color-danger);
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform var(--transition-fast);
  box-shadow: var(--shadow-sm);
}

.remove-btn:hover:not(:disabled) {
  transform: scale(1.1);
}

.remove-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.remove-icon {
  width: 14px;
  height: 14px;
}

.help-text {
  font-size: var(--font-xs);
  color: var(--color-textMuted);
}

.error-text {
  font-size: var(--font-sm);
  color: var(--color-danger);
  font-weight: var(--fw-medium);
}
</style>
