// Global fslightbox refresh function for multi-image uploader
function refreshFsLightboxIfAvailable() {
    setTimeout(() => {
        if (typeof refreshFsLightbox === 'function') {
            refreshFsLightbox();
        }
    }, 100);
}

// Multi-Image Uploader Component Factory
window.MultiImageUploader = function(options) {
    const uploaderId = options.uploaderId;
    const dropzoneId = options.dropzoneId;
    const inputId = options.inputId;
    const maxFiles = options.maxFiles;
    const maxSize = options.maxSize;
    const acceptTypes = options.acceptTypes;
    const displayTypes = options.displayTypes;
    const removeRoute = options.removeRoute;
    const entityId = options.entityId;
    const galleryId = options.galleryId;

    document.addEventListener('DOMContentLoaded', function() {
        const dropzone = document.getElementById(dropzoneId);
        const fileInput = document.getElementById(inputId);
        const newImagesSection = document.getElementById('new_images_section_' + uploaderId);
        const newImagesContainer = document.getElementById('new_images_container_' + uploaderId);
        const newCountSpan = document.getElementById('new_count_' + uploaderId);
        const existingCountSpan = document.getElementById('existing_count_' + uploaderId);

        if (!dropzone || !fileInput) return;

        // Click to select files
        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', function(e) {
            handleFiles(Array.from(e.target.files));
        });

        // Drag and drop
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
            
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });

        function handleFiles(files) {
            // Clear previous previews
            newImagesContainer.innerHTML = '';
            
            // Validate and create previews
            const validFiles = files.filter(validateFile);
            
            if (validFiles.length === 0) return;

            // Update file input with valid files only
            const dt = new DataTransfer();
            validFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;

            // Create previews
            validFiles.forEach((file, index) => {
                createPreview(file, index);
            });

            // Show section and update count
            newImagesSection.style.display = 'block';
            newCountSpan.textContent = validFiles.length;
            
            // Refresh fslightbox if available
            refreshFsLightboxIfAvailable();
        }

        function validateFile(file) {
            // More flexible MIME type checking
            const fileExtension = file.name.split('.').pop().toLowerCase();
            const acceptedExtensions = options.acceptedExtensions;
            
            // Check both MIME type and file extension
            const isValidType = acceptTypes.includes(file.type) || acceptedExtensions.includes(fileExtension);
            
            if (!isValidType) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'نوع الملف غير مدعوم',
                        text: `الملف: ${file.name}\nنوع الملف: ${file.type}\nالامتداد: .${fileExtension}\nالأنواع المقبولة: ${displayTypes}`,
                        icon: 'error',
                        confirmButtonText: '<i class="fe fe-check me-1"></i> حسناً',
                        confirmButtonColor: '#3085d6',
                        customClass: {
                            popup: 'swal2-modern-popup',
                            title: 'swal2-modern-title',
                            htmlContainer: 'swal2-modern-text',
                            confirmButton: 'swal2-modern-confirm',
                        }
                    });
                } else {
                    alert(`نوع الملف غير مدعوم: ${file.name}\nالأنواع المقبولة: ${displayTypes}`);
                }
                return false;
            }

            if (file.size > maxSize) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'حجم الملف كبير جداً',
                        text: `الملف: ${file.name}\nالحد الأقصى: ${options.maxFileSizeMB}MB`,
                        icon: 'error',
                        confirmButtonText: '<i class="fe fe-check me-1"></i> حسناً',
                        confirmButtonColor: '#3085d6',
                        customClass: {
                            popup: 'swal2-modern-popup',
                            title: 'swal2-modern-title',
                            htmlContainer: 'swal2-modern-text',
                            confirmButton: 'swal2-modern-confirm',
                        }
                    });
                } else {
                    alert(`حجم الملف كبير جداً: ${file.name}\nالحد الأقصى: ${options.maxFileSizeMB}MB`);
                }
                return false;
            }

            return true;
        }

        function createPreview(file, index) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-auto';
                
                const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
                
                col.innerHTML = `
                    <div class="image-card position-relative" data-new-index="${index}">
                        <div class="card shadow-sm" style="width: 140px; border-radius: 8px; overflow: hidden;">
                            <div class="position-relative" style="height: 100px; overflow: hidden;">
                                <img src="${e.target.result}" 
                                     class="w-100 h-100" 
                                     style="object-fit: cover;"
                                     alt="${file.name}">
                            </div>
                            <div class="card-body p-2 bg-light">
                                <div class="small text-truncate" title="${file.name}">${file.name}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">${sizeInMB} MB</div>
                            </div>
                            <button type="button" 
                                    class="btn btn-danger btn-sm btn-remove-new"
                                    onclick="window.removeNewImage_${uploaderId}(${index})">
                                <i class="fe fe-x" style="font-size: 14px;"></i>
                            </button>
                        </div>
                    </div>
                `;
                
                newImagesContainer.appendChild(col);
            };
            
            reader.readAsDataURL(file);
        }

        // Remove new image (before upload)
        window['removeNewImage_' + uploaderId] = function(index) {
            const dt = new DataTransfer();
            Array.from(fileInput.files).forEach((file, i) => {
                if (i !== index) {
                    dt.items.add(file);
                }
            });
            fileInput.files = dt.files;
            
            newImagesContainer.innerHTML = '';
            Array.from(fileInput.files).forEach((file, i) => {
                createPreview(file, i);
            });
            
            const count = fileInput.files.length;
            newCountSpan.textContent = count;
            
            if (count === 0) {
                newImagesSection.style.display = 'none';
            }
        };

        // Handle existing image removal via AJAX
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remove-existing')) {
                const btn = e.target.closest('.btn-remove-existing');
                const identifier = btn.getAttribute('data-identifier');
                const imageCard = btn.closest('.image-card');
                const overlay = imageCard.querySelector('.removing-overlay');
                
                if (!removeRoute || removeRoute === '') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'خطأ',
                            text: 'لم يتم تحديد مسار الحذف',
                            icon: 'error',
                            confirmButtonText: '<i class="fe fe-check me-1"></i> حسناً',
                            confirmButtonColor: '#3085d6',
                            customClass: {
                                popup: 'swal2-modern-popup',
                                title: 'swal2-modern-title',
                                htmlContainer: 'swal2-modern-text',
                                confirmButton: 'swal2-modern-confirm',
                            }
                        });
                    } else {
                        alert('لم يتم تحديد مسار الحذف');
                    }
                    return;
                }
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'تأكيد الحذف',
                        text: 'هل أنت متأكد من حذف هذه الصورة؟',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fe fe-trash-2 me-1"></i> حذف',
                        cancelButtonText: '<i class="fe fe-x me-1"></i> إلغاء',
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        reverseButtons: true,
                        customClass: {
                            popup: 'swal2-modern-popup',
                            title: 'swal2-modern-title',
                            htmlContainer: 'swal2-modern-text',
                            confirmButton: 'swal2-modern-confirm',
                            cancelButton: 'swal2-modern-cancel',
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            performImageRemoval(imageCard, identifier, overlay);
                        }
                    });
                } else {
                    if (confirm('هل أنت متأكد من حذف هذه الصورة؟')) {
                        performImageRemoval(imageCard, identifier, overlay);
                    }
                }
            }
        });

        function performImageRemoval(imageCard, identifier, overlay) {
            imageCard.classList.add('removing');
            if (overlay) overlay.style.display = 'flex';
            
            fetch(removeRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    identifier: identifier,
                    entity_id: entityId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'تم الحذف',
                            text: 'تم حذف الصورة بنجاح',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                            customClass: {
                                popup: 'swal2-modern-popup',
                                title: 'swal2-modern-title',
                            }
                        });
                    }
                    
                    imageCard.style.opacity = '0';
                    imageCard.style.transform = 'scale(0.5)';
                    
                    setTimeout(() => {
                        imageCard.closest('.col-auto').remove();
                        
                        const existingCountSpan = document.getElementById('existing_count_' + uploaderId);
                        if (existingCountSpan) {
                            const currentCount = document.querySelectorAll('#existing_images_container_' + uploaderId + ' .image-card').length;
                            existingCountSpan.textContent = currentCount;
                        }
                        
                        refreshFsLightboxIfAvailable();
                    }, 300);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'خطأ',
                            text: data.message || 'حدث خطأ أثناء حذف الصورة',
                            icon: 'error',
                            confirmButtonText: '<i class="fe fe-check me-1"></i> حسناً',
                            confirmButtonColor: '#3085d6',
                            customClass: {
                                popup: 'swal2-modern-popup',
                                title: 'swal2-modern-title',
                                htmlContainer: 'swal2-modern-text',
                                confirmButton: 'swal2-modern-confirm',
                            }
                        });
                    } else {
                        alert(data.message || 'حدث خطأ أثناء حذف الصورة');
                    }
                    imageCard.classList.remove('removing');
                    if (overlay) overlay.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'خطأ',
                        text: 'حدث خطأ أثناء حذف الصورة',
                        icon: 'error',
                        confirmButtonText: '<i class="fe fe-check me-1"></i> حسناً',
                        confirmButtonColor: '#3085d6',
                        customClass: {
                            popup: 'swal2-modern-popup',
                            title: 'swal2-modern-title',
                            htmlContainer: 'swal2-modern-text',
                            confirmButton: 'swal2-modern-confirm',
                        }
                    });
                } else {
                    alert('حدث خطأ أثناء حذف الصورة');
                }
                imageCard.classList.remove('removing');
                if (overlay) overlay.style.display = 'none';
            });
        }
        
        refreshFsLightboxIfAvailable();
    });
};
