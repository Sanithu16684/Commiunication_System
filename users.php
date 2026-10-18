<?php
// users.php - User Accounts Management (Admin only)
$pageTitle = 'User Accounts & Roles';
$pageIcon = 'bi-people-fill';
require_once __DIR__ . '/includes/header.php';
require_admin();

$msg = '';
$err = '';

// Handle Create / Update / Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $name = trim($_POST['full_name'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'cashier']) ? $_POST['role'] : 'cashier';
        $pass = $_POST['password'] ?? '';

        if ($username === '' || $name === '' || $pass === '') {
            $err = 'All fields are required.';
        } else {
            // Check username uniqueness
            $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $chk->execute([$username]);
            if ($chk->fetch()) {
                $err = 'Username is already taken.';
            } else {
                $hashed = password_hash($pass, PASSWORD_BCRYPT);
                $isView = (isset($_POST['is_view']) && $_POST['is_view'] === '0') ? 0 : 1;
                $st = $pdo->prepare("INSERT INTO users (username, password, full_name, role, status, isView) VALUES (?, ?, ?, ?, 'active', ?)");
                $st->execute([$username, $hashed, $name, $role, $isView]);
                log_activity($pdo, $user['id'], 'Created User', 'Users', "Created user account: $username ($role, isView=$isView)");
                $msg = 'User account created successfully.';
            }
        }
    } elseif ($action === 'toggle_status') {
        $userId = (int)($_POST['id'] ?? 0);
        if ($userId && $userId !== $user['id']) {
            $st = $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
            $st->execute([$userId]);
            log_activity($pdo, $user['id'], 'Toggled User Status', 'Users', "Toggled status for user ID #$userId");
            $msg = 'User status updated.';
        }
    } elseif ($action === 'hide_view') {
        $userId = (int)($_POST['id'] ?? 0);
        if ($userId && $userId !== $user['id']) {
            $st = $pdo->prepare("UPDATE users SET isView = 0 WHERE id = ?");
            $st->execute([$userId]);
            log_activity($pdo, $user['id'], 'Hidden User From View', 'Users', "Set isView=0 for user ID #$userId");
            $msg = 'User account hidden from User Accounts view. You can restore or manage visibility anytime in User Profile.';
        }
    } elseif ($action === 'update_password') {
        $userId = (int)($_POST['id'] ?? 0);
        $newPass = $_POST['password'] ?? '';
        if ($userId && $newPass !== '') {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            $st = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $st->execute([$hashed, $userId]);
            log_activity($pdo, $user['id'], 'Reset Password', 'Users', "Reset password for user ID #$userId");
            $msg = 'Password updated successfully.';
        }
    }
}

// User Accounts only views accounts where isView = 1
$users = $pdo->query("SELECT * FROM users WHERE isView = 1 ORDER BY role ASC, full_name ASC")->fetchAll();
$hiddenCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE isView = 0")->fetchColumn();
?>

<div class="page-container">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h3 style="font-size: 18px; font-weight: 700; color: #0f172a;">Staff & User Accounts</h3>
            <p style="font-size: 13px; color: #64748b;">Manage cashiers and administrator access credentials 
                <!-- (Displaying only <code>isView = 1</code>) -->
            .</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <!-- <a href="userprofile.php#user-viewing-control" class="btn btn-outline" title="Manage User Viewing Controls in User Profile">
                <i class="bi bi-eye-slash-fill" style="color: var(--primary);"></i>
                <span>Viewing Controls in Profile</span>
                <?php if ($hiddenCount > 0): ?>
                <span class="badge badge-warning" style="margin-left: 4px;"><?= $hiddenCount ?> Hidden</span>
                <?php endif; ?>
            </a> -->
            <button type="button" class="btn btn-primary" onclick="openModal('create-user-modal')">
                <i class="bi bi-person-plus-fill"></i> Add New User
            </button>
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if ($hiddenCount > 0): ?>
    <!-- <div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-info-circle-fill" style="font-size: 18px;"></i>
            <span>Displaying <strong><?= count($users) ?></strong> user account(s) where <strong>isView = 1</strong>. (<strong><?= $hiddenCount ?></strong> account<?= $hiddenCount > 1 ? 's are' : '' ?> currently hidden).</span>
        </div>
        <a href="userprofile.php#user-viewing-control" class="btn btn-outline btn-sm" style="background: white;">
            <i class="bi bi-sliders"></i> Open Viewing Control Center
        </a>
    </div> -->
    <?php endif; ?>

    <div class="content-card">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th style="text-align: center;">Status</th>
                        <th>Created At</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="bi bi-people" style="font-size: 32px; display: block; margin-bottom: 8px; color: #94a3b8;"></i>
                            No users found with isView = 1.
                            <?php if ($hiddenCount > 0): ?>
                            <br><a href="userprofile.php#user-viewing-control" style="color: var(--primary); text-decoration: underline;">Review <?= $hiddenCount ?> hidden user(s) in User Profile</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                        <td style="font-family: var(--font-mono); color: var(--primary);"><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'admin' ? 'badge-purple' : 'badge-info' ?>">
                                <?= strtoupper($u['role']) ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                                <?= strtoupper($u['status']) ?>
                            </span>
                        </td>
                        <td style="font-size: 13px; color: #64748b;"><?= $u['created_at'] ?></td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 6px;">
                                
                                <?php if ($u['id'] !== $user['id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle active status for this user?');">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" style="<?= $u['status'] === 'active' ? 'color: var(--danger);' : 'color: var(--accent);' ?>" title="Toggle Active/Inactive">
                                        <i class="bi <?= $u['status'] === 'active' ? 'bi-person-dash' : 'bi-person-check' ?>"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline btn-sm" onclick="openResetModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" title="Reset Password">
                                    <i class="bi bi-key"></i> Password
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Create User -->
<div class="modal-backdrop" id="create-user-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-person-plus-fill" style="color: var(--primary);"></i>
                <span>Add New User Account</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('create-user-modal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="e.g. Ruwan Perera">
                </div>
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" required placeholder="e.g. cashier2">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="cashier">Cashier (POS & Invoices Access)</option>
                        <option value="admin">Administrator (Full Access)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Viewing Visibility (isView)</label>
                    <select name="is_view" class="form-select">
                        <option value="1" selected>Visible in User Accounts (isView = 1 - Default)</option>
                        <option value="0">Hidden from User Accounts (isView = 0)</option>
                    </select>
                    <span style="font-size: 11px; color: #64748b;">Controls whether this account appears in the User Accounts table.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Initial Password *</label>
                    <input type="password" name="password" class="form-control" required placeholder="Enter password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('create-user-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reset Password -->
<div class="modal-backdrop" id="reset-password-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-key" style="color: var(--primary);"></i>
                <span>Reset User Password</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('reset-password-modal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_password">
            <input type="hidden" name="id" id="reset-user-id">
            <div class="modal-body">
                <p style="font-size: 13.5px; color: #475569; margin-bottom: 16px;">
                    Updating password for user: <strong id="reset-user-name" style="color: var(--primary);"></strong>
                </p>
                <div class="form-group">
                    <label class="form-label">New Password *</label>
                    <input type="password" name="password" class="form-control" required placeholder="Enter new password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('reset-password-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function openResetModal(id, username) {
    document.getElementById('reset-user-id').value = id;
    document.getElementById('reset-user-name').textContent = username;
    openModal('reset-password-modal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
