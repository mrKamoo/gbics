<template>
  <div class="fscom-catalog-container">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">FS.com Catalog Management</h1>
        <p class="page-description">Import, export, and manage FS.com product catalog</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.total_products }}</div>
          <div class="stat-label">Total Products</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🔌</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.total_gbics }}</div>
          <div class="stat-label">GBICs</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🔗</div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.total_patch_cords }}</div>
          <div class="stat-label">Patch Cords</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🕒</div>
        <div class="stat-content">
          <div class="stat-value">{{ formatDate(stats.last_sync) }}</div>
          <div class="stat-label">Last Sync</div>
        </div>
      </div>
    </div>

    <!-- Actions Bar -->
    <div class="actions-bar">
      <div class="actions-left">
        <button @click="showImportModal = true" class="btn btn-primary">
          📥 Import CSV
        </button>
        <button @click="generateTemplate" class="btn btn-secondary" :disabled="isLoading">
          📄 Generate Template
        </button>
        <button @click="exportCatalog" class="btn btn-secondary" :disabled="isLoading">
          📤 Export All
        </button>
      </div>
      <div class="actions-right">
        <select v-model="filters.category" @change="loadProducts" class="filter-select">
          <option value="all">All Categories</option>
          <option value="gbic">GBICs</option>
          <option value="patch_cord">Patch Cords</option>
        </select>
        <input
          v-model="filters.search"
          @input="debouncedSearch"
          type="text"
          placeholder="Search products..."
          class="search-input"
        />
      </div>
    </div>

    <!-- Products Table -->
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>
              <input
                type="checkbox"
                @change="toggleSelectAll"
                :checked="allSelected"
              />
            </th>
            <th @click="sortBy('sku')" class="sortable">
              SKU {{ getSortIcon('sku') }}
            </th>
            <th @click="sortBy('name')" class="sortable">
              Name {{ getSortIcon('name') }}
            </th>
            <th>Category</th>
            <th>Price</th>
            <th>Last Updated</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="isLoading">
            <td colspan="7" class="loading-cell">Loading products...</td>
          </tr>
          <tr v-else-if="products.length === 0">
            <td colspan="7" class="empty-cell">No products found</td>
          </tr>
          <tr v-else v-for="product in products" :key="product.id">
            <td>
              <input
                type="checkbox"
                v-model="selectedProducts"
                :value="product.id"
              />
            </td>
            <td class="sku-cell">{{ product.sku }}</td>
            <td class="name-cell">{{ product.name }}</td>
            <td>
              <span class="category-badge" :class="`category-${product.category}`">
                {{ product.category }}
              </span>
            </td>
            <td>{{ formatPrice(product.price, product.currency) }}</td>
            <td class="date-cell">{{ formatDate(product.updated_at) }}</td>
            <td class="actions-cell">
              <button @click="viewProduct(product)" class="btn-icon" title="View">
                👁️
              </button>
              <button @click="deleteProduct(product.id)" class="btn-icon btn-danger" title="Delete">
                🗑️
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > 0" class="pagination">
      <button
        @click="goToPage(pagination.current_page - 1)"
        :disabled="pagination.current_page === 1"
        class="btn btn-sm"
      >
        Previous
      </button>
      <span class="pagination-info">
        Page {{ pagination.current_page }} of {{ pagination.last_page }}
        ({{ pagination.total }} total)
      </span>
      <button
        @click="goToPage(pagination.current_page + 1)"
        :disabled="pagination.current_page === pagination.last_page"
        class="btn btn-sm"
      >
        Next
      </button>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedProducts.length > 0" class="bulk-actions">
      <span>{{ selectedProducts.length }} product(s) selected</span>
      <button @click="bulkDelete" class="btn btn-danger">
        Delete Selected
      </button>
      <button @click="selectedProducts = []" class="btn btn-secondary">
        Clear Selection
      </button>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="modal-overlay" @click.self="closeImportModal">
      <div class="modal">
        <div class="modal-header">
          <h2>Import CSV File</h2>
          <button @click="closeImportModal" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <!-- Step 1: Upload File -->
          <div v-if="importStep === 1" class="import-step">
            <h3>Step 1: Select CSV File</h3>
            <div class="file-upload">
              <input
                ref="fileInput"
                type="file"
                accept=".csv,.txt"
                @change="handleFileSelect"
                class="file-input"
              />
              <div v-if="!selectedFile" class="file-upload-placeholder">
                <p>📁 Click to select a CSV file</p>
                <p class="text-muted">Maximum file size: 10MB</p>
              </div>
              <div v-else class="file-selected">
                <p>📄 {{ selectedFile.name }}</p>
                <p class="text-muted">{{ formatFileSize(selectedFile.size) }}</p>
              </div>
            </div>
            <div class="modal-actions">
              <button @click="closeImportModal" class="btn btn-secondary">
                Cancel
              </button>
              <button
                @click="validateFile"
                :disabled="!selectedFile || isValidating"
                class="btn btn-primary"
              >
                {{ isValidating ? 'Validating...' : 'Next: Validate' }}
              </button>
            </div>
          </div>

          <!-- Step 2: Validation Results -->
          <div v-if="importStep === 2" class="import-step">
            <h3>Step 2: Validation Results</h3>
            <div class="validation-results">
              <div class="stats-summary">
                <div class="stat-item">
                  <span class="stat-label">Total Records:</span>
                  <span class="stat-value">{{ validationResult.stats.total }}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Valid Records:</span>
                  <span class="stat-value text-success">
                    {{ validationResult.stats.total - validationResult.stats.errors }}
                  </span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Errors:</span>
                  <span class="stat-value text-error">
                    {{ validationResult.stats.errors }}
                  </span>
                </div>
              </div>

              <div v-if="validationResult.has_errors" class="errors-list">
                <h4>Validation Errors:</h4>
                <div v-for="(error, index) in validationResult.errors" :key="index" class="error-item">
                  <strong>Line {{ error.line }}:</strong>
                  <ul>
                    <li v-for="(msg, i) in error.errors" :key="i">{{ msg }}</li>
                  </ul>
                </div>
              </div>

              <div v-if="!validationResult.has_errors" class="success-message">
                ✅ All records are valid and ready to import!
              </div>
            </div>

            <div class="import-options">
              <label class="checkbox-label">
                <input type="checkbox" v-model="importOptions.stopOnError" />
                Stop import on first error (default: skip errors)
              </label>
            </div>

            <div class="modal-actions">
              <button @click="importStep = 1" class="btn btn-secondary">
                Back
              </button>
              <button
                @click="performImport"
                :disabled="isImporting"
                class="btn btn-primary"
              >
                {{ isImporting ? 'Importing...' : 'Import Products' }}
              </button>
            </div>
          </div>

          <!-- Step 3: Import Results -->
          <div v-if="importStep === 3" class="import-step">
            <h3>Import Complete</h3>
            <div class="import-results">
              <div class="stats-summary">
                <div class="stat-item">
                  <span class="stat-label">Created:</span>
                  <span class="stat-value text-success">
                    {{ importResult.stats.created }}
                  </span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Updated:</span>
                  <span class="stat-value text-info">
                    {{ importResult.stats.updated }}
                  </span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Errors:</span>
                  <span class="stat-value text-error">
                    {{ importResult.stats.errors }}
                  </span>
                </div>
              </div>

              <div v-if="importResult.has_errors" class="errors-list">
                <h4>Import Errors:</h4>
                <div v-for="(error, index) in importResult.errors" :key="index" class="error-item">
                  <strong>Line {{ error.line }}:</strong>
                  <ul>
                    <li v-for="(msg, i) in error.errors" :key="i">{{ msg }}</li>
                  </ul>
                </div>
              </div>

              <div v-else class="success-message">
                ✅ Import completed successfully!
              </div>
            </div>

            <div class="modal-actions">
              <button @click="finishImport" class="btn btn-primary">
                Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Product Details Modal -->
    <div v-if="showProductModal" class="modal-overlay" @click.self="showProductModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Product Details</h2>
          <button @click="showProductModal = false" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <div v-if="currentProduct" class="product-details">
            <div class="detail-row">
              <strong>SKU:</strong>
              <span>{{ currentProduct.sku }}</span>
            </div>
            <div class="detail-row">
              <strong>Name:</strong>
              <span>{{ currentProduct.name }}</span>
            </div>
            <div class="detail-row">
              <strong>Category:</strong>
              <span class="category-badge" :class="`category-${currentProduct.category}`">
                {{ currentProduct.category }}
              </span>
            </div>
            <div class="detail-row">
              <strong>Description:</strong>
              <span>{{ currentProduct.description || 'N/A' }}</span>
            </div>
            <div class="detail-row">
              <strong>Price:</strong>
              <span>{{ formatPrice(currentProduct.price, currentProduct.currency) }}</span>
            </div>
            <div v-if="currentProduct.specifications" class="detail-row">
              <strong>Specifications:</strong>
              <pre class="specifications">{{ formatSpecs(currentProduct.specifications) }}</pre>
            </div>
            <div v-if="currentProduct.url" class="detail-row">
              <strong>URL:</strong>
              <a :href="currentProduct.url" target="_blank" class="link">
                View on FS.com
              </a>
            </div>
            <div v-if="currentProduct.image_url" class="detail-row">
              <strong>Image:</strong>
              <img :src="currentProduct.image_url" :alt="currentProduct.name" class="product-image" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showProductModal = false" class="btn btn-secondary">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import api from '@/services/api';

// State
const stats = reactive({
  total_products: 0,
  total_gbics: 0,
  total_patch_cords: 0,
  last_sync: null,
});

const products = ref([]);
const selectedProducts = ref([]);
const isLoading = ref(false);
const isValidating = ref(false);
const isImporting = ref(false);

const filters = reactive({
  category: 'all',
  search: '',
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
});

const sort = reactive({
  by: 'created_at',
  order: 'desc',
});

// Import Modal
const showImportModal = ref(false);
const importStep = ref(1);
const selectedFile = ref(null);
const validationResult = ref(null);
const importResult = ref(null);
const importOptions = reactive({
  stopOnError: false,
});

// Product Details Modal
const showProductModal = ref(false);
const currentProduct = ref(null);

// Computed
const allSelected = computed(() => {
  return products.value.length > 0 && selectedProducts.value.length === products.value.length;
});

// Methods
const loadStats = async () => {
  try {
    const response = await api.get('/admin/fscom-catalog/stats');
    Object.assign(stats, response.data);
  } catch (error) {
    console.error('Failed to load stats:', error);
  }
};

const loadProducts = async (page = 1) => {
  isLoading.value = true;
  try {
    const response = await api.get('/admin/fscom-catalog', {
      params: {
        page,
        per_page: pagination.per_page,
        category: filters.category,
        search: filters.search,
        sort_by: sort.by,
        sort_order: sort.order,
      },
    });

    products.value = response.data.data;
    pagination.current_page = response.data.current_page;
    pagination.last_page = response.data.last_page;
    pagination.total = response.data.total;
  } catch (error) {
    console.error('Failed to load products:', error);
    alert('Failed to load products');
  } finally {
    isLoading.value = false;
  }
};

const generateTemplate = async () => {
  isLoading.value = true;
  try {
    const response = await api.post('/admin/fscom-catalog/template');
    // Download the file
    window.open(response.data.download_url, '_blank');
    alert('Template generated successfully!');
  } catch (error) {
    console.error('Failed to generate template:', error);
    const errorMsg = error.response?.data?.message || error.message || 'Failed to generate template';
    alert('Error: ' + errorMsg);
  } finally {
    isLoading.value = false;
  }
};

const exportCatalog = async () => {
  isLoading.value = true;
  try {
    const response = await api.post('/admin/fscom-catalog/export', {
      category: filters.category !== 'all' ? filters.category : null,
    });
    // Download the file
    window.open(response.data.download_url, '_blank');
    alert(`Exported ${response.data.count} products successfully!`);
  } catch (error) {
    console.error('Failed to export catalog:', error);
    alert('Failed to export catalog');
  } finally {
    isLoading.value = false;
  }
};

const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    selectedFile.value = file;
  }
};

const validateFile = async () => {
  if (!selectedFile.value) return;

  isValidating.value = true;
  try {
    const formData = new FormData();
    formData.append('file', selectedFile.value);

    const response = await api.post('/admin/fscom-catalog/validate', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    validationResult.value = response.data;
    importStep.value = 2;
  } catch (error) {
    console.error('Validation failed:', error);
    alert('Validation failed: ' + (error.response?.data?.message || error.message));
  } finally {
    isValidating.value = false;
  }
};

const performImport = async () => {
  if (!selectedFile.value) return;

  isImporting.value = true;
  try {
    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('stop_on_error', importOptions.stopOnError ? '1' : '0');

    const response = await api.post('/admin/fscom-catalog/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    importResult.value = response.data;
    importStep.value = 3;

    // Reload stats and products
    loadStats();
  } catch (error) {
    console.error('Import failed:', error);
    alert('Import failed: ' + (error.response?.data?.message || error.message));
  } finally {
    isImporting.value = false;
  }
};

const finishImport = () => {
  closeImportModal();
  loadProducts();
};

const closeImportModal = () => {
  showImportModal.value = false;
  importStep.value = 1;
  selectedFile.value = null;
  validationResult.value = null;
  importResult.value = null;
  importOptions.stopOnError = false;
};

const deleteProduct = async (id) => {
  if (!confirm('Are you sure you want to delete this product?')) return;

  try {
    await api.delete(`/admin/fscom-catalog/${id}`);
    alert('Product deleted successfully');
    loadProducts();
    loadStats();
  } catch (error) {
    console.error('Failed to delete product:', error);
    alert('Failed to delete product');
  }
};

const bulkDelete = async () => {
  if (!confirm(`Delete ${selectedProducts.value.length} selected product(s)?`)) return;

  try {
    await api.post('/admin/fscom-catalog/bulk-delete', {
      ids: selectedProducts.value,
    });
    alert(`${selectedProducts.value.length} products deleted successfully`);
    selectedProducts.value = [];
    loadProducts();
    loadStats();
  } catch (error) {
    console.error('Bulk delete failed:', error);
    alert('Bulk delete failed');
  }
};

const viewProduct = (product) => {
  currentProduct.value = product;
  showProductModal.value = true;
};

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedProducts.value = [];
  } else {
    selectedProducts.value = products.value.map((p) => p.id);
  }
};

const sortBy = (field) => {
  if (sort.by === field) {
    sort.order = sort.order === 'asc' ? 'desc' : 'asc';
  } else {
    sort.by = field;
    sort.order = 'asc';
  }
  loadProducts();
};

const getSortIcon = (field) => {
  if (sort.by !== field) return '';
  return sort.order === 'asc' ? '↑' : '↓';
};

const goToPage = (page) => {
  if (page < 1 || page > pagination.last_page) return;
  loadProducts(page);
};

let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadProducts();
  }, 500);
};

// Formatting helpers
const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString();
};

const formatPrice = (price, currency = 'USD') => {
  if (!price) return 'N/A';
  return `${parseFloat(price).toFixed(2)} ${currency}`;
};

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const formatSpecs = (specs) => {
  try {
    const parsed = typeof specs === 'string' ? JSON.parse(specs) : specs;
    return JSON.stringify(parsed, null, 2);
  } catch {
    return specs;
  }
};

// Lifecycle
onMounted(() => {
  loadStats();
  loadProducts();
});
</script>

<style scoped>
.fscom-catalog-container {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  color: #1a202c;
}

.page-description {
  color: #718096;
  margin: 0.5rem 0 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 0.5rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  font-size: 2.5rem;
}

.stat-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: #1a202c;
}

.stat-label {
  color: #718096;
  font-size: 0.875rem;
}

.actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.actions-left,
.actions-right {
  display: flex;
  gap: 0.75rem;
}

.btn {
  padding: 0.625rem 1.25rem;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-secondary {
  background: #e5e7eb;
  color: #374151;
}

.btn-secondary:hover:not(:disabled) {
  background: #d1d5db;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.filter-select,
.search-input {
  padding: 0.625rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.search-input {
  min-width: 250px;
}

.table-container {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: #f9fafb;
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.data-table th.sortable {
  cursor: pointer;
  user-select: none;
}

.data-table th.sortable:hover {
  background: #f3f4f6;
}

.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.data-table tbody tr:hover {
  background: #f9fafb;
}

.category-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.category-gbic {
  background: #dbeafe;
  color: #1e40af;
}

.category-patch_cord {
  background: #d1fae5;
  color: #065f46;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.25rem;
  padding: 0.25rem;
  margin: 0 0.25rem;
}

.loading-cell,
.empty-cell {
  text-align: center;
  padding: 3rem 1rem;
  color: #9ca3af;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.pagination-info {
  color: #6b7280;
  font-size: 0.875rem;
}

.bulk-actions {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  padding: 1rem 2rem;
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 1rem;
  z-index: 50;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 1rem;
}

.modal {
  background: white;
  border-radius: 0.5rem;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #9ca3af;
  line-height: 1;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.import-step h3 {
  margin-top: 0;
  margin-bottom: 1.5rem;
  color: #374151;
}

.file-upload {
  border: 2px dashed #d1d5db;
  border-radius: 0.5rem;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  margin-bottom: 1.5rem;
}

.file-input {
  width: 100%;
  cursor: pointer;
}

.file-upload-placeholder,
.file-selected {
  margin-top: 0.5rem;
}

.text-muted {
  color: #9ca3af;
  font-size: 0.875rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.stats-summary {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.375rem;
  margin-bottom: 1rem;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e5e7eb;
}

.stat-item:last-child {
  border-bottom: none;
}

.text-success {
  color: #059669;
  font-weight: 600;
}

.text-error {
  color: #dc2626;
  font-weight: 600;
}

.text-info {
  color: #0284c7;
  font-weight: 600;
}

.errors-list {
  margin-top: 1rem;
  max-height: 300px;
  overflow-y: auto;
}

.error-item {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.375rem;
  padding: 0.75rem;
  margin-bottom: 0.75rem;
}

.error-item ul {
  margin: 0.5rem 0 0 1.25rem;
}

.success-message {
  background: #f0fdf4;
  border: 1px solid #86efac;
  border-radius: 0.375rem;
  padding: 1rem;
  text-align: center;
  color: #166534;
}

.import-options {
  margin: 1rem 0;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.product-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-row {
  display: grid;
  grid-template-columns: 150px 1fr;
  gap: 1rem;
}

.specifications {
  background: #f9fafb;
  padding: 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  overflow-x: auto;
}

.product-image {
  max-width: 300px;
  border-radius: 0.375rem;
}

.link {
  color: #3b82f6;
  text-decoration: none;
}

.link:hover {
  text-decoration: underline;
}
</style>
