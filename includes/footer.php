    <footer class="mt-auto py-3 bg-white border-top">
        <div class="container-fluid px-4 text-center text-muted small">
            <span>&copy; <?= date('Y') ?> <strong>QPTEO Electronic Logbook System</strong>. All rights reserved.</span>
        </div>
    </footer>

    <!-- Global Uniform Confirmation Modal -->
    <div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-labelledby="globalConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 12px;">
                <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #040484 0%, #020257 100%);">
                    <h5 class="modal-title fw-bold fs-6 d-flex align-items-center" id="globalConfirmModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>
                        <span id="confirmModalTitle">Confirm Action</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <span class="badge rounded-circle bg-danger-subtle text-danger p-3 mb-2 d-inline-flex align-items-center justify-content-center" style="width:64px; height:64px;">
                            <i class="bi bi-trash3-fill fs-2"></i>
                        </span>
                    </div>
                    <h6 class="fw-bold text-dark mb-2" id="confirmModalHeader">Are you sure?</h6>
                    <p class="text-muted small mb-0" id="confirmModalBody">This action cannot be undone.</p>
                </div>
                <div class="modal-footer bg-light border-0 d-flex justify-content-center gap-2 py-3">
                    <button type="button" class="btn btn-sm btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-sm btn-danger px-4 fw-semibold shadow-sm" id="confirmModalBtnAction">
                        <i class="bi bi-check-circle me-1"></i> Yes, Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Application JS -->
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>

