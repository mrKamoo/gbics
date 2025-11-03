/**
 * Format date to locale string
 */
export function formatDate(date, options = {}) {
  if (!date) return ''
  const defaultOptions = {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    ...options
  }
  return new Date(date).toLocaleDateString('en-US', defaultOptions)
}

/**
 * Format date with time
 */
export function formatDateTime(date) {
  if (!date) return ''
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

/**
 * Get badge color class based on status
 */
export function getStatusBadgeClass(status) {
  const classes = {
    in_stock: 'badge-success',
    assigned: 'badge-info',
    deployed: 'badge-info',
    faulty: 'badge-danger',
    retired: 'badge-secondary',
    maintenance: 'badge-warning'
  }
  return classes[status] || 'badge-secondary'
}

/**
 * Debounce function
 */
export function debounce(fn, delay = 300) {
  let timeoutId
  return function (...args) {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn.apply(this, args), delay)
  }
}

/**
 * Copy text to clipboard
 */
export async function copyToClipboard(text) {
  try {
    await navigator.clipboard.writeText(text)
    return true
  } catch (err) {
    console.error('Failed to copy:', err)
    return false
  }
}

/**
 * Download file from blob
 */
export function downloadFile(blob, filename) {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

/**
 * Parse error response
 */
export function parseErrorMessage(error) {
  if (error.response) {
    if (error.response.data.message) {
      return error.response.data.message
    }
    if (error.response.data.errors) {
      const errors = error.response.data.errors
      return Object.values(errors).flat().join(', ')
    }
  }
  return error.message || 'An error occurred'
}
