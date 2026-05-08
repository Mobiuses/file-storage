<template>
  <div class="container mt-5">
    <h1 class="mb-4">File Storage Manager</h1>

    <!-- Upload Form -->
    <div class="card mb-4">
      <div class="card-header">
        <h5>Upload File</h5>
      </div>
      <div class="card-body">
        <div
          class="upload-area"
          :class="{ 'drag-over': isDragging }"
          @drop.prevent="handleDrop"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
        >
          <div class="text-center">
            <i class="bi bi-cloud-upload fs-1 text-muted"></i>
            <p class="mt-3">Drag and drop files here or click to browse</p>
            <input
              type="file"
              ref="fileInput"
              @change="handleFileSelect"
              accept=".pdf,.docx"
              class="d-none"
            />
            <button class="btn btn-primary" @click="$refs.fileInput.click()">
              Browse Files
            </button>
            <p class="text-muted mt-2 small">
              Supported formats: PDF, DOCX (max 10MB)
            </p>
          </div>
        </div>

        <!-- Upload Progress -->
        <div v-if="uploading" class="mt-3">
          <div class="progress">
            <div
              class="progress-bar progress-bar-striped progress-bar-animated"
              role="progressbar"
              :style="{ width: uploadProgress + '%' }"
            >
              {{ uploadProgress }}%
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Files List -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Uploaded Files</h5>
        <button class="btn btn-sm btn-outline-secondary" @click="fetchFiles">
          <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
      </div>
      <div class="card-body">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else-if="files.length === 0" class="alert alert-info">
          No files uploaded yet. Upload your first file!
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>File Name</th>
                <th>Type</th>
                <th>Size</th>
                <th>Uploaded</th>
                <th>Expires In</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="file in files" :key="file.id">
                <td>
                  <i :class="getFileIcon(file.mime_type)" class="me-2"></i>
                  {{ file.original_name }}
                </td>
                <td>
                  <span class="badge bg-secondary">
                    {{ getFileType(file.mime_type) }}
                  </span>
                </td>
                <td>{{ formatSize(file.size) }}</td>
                <td>{{ formatDate(file.created_at) }}</td>
                <td>
                  <span
                    class="badge"
                    :class="getExpiryClass(file.expires_at)"
                  >
                    {{ getTimeRemaining(file.expires_at) }}
                  </span>
                </td>
                <td>
                  <button
                    class="btn btn-sm btn-success me-2"
                    @click="downloadFile(file.id, file.original_name)"
                    title="Download"
                  >
                    <i class="bi bi-download"></i>
                  </button>
                  <button
                    class="btn btn-sm btn-danger"
                    @click="deleteFile(file.id)"
                    title="Delete"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FileManager',
  data() {
    return {
      files: [],
      loading: false,
      uploading: false,
      uploadProgress: 0,
      isDragging: false
    };
  },
  mounted() {
    this.fetchFiles();
  },
  methods: {
    async fetchFiles() {
      this.loading = true;
      try {
        const response = await axios.get('/files');
        this.files = response.data;
      } catch (error) {
        console.error('Error fetching files:', error);
        alert('Failed to load files');
      } finally {
        this.loading = false;
      }
    },
    handleDrop(e) {
      this.isDragging = false;
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        this.uploadFile(files[0]);
      }
    },
    handleFileSelect(e) {
      const files = e.target.files;
      if (files.length > 0) {
        this.uploadFile(files[0]);
      }
    },
    async uploadFile(file) {
      if (!this.validateFile(file)) {
        return;
      }

      this.uploading = true;
      this.uploadProgress = 0;

      const formData = new FormData();
      formData.append('file', file);

      try {
        const response = await axios.post('/files', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          },
          onUploadProgress: (progressEvent) => {
            this.uploadProgress = Math.round(
              (progressEvent.loaded * 100) / progressEvent.total
            );
          }
        });

        this.files.unshift(response.data);
        alert('File uploaded successfully!');
        this.$refs.fileInput.value = '';
      } catch (error) {
        console.error('Error uploading file:', error);
        let message = 'Failed to upload file';

        if (error.response) {
          // Laravel validation errors (422)
          if (error.response.status === 422 && error.response.data.errors) {
            const errors = error.response.data.errors;
            const firstError = Object.values(errors)[0];
            message = Array.isArray(firstError) ? firstError[0] : firstError;
          }
          // Other error messages
          else if (error.response.data.message) {
            message = error.response.data.message;
          }
          else if (error.response.data.error) {
            message = error.response.data.error;
          }
        }

        alert(message);
      } finally {
        this.uploading = false;
        this.uploadProgress = 0;
      }
    },
    validateFile(file) {
      const allowedTypes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
      ];
      const maxSize = 10 * 1024 * 1024; // 10MB

      if (!allowedTypes.includes(file.type)) {
        alert('Only PDF and DOCX files are allowed');
        return false;
      }

      if (file.size > maxSize) {
        alert('File size must not exceed 10MB');
        return false;
      }

      return true;
    },
    async downloadFile(id, filename) {
      try {
        const response = await axios.get(`/files/${id}/download`, {
          responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
      } catch (error) {
        console.error('Error downloading file:', error);
        alert('Failed to download file');
      }
    },
    async deleteFile(id) {
      if (!confirm('Are you sure you want to delete this file?')) {
        return;
      }

      try {
        await axios.delete(`/files/${id}`);
        this.files = this.files.filter(f => f.id !== id);
        alert('File deleted successfully!');
      } catch (error) {
        console.error('Error deleting file:', error);
        alert('Failed to delete file');
      }
    },
    getFileIcon(mimeType) {
      if (mimeType === 'application/pdf') {
        return 'bi bi-file-pdf-fill text-danger';
      }
      return 'bi bi-file-word-fill text-primary';
    },
    getFileType(mimeType) {
      if (mimeType === 'application/pdf') {
        return 'PDF';
      }
      return 'DOCX';
    },
    formatSize(bytes) {
      const units = ['B', 'KB', 'MB', 'GB'];
      let size = bytes;
      let unitIndex = 0;

      while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
      }

      return `${size.toFixed(2)} ${units[unitIndex]}`;
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleString();
    },
    getTimeRemaining(expiresAt) {
      const now = new Date();
      const expires = new Date(expiresAt);
      const diff = expires - now;

      if (diff <= 0) {
        return 'Expired';
      }

      const hours = Math.floor(diff / (1000 * 60 * 60));
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

      if (hours > 0) {
        return `${hours}h ${minutes}m`;
      }
      return `${minutes}m`;
    },
    getExpiryClass(expiresAt) {
      const now = new Date();
      const expires = new Date(expiresAt);
      const diff = expires - now;
      const hours = diff / (1000 * 60 * 60);

      if (hours <= 0) {
        return 'bg-danger';
      } else if (hours <= 2) {
        return 'bg-warning';
      }
      return 'bg-success';
    }
  }
};
</script>

<style scoped>
.upload-area {
  border: 2px dashed #ccc;
  border-radius: 8px;
  padding: 40px;
  transition: all 0.3s ease;
  cursor: pointer;
}

.upload-area:hover,
.upload-area.drag-over {
  border-color: #0d6efd;
  background-color: #f8f9fa;
}

.table th {
  font-weight: 600;
  background-color: #f8f9fa;
}
</style>
