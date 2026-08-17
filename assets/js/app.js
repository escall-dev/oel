// QPTEO Electronic Logbook System JavaScript Handler

document.addEventListener('DOMContentLoaded', function () {
    const categorySelect = document.getElementById('category_id');
    const typeSelect = document.getElementById('document_type_id');
    const checklistContainer = document.getElementById('attachment_checklist_container');
    const refInput = document.getElementById('reference_number');
    const refFeedback = document.getElementById('reference_feedback');

    // Cascading Dropdown: Category -> Document Types
    if (categorySelect && typeSelect) {
        categorySelect.addEventListener('change', function () {
            const categoryId = this.value;
            typeSelect.innerHTML = '<option value="">-- Select Document Type --</option>';
            if (checklistContainer) {
                checklistContainer.innerHTML = '<div class="text-muted small italic">Select a document type to view attachment checklist.</div>';
            }

            if (!categoryId) return;

            fetch(`api.php?action=get_types&category_id=${categoryId}`)
                .then(response => response.json())
                .then(types => {
                    if (types.length === 0) {
                        typeSelect.innerHTML = '<option value="">-- No types available --</option>';
                    } else {
                        typeSelect.innerHTML = '<option value="">-- Select Document Type --</option>';
                        types.forEach(t => {
                            const option = document.createElement('option');
                            option.value = t.id;
                            option.textContent = t.type_name;
                            typeSelect.appendChild(option);
                        });
                    }
                })
                .catch(err => console.error('Error fetching document types:', err));
        });
    }

    // Cascading Dropdown: Type -> Attachment Items
    if (typeSelect && checklistContainer) {
        typeSelect.addEventListener('change', function () {
            const typeId = this.value;
            checklistContainer.innerHTML = '';

            if (!typeId) {
                checklistContainer.innerHTML = '<div class="text-muted small">Select a document type to view attachment checklist.</div>';
                return;
            }

            fetch(`api.php?action=get_attachments&type_id=${typeId}`)
                .then(response => response.json())
                .then(items => {
                    let html = '';
                    if (items.length > 0) {
                        html += '<h6 class="fw-bold mb-2 text-primary" style="font-size:0.85rem;">Standard Checklist Items:</h6>';
                        items.forEach(item => {
                            html += `
                                <div class="attachment-item">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input attachment-checkbox" type="checkbox" name="attachment_items[]" value="${item.id}" id="attach_item_${item.id}">
                                        <label class="form-check-label fw-semibold text-dark" for="attach_item_${item.id}">
                                            ${item.item_name}
                                        </label>
                                    </div>
                                    <div class="ms-4 mt-1">
                                        <input type="file" class="form-control form-control-sm" name="attachment_files_${item.id}">
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        html += '<div class="alert alert-info py-2 small mb-2"><i class="bi bi-info-circle me-1"></i> No standard attachment checklist for this type. You may attach custom files below.</div>';
                    }

                    // Add Custom Attachments Section
                    html += `
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-secondary" style="font-size:0.85rem;">Additional / Custom Attachments:</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn_add_custom_file"><i class="bi bi-plus-circle me-1"></i> Add Custom File</button>
                        </div>
                        <div id="custom_attachments_list"></div>
                    `;

                    checklistContainer.innerHTML = html;

                    // Bind dynamic Custom Attachment add button
                    const addCustomBtn = document.getElementById('btn_add_custom_file');
                    if (addCustomBtn) {
                        addCustomBtn.addEventListener('click', addCustomFileRow);
                    }
                })
                .catch(err => console.error('Error fetching attachment checklist:', err));
        });
    }

    function addCustomFileRow() {
        const customList = document.getElementById('custom_attachments_list');
        if (!customList) return;
        const rowIndex = Date.now();
        const row = document.createElement('div');
        row.className = 'attachment-item bg-light border p-2 mb-2 rounded position-relative';
        row.innerHTML = `
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-sm" name="custom_names[]" placeholder="Document / File Label (e.g. Endorsement)" required>
                </div>
                <div class="col-md-6">
                    <input type="file" class="form-control form-control-sm" name="custom_files[]" required>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-custom-row">&times;</button>
                </div>
            </div>
        `;
        customList.appendChild(row);

        row.querySelector('.remove-custom-row').addEventListener('click', function () {
            window.showConfirmModal({
                title: 'Remove Attachment Row',
                header: 'Remove Custom File Entry?',
                body: 'Are you sure you want to remove this file attachment field?',
                btnText: 'Yes, Remove',
                btnClass: 'btn-danger',
                onConfirm: function () {
                    row.remove();
                }
            });
        });
    }

    // Live Uniqueness Check for Reference Number
    if (refInput && refFeedback) {
        let timer = null;
        refInput.addEventListener('input', function () {
            clearTimeout(timer);
            const refVal = this.value.trim();
            const docId = document.getElementById('document_id') ? document.getElementById('document_id').value : 0;

            if (refVal === '') {
                refFeedback.innerHTML = '';
                refInput.classList.remove('is-invalid', 'is-valid');
                return;
            }

            timer = setTimeout(() => {
                fetch(`api.php?action=check_reference&ref=${encodeURIComponent(refVal)}&exclude_id=${docId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.exists) {
                            refFeedback.innerHTML = '<span class="text-danger small"><i class="bi bi-x-circle me-1"></i> Reference Number already exists in the database!</span>';
                            refInput.classList.add('is-invalid');
                            refInput.classList.remove('is-valid');
                        } else {
                            refFeedback.innerHTML = '<span class="text-success small"><i class="bi bi-check-circle me-1"></i> Reference Number is available.</span>';
                            refInput.classList.add('is-valid');
                            refInput.classList.remove('is-invalid');
                        }
                    })
                    .catch(err => console.error('Error checking reference:', err));
            }, 300);
        });
    }

    // Global Uniform Modal Helper Function
    window.showConfirmModal = function (options) {
        const modalEl = document.getElementById('globalConfirmModal');
        if (!modalEl) return;

        const titleEl = document.getElementById('confirmModalTitle');
        const headerEl = document.getElementById('confirmModalHeader');
        const bodyEl = document.getElementById('confirmModalBody');
        const actionBtn = document.getElementById('confirmModalBtnAction');

        if (titleEl) titleEl.textContent = options.title || 'Confirm Action';
        if (headerEl) headerEl.textContent = options.header || 'Are you sure?';
        if (bodyEl) bodyEl.innerHTML = options.body || 'This action cannot be undone.';
        if (actionBtn) {
            actionBtn.textContent = options.btnText || 'Yes, Delete';
            actionBtn.className = `btn btn-sm px-4 fw-semibold shadow-sm ${options.btnClass || 'btn-danger'}`;
        }

        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Remove previous listeners
        const newBtn = actionBtn.cloneNode(true);
        actionBtn.parentNode.replaceChild(newBtn, actionBtn);

        newBtn.addEventListener('click', function () {
            bsModal.hide();
            if (typeof options.onConfirm === 'function') {
                options.onConfirm();
            }
        });

        bsModal.show();
    };

    // Uniform Delete Confirmation for Single Forms
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.classList.contains('confirm-delete-form')) {
            if (form.dataset.confirmed === 'true') {
                return true; // Allow submit
            }
            e.preventDefault();
            const msg = form.dataset.confirmMsg || 'Are you sure you want to delete this record?';
            const title = form.dataset.confirmTitle || 'Confirm Deletion';
            const header = form.dataset.confirmHeader || 'Delete Record';

            window.showConfirmModal({
                title: title,
                header: header,
                body: msg,
                btnText: 'Yes, Delete',
                btnClass: 'btn-danger',
                onConfirm: function () {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }
            });
        }
    });

    // Multi-Select Checkbox & Bulk Delete Handling
    const selectAllCheckbox = document.getElementById('selectAllDocs');
    const docCheckboxes = document.querySelectorAll('.doc-checkbox');
    const bulkDeleteBtn = document.getElementById('btnDeleteSelected');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');

    function updateBulkDeleteState() {
        const checked = document.querySelectorAll('.doc-checkbox:checked');
        const count = checked.length;

        if (selectedCountSpan) {
            selectedCountSpan.textContent = count;
        }

        if (bulkDeleteBtn) {
            if (count > 0) {
                bulkDeleteBtn.classList.remove('d-none');
            } else {
                bulkDeleteBtn.classList.add('d-none');
            }
        }

        if (selectAllCheckbox && docCheckboxes.length > 0) {
            selectAllCheckbox.checked = (count === docCheckboxes.length);
            selectAllCheckbox.indeterminate = (count > 0 && count < docCheckboxes.length);
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const isChecked = this.checked;
            docCheckboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            updateBulkDeleteState();
        });
    }

    docCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkDeleteState);
    });

    if (bulkDeleteBtn && bulkDeleteForm) {
        bulkDeleteBtn.addEventListener('click', function () {
            const checked = document.querySelectorAll('.doc-checkbox:checked');
            if (checked.length === 0) {
                return;
            }

            const count = checked.length;
            const message = `Are you sure you want to delete <strong>${count} selected document(s)</strong> and all their associated attachment files?<br><br><span class="text-danger small"><i class="bi bi-exclamation-octagon me-1"></i> This permanent deletion action cannot be undone.</span>`;

            window.showConfirmModal({
                title: 'Confirm Bulk Deletion',
                header: `Delete ${count} Selected Document(s)?`,
                body: message,
                btnText: `Delete ${count} File(s)`,
                btnClass: 'btn-danger',
                onConfirm: function () {
                    bulkDeleteForm.submit();
                }
            });
        });
    }

    // Individual Document Delete Button Listener (avoids nested forms)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-doc');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        const docId = btn.dataset.docId;
        const docRef = btn.dataset.docRef || 'this document';

        window.showConfirmModal({
            title: 'Confirm Deletion',
            header: 'Delete Document?',
            body: `Are you sure you want to delete reference <strong>${docRef}</strong>?<br><br><span class="text-danger small"><i class="bi bi-exclamation-octagon me-1"></i> This action cannot be undone.</span>`,
            btnText: 'Yes, Delete',
            btnClass: 'btn-danger',
            onConfirm: function () {
                // Create a standalone form outside the bulk delete wrapper
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.pathname;
                form.style.display = 'none';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'form_action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'document_id';
                idInput.value = docId;
                form.appendChild(idInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // Individual File Attachment Delete Listener
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-attachment');
        if (!btn) return;
        e.preventDefault();

        const attId = btn.dataset.id;
        const fileName = btn.dataset.name || 'this file attachment';

        window.showConfirmModal({
            title: 'Confirm File Deletion',
            header: 'Delete File Attachment?',
            body: `Are you sure you want to delete file attachment <strong>"${fileName}"</strong>?<br><br><span class="text-danger small"><i class="bi bi-exclamation-octagon me-1"></i> This physical file deletion action cannot be undone.</span>`,
            btnText: 'Yes, Delete File',
            btnClass: 'btn-danger',
            onConfirm: function () {
                const formData = new FormData();
                formData.append('id', attId);
                fetch('api.php?action=delete_attachment', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const row = document.querySelector(`.attachment-row-${attId}`);
                        if (row) {
                            row.remove();
                        }
                    } else {
                        alert(data.error || 'Failed to delete file attachment.');
                    }
                })
                .catch(err => {
                    console.error('Error deleting attachment:', err);
                    alert('An error occurred while deleting the file attachment.');
                });
            }
        });
    });
});

