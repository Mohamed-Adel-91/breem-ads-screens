/**
 * Custom Form Inputs Validation Handler
 * Provides native HTML5-like validation for custom inputs:
 * - image-uploader
 * - multi-image-uploader
 * - select2
 * - TinyMCE
 */

(function() {
    'use strict';

    class CustomInputsValidator {
        constructor() {
            this.validationMessages = {
                ar: {
                    required: 'هذا الحقل مطلوب',
                    fileRequired: 'يرجى اختيار ملف',
                    selectRequired: 'يرجى اختيار خيار',
                    editorRequired: 'يرجى إدخال محتوى'
                },
                en: {
                    required: 'This field is required',
                    fileRequired: 'Please select a file',
                    selectRequired: 'Please select an option',
                    editorRequired: 'Please enter content'
                }
            };
            this.lang = 'ar';
            this.init();
        }

        init() {
            this.setupFormValidation();
            this.setupImageUploaderValidation();
            this.setupMultiImageUploaderValidation();
            this.setupSelect2Validation();
            this.setupTinyMCEValidation();
        }

        setupFormValidation() {
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form.classList.contains('needs-validation')) return;

                const isValid = this.validateForm(form);
                
                if (!isValid) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, true);
        }

        validateForm(form) {
            let isValid = true;

            // Validate all custom inputs
            isValid = this.validateAllImageUploaders(form) && isValid;
            isValid = this.validateAllMultiImageUploaders(form) && isValid;
            isValid = this.validateAllSelect2(form) && isValid;
            isValid = this.validateAllTinyMCE(form) && isValid;

            return isValid;
        }

        // ========== Image Uploader Validation ==========
        setupImageUploaderValidation() {
            document.addEventListener('DOMContentLoaded', () => {
                const uploaders = document.querySelectorAll('.modern-file-input[required]');
                
                uploaders.forEach(input => {
                    // Create hidden validation input
                    this.createValidationInput(input);
                    
                    // Listen for file changes
                    input.addEventListener('change', () => {
                        this.validateImageUploader(input);
                    });

                    // Remove validation on focus
                    input.addEventListener('focus', () => {
                        this.clearValidation(input);
                    });
                });
            });
        }

        createValidationInput(originalInput) {
            if (originalInput.dataset.validationInputCreated) return;
            
            const validationInput = document.createElement('input');
            validationInput.type = 'text';
            validationInput.style.display = 'none';
            validationInput.required = true;
            validationInput.tabIndex = -1;
            validationInput.setAttribute('data-validation-for', originalInput.id);
            
            originalInput.parentNode.insertBefore(validationInput, originalInput.nextSibling);
            originalInput.dataset.validationInputCreated = 'true';
            originalInput.dataset.validationInputId = validationInput.id || 'validation_' + originalInput.id;
        }

        validateImageUploader(input) {
            const wrapper = input.closest('.modern-image-uploader');
            const hasFile = input.files && input.files.length > 0;
            const hasExistingValue = input.dataset.hasExistingFile === 'true';
            
            if (input.required && !hasFile && !hasExistingValue) {
                this.setInvalid(wrapper, this.validationMessages[this.lang].fileRequired);
                return false;
            } else {
                this.setValid(wrapper);
                return true;
            }
        }

        validateAllImageUploaders(form) {
            const uploaders = form.querySelectorAll('.modern-file-input[required]');
            let allValid = true;

            uploaders.forEach(input => {
                if (!this.validateImageUploader(input)) {
                    allValid = false;
                    // Scroll to first invalid input
                    if (allValid === false) {
                        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });

            return allValid;
        }

        // ========== Multi Image Uploader Validation ==========
        setupMultiImageUploaderValidation() {
            document.addEventListener('DOMContentLoaded', () => {
                const uploaders = document.querySelectorAll('.multi-image-uploader-wrapper input[type="file"][required]');
                
                uploaders.forEach(input => {
                    // Create hidden validation input
                    this.createValidationInput(input);
                    
                    // Listen for file changes
                    input.addEventListener('change', () => {
                        this.validateMultiImageUploader(input);
                    });
                });
            });
        }

        validateMultiImageUploader(input) {
            const wrapper = input.closest('.multi-image-uploader-wrapper');
            const hasNewFiles = input.files && input.files.length > 0;
            const existingImagesContainer = wrapper.querySelector('[id^="existing_images_container_"]');
            const hasExistingImages = existingImagesContainer && existingImagesContainer.children.length > 0;
            
            if (input.required && !hasNewFiles && !hasExistingImages) {
                this.setInvalid(wrapper, this.validationMessages[this.lang].fileRequired);
                return false;
            } else {
                this.setValid(wrapper);
                return true;
            }
        }

        validateAllMultiImageUploaders(form) {
            const uploaders = form.querySelectorAll('.multi-image-uploader-wrapper input[type="file"][required]');
            let allValid = true;

            uploaders.forEach(input => {
                if (!this.validateMultiImageUploader(input)) {
                    allValid = false;
                }
            });

            return allValid;
        }

        // ========== Select2 Validation ==========
        setupSelect2Validation() {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    const selects = document.querySelectorAll('select[data-select2-init="true"][required]');
                    
                    selects.forEach(select => {
                        const $select = $(select);
                        
                        // Listen for select2 changes
                        $select.on('change', () => {
                            this.validateSelect2(select);
                        });

                        // Listen for select2 open to clear validation
                        $select.on('select2:open', () => {
                            this.clearValidation(select);
                        });
                    });
                }, 500);
            });
        }

        validateSelect2(select) {
            const value = select.value;
            const wrapper = select.closest('.form-group');
            const $select = $(select);
            const container = $select.next('.select2-container');
            
            if (select.required && (!value || value === '')) {
                this.setInvalid(wrapper, this.validationMessages[this.lang].selectRequired);
                if (container.length) {
                    container.find('.select2-selection').addClass('is-invalid');
                }
                return false;
            } else {
                this.setValid(wrapper);
                if (container.length) {
                    container.find('.select2-selection').removeClass('is-invalid').addClass('is-valid');
                }
                return true;
            }
        }

        validateAllSelect2(form) {
            const selects = form.querySelectorAll('select[data-select2-init="true"][required]');
            let allValid = true;

            selects.forEach(select => {
                if (!this.validateSelect2(select)) {
                    allValid = false;
                }
            });

            return allValid;
        }

        // ========== TinyMCE Validation ==========
        setupTinyMCEValidation() {
            document.addEventListener('DOMContentLoaded', () => {
                if (!window.tinymce) return;

                // Wait for TinyMCE to initialize
                setTimeout(() => {
                    const textareas = document.querySelectorAll('textarea[data-editor="tinymce"][required]');
                    
                    textareas.forEach(textarea => {
                        const editor = tinymce.get(textarea.id);
                        if (editor) {
                            // Listen for content changes
                            editor.on('change keyup', () => {
                                this.validateTinyMCE(textarea);
                            });

                            // Clear validation on focus
                            editor.on('focus', () => {
                                this.clearValidation(textarea);
                            });
                        }
                    });
                }, 1000);
            });
        }

        validateTinyMCE(textarea) {
            const editor = tinymce.get(textarea.id);
            if (!editor) return true;

            const content = editor.getContent({ format: 'text' }).trim();
            const wrapper = textarea.closest('.form-group') || textarea.parentElement;
            
            if (textarea.required && !content) {
                this.setInvalid(wrapper, this.validationMessages[this.lang].editorRequired);
                const editorContainer = wrapper.querySelector('.tox-tinymce');
                if (editorContainer) {
                    editorContainer.classList.add('is-invalid');
                }
                return false;
            } else {
                this.setValid(wrapper);
                const editorContainer = wrapper.querySelector('.tox-tinymce');
                if (editorContainer) {
                    editorContainer.classList.remove('is-invalid');
                    editorContainer.classList.add('is-valid');
                }
                return true;
            }
        }

        validateAllTinyMCE(form) {
            if (!window.tinymce) return true;

            const textareas = form.querySelectorAll('textarea[data-editor="tinymce"][required]');
            let allValid = true;

            textareas.forEach(textarea => {
                if (!this.validateTinyMCE(textarea)) {
                    allValid = false;
                }
            });

            return allValid;
        }

        // ========== Helper Methods ==========
        setInvalid(wrapper, message) {
            if (!wrapper) return;

            // Add invalid class
            wrapper.classList.add('is-invalid');
            wrapper.classList.remove('is-valid');

            // Find or create feedback element
            let feedback = wrapper.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback d-block';
                wrapper.appendChild(feedback);
            }
            feedback.textContent = message;
            feedback.style.display = 'block';

            // Add invalid class to input group if exists
            const inputGroup = wrapper.querySelector('.input-group');
            if (inputGroup) {
                inputGroup.classList.add('is-invalid');
            }

            // Add invalid class to dropzone if exists
            const dropzone = wrapper.querySelector('.upload-dropzone');
            if (dropzone) {
                dropzone.style.borderColor = '#dc3545';
            }
        }

        setValid(wrapper) {
            if (!wrapper) return;

            // Remove invalid class
            wrapper.classList.remove('is-invalid');
            wrapper.classList.add('is-valid');

            // Hide feedback
            const feedback = wrapper.querySelector('.invalid-feedback');
            if (feedback && !feedback.hasAttribute('data-server-error')) {
                feedback.style.display = 'none';
            }

            // Remove invalid class from input group
            const inputGroup = wrapper.querySelector('.input-group');
            if (inputGroup) {
                inputGroup.classList.remove('is-invalid');
            }

            // Reset dropzone style
            const dropzone = wrapper.querySelector('.upload-dropzone');
            if (dropzone) {
                dropzone.style.borderColor = '';
            }
        }

        clearValidation(element) {
            const wrapper = element.closest('.modern-image-uploader') || 
                           element.closest('.multi-image-uploader-wrapper') || 
                           element.closest('.form-group') ||
                           element.parentElement;
            
            if (wrapper) {
                wrapper.classList.remove('is-invalid', 'is-valid');
                
                const feedback = wrapper.querySelector('.invalid-feedback');
                if (feedback && !feedback.hasAttribute('data-server-error')) {
                    feedback.style.display = 'none';
                }

                // Clear Select2 validation styling
                if (element.tagName === 'SELECT') {
                    const $select = $(element);
                    const container = $select.next('.select2-container');
                    if (container.length) {
                        container.find('.select2-selection').removeClass('is-invalid is-valid');
                    }
                }

                // Clear TinyMCE validation styling
                if (element.tagName === 'TEXTAREA' && element.dataset.editor === 'tinymce') {
                    const editorContainer = wrapper.querySelector('.tox-tinymce');
                    if (editorContainer) {
                        editorContainer.classList.remove('is-invalid', 'is-valid');
                    }
                }

                // Reset dropzone style
                const dropzone = wrapper.querySelector('.upload-dropzone');
                if (dropzone) {
                    dropzone.style.borderColor = '';
                }
            }
        }
    }

    // Initialize validator when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.customInputsValidator = new CustomInputsValidator();
        });
    } else {
        window.customInputsValidator = new CustomInputsValidator();
    }
})();
