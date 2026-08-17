<?php
require_once __DIR__ . '/config/db.php';
requireRole('admin');

// Handle User Actions (Add / Edit / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['user_action'] ?? '';

    // DELETE USER
    if ($action === 'delete') {
        $userId = intval($_POST['user_id'] ?? 0);
        // Prevent deleting superadmin 061920 or self
        $stmtUser = $pdo->prepare("SELECT username FROM users WHERE id = :id");
        $stmtUser->execute([':id' => $userId]);
        $u = $stmtUser->fetch();

        if ($u && $u['username'] === '061920') {
            setFlash('danger', 'System Superadmin account (061920) cannot be deleted.');
        } elseif ($userId === (int)$_SESSION['user_id']) {
            setFlash('danger', 'You cannot delete your own currently logged-in account.');
        } else {
            $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmtDel->execute([':id' => $userId]);
            setFlash('success', 'User account deleted successfully.');
        }
        header("Location: users.php");
        exit();
    }

    // SAVE / UPDATE USER
    if ($action === 'save') {
        $userId = intval($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role'] ?? 'viewer');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($fullName) || !in_array($role, ['admin', 'encoder', 'viewer'])) {
            setFlash('danger', 'Please provide valid Employee ID, Full Name, and Role.');
            header("Location: users.php");
            exit();
        }

        // Check Employee ID uniqueness
        if ($userId > 0) {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u AND id != :id");
            $stmtCheck->execute([':u' => $username, ':id' => $userId]);
        } else {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
            $stmtCheck->execute([':u' => $username]);
        }

        if ($stmtCheck->fetchColumn() > 0) {
            setFlash('danger', "Employee ID '$username' is already registered to another user.");
            header("Location: users.php");
            exit();
        }

        if ($userId > 0) {
            // Update User
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmtUp = $pdo->prepare("UPDATE users SET username = :u, full_name = :fn, role = :r, password = :p WHERE id = :id");
                $stmtUp->execute([':u' => $username, ':fn' => $fullName, ':r' => $role, ':p' => $hash, ':id' => $userId]);
            } else {
                $stmtUp = $pdo->prepare("UPDATE users SET username = :u, full_name = :fn, role = :r WHERE id = :id");
                $stmtUp->execute([':u' => $username, ':fn' => $fullName, ':r' => $role, ':id' => $userId]);
            }
            setFlash('success', 'User account updated successfully.');
        } else {
            // Insert New User
            if (empty($password)) {
                setFlash('danger', 'Password is required when creating a new user.');
                header("Location: users.php");
                exit();
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtIns = $pdo->prepare("INSERT INTO users (username, full_name, role, password) VALUES (:u, :fn, :r, :p)");
            $stmtIns->execute([':u' => $username, ':fn' => $fullName, ':r' => $role, ':p' => $hash]);
            setFlash('success', 'New user account created successfully.');
        }

        header("Location: users.php");
        exit();
    }
}

// Fetch all users
$allUsers = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-fluid px-4 py-2">
    <!-- Header Title -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--primary-color);">
                <i class="bi bi-people me-2 text-primary"></i> System User Management
            </h3>
            <p class="text-muted mb-0 small">Manage user credentials, employee IDs, and role permissions (Admin, Encoder, Viewer).</p>
        </div>
        <div class="mt-3 mt-md-0">
            <button type="button" class="btn btn-primary-custom shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetUserForm()">
                <i class="bi bi-person-plus me-1"></i> Add New User
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card card-custom">
        <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold mb-0 text-dark">
                <i class="bi bi-person-lines-fill me-2 text-primary"></i> Registered User Accounts
            </h5>
            <span class="badge bg-light text-muted border"><?= count($allUsers) ?> accounts</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee ID (Username)</th>
                            <th>Full Name</th>
                            <th>Role Permission</th>
                            <th>Created Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $u): ?>
                        <tr>
                            <td class="fw-bold text-muted"><?= $u['id'] ?></td>
                            <td class="fw-bold text-dark font-monospace"><?= sanitize($u['username']) ?></td>
                            <td class="fw-semibold text-dark"><?= sanitize($u['full_name']) ?></td>
                            <td>
                                <span class="badge badge-role badge-role-<?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span>
                            </td>
                            <td class="small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 me-1" 
                                        onclick='editUser(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </button>
                                 <?php if ($u['username'] !== '061920' && $u['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="POST" action="users.php" class="d-inline confirm-delete-form" data-confirm-title="Confirm User Deletion" data-confirm-header="Delete User Account?" data-confirm-msg="Are you sure you want to delete user <strong><?= sanitize($u['username']) ?></strong>? This action cannot be undone.">
                                    <input type="hidden" name="user_action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2">
                                        <i class="bi bi-trash me-1"></i> Delete
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit User -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <form method="POST" action="users.php" class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #040484 0%, #020257 100%);">
                <h5 class="modal-title fw-bold" id="userModalTitle"><i class="bi bi-person-plus me-2"></i> Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="user_action" value="save">
                <input type="hidden" name="user_id" id="user_id" value="0">

                <div class="mb-3">
                    <label for="user_username" class="form-label">Employee ID (Username) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="user_username" name="username" required placeholder="e.g. 061920">
                </div>

                <div class="mb-3">
                    <label for="user_full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="user_full_name" name="full_name" required placeholder="e.g. Juan Dela Cruz">
                </div>

                <div class="mb-3">
                    <label for="user_role" class="form-label">Role Permission <span class="text-danger">*</span></label>
                    <select class="form-select" id="user_role" name="role" required>
                        <option value="viewer">Viewer (Read Only)</option>
                        <option value="encoder">Encoder (Add & Edit Documents)</option>
                        <option value="admin">Admin (Full System Access)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="user_password" class="form-label" id="passwordLabel">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="user_password" name="password" placeholder="Enter password">
                    <div class="form-text small" id="passwordHelp">Leave blank when editing if keeping existing password.</div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-custom px-4"><i class="bi bi-save me-1"></i> Save Account</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetUserForm() {
    document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-person-plus me-2"></i> Add New User';
    document.getElementById('user_id').value = '0';
    document.getElementById('user_username').value = '';
    document.getElementById('user_full_name').value = '';
    document.getElementById('user_role').value = 'viewer';
    document.getElementById('user_password').value = '';
    document.getElementById('user_password').required = true;
    document.getElementById('passwordHelp').style.display = 'none';
}

function editUser(user) {
    document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i> Edit User Account';
    document.getElementById('user_id').value = user.id;
    document.getElementById('user_username').value = user.username;
    document.getElementById('user_full_name').value = user.full_name;
    document.getElementById('user_role').value = user.role;
    document.getElementById('user_password').value = '';
    document.getElementById('user_password').required = false;
    document.getElementById('passwordHelp').style.display = 'block';

    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
