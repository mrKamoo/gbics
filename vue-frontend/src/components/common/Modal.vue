<template>
  <teleport to="body">
    <transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeOnBackdrop && close()">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
          <div
            :class="[
              'relative bg-white rounded-lg shadow-xl transform transition-all',
              sizeClasses
            ]"
            @click.stop
          >
            <!-- Header -->
            <div v-if="!hideHeader" class="flex items-center justify-between p-6 border-b border-gray-200">
              <h3 class="text-xl font-semibold text-gray-900">
                <slot name="title">{{ title }}</slot>
              </h3>
              <button
                v-if="!hideClose"
                @click="close"
                class="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div :class="['p-6', bodyClass]">
              <slot></slot>
            </div>

            <!-- Footer -->
            <div v-if="!hideFooter" class="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
              <slot name="footer">
                <button
                  v-if="!hideCancelButton"
                  @click="close"
                  type="button"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  {{ cancelText }}
                </button>
                <button
                  v-if="!hideConfirmButton"
                  @click="confirm"
                  type="button"
                  :class="[
                    'px-4 py-2 text-sm font-medium text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2',
                    confirmButtonClass
                  ]"
                  :disabled="confirmDisabled"
                >
                  {{ confirmText }}
                </button>
              </slot>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    default: 'Modal'
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl', '2xl'].includes(value)
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  },
  hideHeader: {
    type: Boolean,
    default: false
  },
  hideFooter: {
    type: Boolean,
    default: false
  },
  hideClose: {
    type: Boolean,
    default: false
  },
  hideCancelButton: {
    type: Boolean,
    default: false
  },
  hideConfirmButton: {
    type: Boolean,
    default: false
  },
  cancelText: {
    type: String,
    default: 'Cancel'
  },
  confirmText: {
    type: String,
    default: 'Confirm'
  },
  confirmDisabled: {
    type: Boolean,
    default: false
  },
  confirmVariant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'danger', 'success', 'warning'].includes(value)
  },
  bodyClass: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'close', 'confirm'])

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'max-w-sm w-full',
    md: 'max-w-md w-full',
    lg: 'max-w-lg w-full',
    xl: 'max-w-xl w-full',
    '2xl': 'max-w-2xl w-full'
  }
  return sizes[props.size]
})

const confirmButtonClass = computed(() => {
  const variants = {
    primary: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 disabled:bg-blue-300',
    danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500 disabled:bg-red-300',
    success: 'bg-green-600 hover:bg-green-700 focus:ring-green-500 disabled:bg-green-300',
    warning: 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500 disabled:bg-yellow-300'
  }
  return variants[props.confirmVariant]
})

const close = () => {
  emit('update:modelValue', false)
  emit('close')
}

const confirm = () => {
  emit('confirm')
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.9);
  opacity: 0;
}
</style>
