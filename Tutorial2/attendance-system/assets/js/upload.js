// ===== FILE UPLOAD ENHANCEMENTS =====

class FileUploadHandler {
    constructor() {
        this.init();
    }

    init() {
        this.setupFileUploads();
        this.setupDragAndDrop();
    }

    setupFileUploads() {
        // Enhance file input elements
        $('input[type="file"]').each((index, input) => {
            this.enhanceFileInput($(input));
        });

        // File validation
        $(document).on('change', 'input[type="file"]', (e) => {
            this.validateFile(e.target);
        });
    }

    enhanceFileInput($input) {
        const container = $input.closest('.file-upload-container');
        
        if (container.length === 0) {
            $input.wrap('<div class="file-upload-container" style="position: relative;"></div>');
            container = $input.parent();
        }

        // Add custom upload area
        container.append(`
            <div class="file-upload-area" style="
                border: 2px dashed #ddd;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                background: #fafafa;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 10px;
            ">
                <div class="upload-icon" style="font-size: 24px; margin-bottom: 10px;">📁</div>
                <div class="upload-text">
                    <strong>Click to upload</strong> or drag and drop
                </div>
                <div class="upload-hint" style="font-size: 12px; color: #666; margin-top: 5px;">
                    Supports: CSV, Excel, PDF, Images
                </div>
            </div>
            <div class="file-preview" style="margin-top: 10px;"></div>
        `);

        const $uploadArea = container.find('.file-upload-area');
        const $preview = container.find('.file-preview');

        // Hide original input
        $input.css({
            'position': 'absolute',
            'opacity': 0,
            'width': '100%',
            'height': '100%',
            'cursor': 'pointer'
        });

        // Click handler for upload area
        $uploadArea.on('click', () => {
            $input.click();
        });

        // Update display when file is selected
        $input.on('change', () => {
            this.updateFileDisplay($input, $uploadArea, $preview);
        });
    }

    updateFileDisplay($input, $uploadArea, $preview) {
        const files = $input[0].files;
        
        if (files.length > 0) {
            const file = files[0];
            const fileSize = this.formatFileSize(file.size);
            const fileType = this.getFileType(file.name);
            
            $uploadArea.html(`
                <div style="text-align: left;">
                    <div style="font-size: 16px; margin-bottom: 5px;">
                        <strong>${file.name}</strong>
                    </div>
                    <div style="font-size: 12px; color: #666;">
                        ${fileType} • ${fileSize}
                    </div>
                </div>
            `);
            
            $uploadArea.css({
                'border-color': '#4caf50',
                'background': '#e8f5e8'
            });

            // Show preview for images and PDFs
            if (file.type.startsWith('image/')) {
                this.previewImage(file, $preview);
            } else if (file.type === 'application/pdf') {
                this.previewPDF(file, $preview);
            }
        } else {
            $uploadArea.html(`
                <div class="upload-icon" style="font-size: 24px; margin-bottom: 10px;">📁</div>
                <div class="upload-text">
                    <strong>Click to upload</strong> or drag and drop
                </div>
                <div class="upload-hint" style="font-size: 12px; color: #666; margin-top: 5px;">
                    Supports: CSV, Excel, PDF, Images
                </div>
            `);
            $uploadArea.css({
                'border-color': '#ddd',
                'background': '#fafafa'
            });
            $preview.empty();
        }
    }

    setupDragAndDrop() {
        $(document).on('dragover', '.file-upload-area', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).css({
                'border-color': '#e91e63',
                'background': '#fce4ec'
            });
        });

        $(document).on('dragleave', '.file-upload-area', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).css({
                'border-color': '#ddd',
                'background': '#fafafa'
            });
        });

        $(document).on('drop', '.file-upload-area', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const files = e.originalEvent.dataTransfer.files;
            const $input = $(this).siblings('input[type="file"]');
            
            if (files.length > 0) {
                $input[0].files = files;
                $input.trigger('change');
            }
        });
    }

    validateFile(input) {
        const file = input.files[0];
        if (!file) return true;

        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        // Check file size
        if (file.size > maxSize) {
            this.showUploadError('File size must be less than 5MB');
            input.value = '';
            return false;
        }

        // Check file type
        if (!allowedTypes.includes(file.type) && 
            !file.name.endsWith('.csv') && 
            !file.name.endsWith('.xlsx') && 
            !file.name.endsWith('.xls')) {
            this.showUploadError('File type not supported. Please upload CSV, Excel, PDF, or image files.');
            input.value = '';
            return false;
        }

        return true;
    }

    previewImage(file, $preview) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            $preview.html(`
                <div style="margin-top: 10px;">
                    <img src="${e.target.result}" style="max-width: 200px; max-height: 200px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
            `);
        };
        
        reader.readAsDataURL(file);
    }

    previewPDF(file, $preview) {
        $preview.html(`
            <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; text-align: center;">
                <div style="font-size: 24px; margin-bottom: 5px;">📄</div>
                <div style="font-size: 12px; color: #666;">
                    PDF Document: ${file.name}
                </div>
            </div>
        `);
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    getFileType(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        const types = {
            'jpg': 'JPEG Image',
            'jpeg': 'JPEG Image',
            'png': 'PNG Image',
            'gif': 'GIF Image',
            'pdf': 'PDF Document',
            'csv': 'CSV File',
            'xlsx': 'Excel Spreadsheet',
            'xls': 'Excel Spreadsheet'
        };
        
        return types[ext] || 'File';
    }

    showUploadError(message) {
        // Create error notification
        const $error = $(`
            <div class="alert alert-error" style="margin-top: 10px;">
                ${message}
            </div>
        `);
        
        $('body').append($error);
        
        setTimeout(() => {
            $error.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // AJAX file upload with progress
    uploadWithProgress($form, onProgress, onComplete) {
        const formData = new FormData($form[0]);
        
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                
                // Upload progress
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        onProgress(percentComplete);
                    }
                }, false);
                
                return xhr;
            },
            success: function(response) {
                onComplete(response);
            },
            error: function(xhr, status, error) {
                onComplete({ success: false, error: error });
            }
        });
    }
}

// Initialize file upload handler
$(document).ready(function() {
    window.fileUploadHandler = new FileUploadHandler();
});

// Export functionality for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FileUploadHandler };
}