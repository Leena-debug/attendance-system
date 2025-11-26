// ===== MAIN APPLICATION JS =====

class AttendanceApp {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupAjax();
        this.setupUI();
        this.checkNotifications();
    }

    // ===== EVENT LISTENERS =====
    setupEventListeners() {
        // Global click handlers
        $(document).on('click', '[data-action]', this.handleAction.bind(this));
        
        // Form submissions
        $(document).on('submit', 'form', this.handleFormSubmit.bind(this));
        
        // Search functionality
        $(document).on('keyup', '.search-input', this.handleSearch.bind(this));
        
        // Modal handlers
        $(document).on('click', '[data-modal]', this.handleModal.bind(this));
        $(document).on('click', '.modal-overlay', this.closeModal.bind(this));
        $(document).on('click', '.modal-close', this.closeModal.bind(this));
        
        // Tab functionality
        $(document).on('click', '[data-tab]', this.handleTabSwitch.bind(this));
        
        // Auto-save forms
        $(document).on('change', '.auto-save', this.autoSaveForm.bind(this));
    }

    // ===== ACTION HANDLER =====
    handleAction(e) {
        e.preventDefault();
        const $target = $(e.currentTarget);
        const action = $target.data('action');
        const data = $target.data();

        switch (action) {
            case 'delete':
                this.confirmDelete(data);
                break;
            case 'export':
                this.exportData(data);
                break;
            case 'refresh':
                this.refreshData();
                break;
            case 'print':
                this.printElement(data.target);
                break;
            case 'toggle':
                this.toggleElement(data.target);
                break;
        }
    }

    // ===== FORM HANDLING =====
    handleFormSubmit(e) {
        const $form = $(e.currentTarget);
        const $submitBtn = $form.find('button[type="submit"]');
        
        // Show loading state
        this.setButtonLoading($submitBtn, true);
        
        // Add timestamp to prevent caching
        $form.find('input[name="_ts"]').remove();
        $form.append('<input type="hidden" name="_ts" value="' + Date.now() + '">');
        
        // Handle file uploads differently
        if ($form.attr('enctype') === 'multipart/form-data') {
            return true; // Let normal form submission handle it
        }
        
        // AJAX form submission
        e.preventDefault();
        this.submitFormAjax($form);
    }

    submitFormAjax($form) {
        $.ajax({
            url: $form.attr('action') || window.location.href,
            method: $form.attr('method') || 'POST',
            data: $form.serialize(),
            dataType: 'json'
        })
        .done(response => {
            this.handleFormResponse(response, $form);
        })
        .fail(error => {
            this.showError('Form submission failed. Please try again.');
            console.error('Form submission error:', error);
        })
        .always(() => {
            this.setButtonLoading($form.find('button[type="submit"]'), false);
        });
    }

    handleFormResponse(response, $form) {
        if (response.success) {
            this.showSuccess(response.message || 'Operation completed successfully!');
            
            if (response.redirect) {
                setTimeout(() => {
                    window.location.href = response.redirect;
                }, 1500);
            } else if (response.reload) {
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                // Clear form if no redirect
                $form[0].reset();
            }
        } else {
            this.showError(response.message || 'An error occurred. Please try again.');
        }
    }

    // ===== SEARCH FUNCTIONALITY =====
    handleSearch(e) {
        const $input = $(e.currentTarget);
        const searchTerm = $input.val().toLowerCase();
        const tableId = $input.data('table') || $input.closest('.card').find('table').attr('id');
        
        if (tableId) {
            $(`#${tableId} tbody tr`).each(function() {
                const $row = $(this);
                const text = $row.text().toLowerCase();
                $row.toggle(text.indexOf(searchTerm) > -1);
            });
        }
    }

    // ===== MODAL SYSTEM =====
    handleModal(e) {
        e.preventDefault();
        const $target = $(e.currentTarget);
        const modalId = $target.data('modal');
        
        this.openModal(modalId);
    }

    openModal(modalId) {
        const $modal = $(`#${modalId}`);
        if ($modal.length) {
            $modal.fadeIn(300);
            $('body').addClass('modal-open');
        }
    }

    closeModal() {
        $('.modal-overlay').fadeOut(300);
        $('body').removeClass('modal-open');
    }

    // ===== TAB SYSTEM =====
    handleTabSwitch(e) {
        e.preventDefault();
        const $tab = $(e.currentTarget);
        const tabId = $tab.data('tab');
        
        // Update active tab
        $tab.closest('.tabs').find('.tab').removeClass('active');
        $tab.addClass('active');
        
        // Show corresponding content
        $(`.tab-content`).removeClass('active');
        $(`#${tabId}`).addClass('active');
    }

    // ===== UTILITY METHODS =====
    setButtonLoading($button, loading) {
        if (loading) {
            $button.data('original-text', $button.html());
            $button.html('<span class="loading"></span> Processing...').prop('disabled', true);
        } else {
            $button.html($button.data('original-text')).prop('disabled', false);
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        const notification = $(`
            <div class="alert alert-${type} notification">
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        notification.slideDown(300);
        
        setTimeout(() => {
            notification.slideUp(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    confirmDelete(data) {
        const message = data.message || 'Are you sure you want to delete this item? This action cannot be undone.';
        
        if (confirm(message)) {
            if (data.url) {
                window.location.href = data.url;
            } else if (data.selector) {
                $(data.selector).remove();
                this.showSuccess('Item deleted successfully!');
            }
        }
    }

    exportData(data) {
        const $button = $(`[data-action="export"][data-type="${data.type}"]`);
        this.setButtonLoading($button, true);
        
        // Simulate export process
        setTimeout(() => {
            this.setButtonLoading($button, false);
            this.showSuccess(`Export completed! Your ${data.type} data is ready for download.`);
        }, 2000);
    }

    refreshData() {
        window.location.reload();
    }

    printElement(selector) {
        const $element = $(selector);
        const printContent = $element.html();
        const originalContent = $('body').html();
        
        $('body').html(printContent);
        window.print();
        $('body').html(originalContent);
    }

    toggleElement(selector) {
        $(selector).slideToggle(300);
    }

    autoSaveForm(e) {
        const $form = $(e.currentTarget).closest('form');
        if ($form.hasClass('auto-save-form')) {
            this.submitFormAjax($form);
        }
    }

    // ===== AJAX SETUP =====
    setupAjax() {
        $.ajaxSetup({
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            error: (xhr, status, error) => {
                if (xhr.status === 401) {
                    this.showError('Your session has expired. Please log in again.');
                    setTimeout(() => {
                        window.location.href = '../auth/login.php';
                    }, 2000);
                } else if (xhr.status === 403) {
                    this.showError('You do not have permission to perform this action.');
                } else if (xhr.status === 500) {
                    this.showError('Server error. Please try again later.');
                }
            }
        });
    }

    // ===== UI ENHANCEMENTS =====
    setupUI() {
        // Add loading state to all buttons with loading class
        $('.btn-loading').on('click', function() {
            const $btn = $(this);
            app.setButtonLoading($btn, true);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            $('.alert').not('.notification').fadeOut(500);
        }, 5000);

        // Smooth scrolling for anchor links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 800);
            }
        });

        // Enhance select elements
        $('select').each(function() {
            if ($(this).children('option').length > 5) {
                $(this).addClass('enhanced-select');
            }
        });
    }

    // ===== NOTIFICATION SYSTEM =====
    checkNotifications() {
        // Check for new notifications every 30 seconds
        setInterval(() => {
            if (document.hasFocus()) { // Only check if tab is active
                this.fetchNotifications();
            }
        }, 30000);
    }

    fetchNotifications() {
        $.get('../api/notifications.php')
            .done(data => {
                if (data.unread > 0) {
                    this.showNotification(`You have ${data.unread} new notifications`, 'info');
                }
            })
            .fail(() => {
                // Silent fail for notifications
            });
    }

    // ===== FILE UPLOAD ENHANCEMENTS =====
    enhanceFileUploads() {
        $('input[type="file"]').on('change', function() {
            const $input = $(this);
            const fileName = $input.val().split('\\').pop();
            
            if (fileName) {
                $input.next('.file-name').remove();
                $input.after(`<span class="file-name" style="display: block; margin-top: 5px; color: #666;">📎 ${fileName}</span>`);
            }
        });
    }

    // ===== DATA VISUALIZATION HELPERS =====
    createProgressBar(percentage, size = 'medium') {
        const color = percentage >= 80 ? 'var(--success)' : 
                     percentage >= 60 ? 'var(--warning)' : 'var(--error)';
        
        const height = size === 'small' ? '4px' : 
                      size === 'large' ? '10px' : '6px';
        
        return `
            <div class="progress-container" style="background: #f0f0f0; border-radius: 10px; height: ${height}; overflow: hidden;">
                <div class="progress-bar" style="background: ${color}; width: ${Math.min(percentage, 100)}%; height: 100%; border-radius: 10px; transition: width 0.3s ease;"></div>
            </div>
        `;
    }

    // ===== DATE/TIME HELPERS =====
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // ===== STORAGE HELPERS =====
    setStorage(key, value) {
        try {
            localStorage.setItem(`attendance_${key}`, JSON.stringify(value));
        } catch (e) {
            console.warn('Local storage not available');
        }
    }

    getStorage(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(`attendance_${key}`);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            return defaultValue;
        }
    }
}

// ===== INITIALIZATION =====
const app = new AttendanceApp();

// Global helper functions
window.AttendanceApp = app;

// jQuery document ready
$(document).ready(function() {
    // Additional initialization that requires DOM to be fully ready
    app.enhanceFileUploads();
    
    // Add any other DOM-ready functionality here
    console.log('Attendance Management System loaded successfully! 🎓');
});

// Error handling
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    app.showError('An unexpected error occurred. Please refresh the page.');
});

// Page visibility handling
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Page became visible, refresh data if needed
        app.fetchNotifications();
    }
});