        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Design Panel Scripts -->
    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('designSidebar');
            const main = document.getElementById('designMain');
            
            if (window.innerWidth > 768) {
                sidebar.classList.toggle('collapsed');
                main.classList.toggle('expanded');
                
                // Save state to localStorage
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            } else {
                sidebar.classList.toggle('show');
            }
        }

        // Restore sidebar state on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth > 768) {
                const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                if (isCollapsed) {
                    document.getElementById('designSidebar').classList.add('collapsed');
                    document.getElementById('designMain').classList.add('expanded');
                }
            }
        });

        // Auto-save functionality
        function autoSave(formData, endpoint) {
            $.ajax({
                url: endpoint,
                method: 'POST',
                data: formData,
                success: function(response) {
                    showToast('Otomatik olarak kaydedildi', 'success');
                },
                error: function() {
                    showToast('Kaydetme hatası', 'error');
                }
            });
        }

        // Toast notification
        function showToast(message, type = 'info', duration = 3000) {
            const toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: duration,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            const icon = type === 'success' ? 'success' : 
                        type === 'error' ? 'error' : 
                        type === 'warning' ? 'warning' : 'info';

            toast.fire({
                icon: icon,
                title: message
            });
        }

        // Confirm delete
        function confirmDelete(title = 'Silmek istediğinizden emin misiniz?', text = 'Bu işlem geri alınamaz!') {
            return Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Evet, sil!',
                cancelButtonText: 'İptal'
            });
        }

        // Image preview
        function previewImage(input, previewElement) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (typeof previewElement === 'string') {
                        previewElement = document.querySelector(previewElement);
                    }
                    if (previewElement) {
                        previewElement.src = e.target.result;
                        previewElement.style.display = 'block';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Color picker handler
        function updateColorPreview(input, preview) {
            if (typeof preview === 'string') {
                preview = document.querySelector(preview);
            }
            if (preview) {
                preview.style.backgroundColor = input.value;
            }
        }

        // Form validation
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;

            const inputs = form.querySelectorAll('[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            return isValid;
        }

        // AJAX form submit
        function submitForm(formId, endpoint, successCallback) {
            if (!validateForm(formId)) {
                showToast('Lütfen tüm gerekli alanları doldurun', 'error');
                return;
            }

            const form = document.getElementById(formId);
            const formData = new FormData(form);

            $.ajax({
                url: endpoint,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    // Show loading state
                    const submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
                    }
                },
                success: function(response) {
                    showToast('İşlem başarıyla tamamlandı', 'success');
                    if (successCallback) successCallback(response);
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Bir hata oluştu';
                    showToast(errorMsg, 'error');
                },
                complete: function() {
                    // Reset button state
                    const submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Kaydet';
                    }
                }
            });
        }

        // Drag and drop file upload
        function initDragDrop(dropZone, fileInput) {
            if (typeof dropZone === 'string') {
                dropZone = document.querySelector(dropZone);
            }
            if (typeof fileInput === 'string') {
                fileInput = document.querySelector(fileInput);
            }

            if (!dropZone || !fileInput) return;

            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                dropZone.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });

            dropZone.addEventListener('click', function() {
                fileInput.click();
            });
        }

        // Auto-resize textarea
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        // Live preview updates
        function updateLivePreview() {
            // This function can be overridden in specific pages
            // to provide real-time preview functionality
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Auto-resize textareas
            document.querySelectorAll('textarea[data-auto-resize]').forEach(textarea => {
                textarea.addEventListener('input', function() {
                    autoResize(this);
                });
                autoResize(textarea);
            });

            // Color pickers
            document.querySelectorAll('input[type="color"]').forEach(colorInput => {
                const preview = document.querySelector(`[data-preview="${colorInput.id}"]`);
                if (preview) {
                    updateColorPreview(colorInput, preview);
                    colorInput.addEventListener('change', () => updateColorPreview(colorInput, preview));
                }
            });
        });

        // Live search functionality
        function liveSearch(searchInput, targetContainer, searchUrl) {
            let debounceTimer;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const query = this.value.trim();
                    
                    if (query.length < 2) {
                        targetContainer.innerHTML = '';
                        return;
                    }

                    $.ajax({
                        url: searchUrl,
                        method: 'GET',
                        data: { q: query },
                        success: function(response) {
                            targetContainer.innerHTML = response;
                        },
                        error: function() {
                            targetContainer.innerHTML = '<p class="text-danger">Arama sırasında hata oluştu</p>';
                        }
                    });
                }, 300);
            });
        }

        // Export functionality
        function exportData(type, endpoint) {
            window.open(`${endpoint}?export=${type}`, '_blank');
        }

        // Bulk operations
        function handleBulkOperation(operation, selectedIds) {
            if (selectedIds.length === 0) {
                showToast('Lütfen en az bir öğe seçin', 'warning');
                return;
            }

            confirmDelete(
                `${selectedIds.length} öğeyi ${operation} yapmak istediğinizden emin misiniz?`,
                'Bu işlem seçili tüm öğeleri etkileyecek!'
            ).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'bulk_operations.php',
                        method: 'POST',
                        data: {
                            operation: operation,
                            ids: selectedIds
                        },
                        success: function(response) {
                            showToast('Toplu işlem başarıyla tamamlandı', 'success');
                            location.reload();
                        },
                        error: function() {
                            showToast('Toplu işlem sırasında hata oluştu', 'error');
                        }
                    });
                }
            });
        }
    </script>

    <!-- Additional page specific scripts -->
    <?php if (isset($additionalJS)): ?>
        <?php echo $additionalJS; ?>
    <?php endif; ?>

    <!-- Show messages if any -->
    <?php if (isset($_GET['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showToast('<?php echo htmlspecialchars($_GET['success']); ?>', 'success');
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showToast('<?php echo htmlspecialchars($_GET['error']); ?>', 'error');
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['warning'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showToast('<?php echo htmlspecialchars($_GET['warning']); ?>', 'warning');
            });
        </script>
    <?php endif; ?>

</body>
</html>
