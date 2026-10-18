<?php
// userprofile.php - User Profile & Viewing Control Center
$pageTitle = 'User Profile & Viewing Controls';
$pageIcon = 'bi-person-badge-fill';
require_once __DIR__ . '/includes/header.php';

// Fetch current user details fresh from database
$uStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$uStmt->execute([$user['id']]);
$profile = $uStmt->fetch();

if (!$profile) {
    header("Location: logout.php");
    exit;
}

$msg = '';
$err = '';

// Handle Profile Updates & Viewing Controls
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        if ($fullName === '') {
            $err = 'Full Name cannot be empty.';
        } else {
            $upd = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $upd->execute([$fullName, $user['id']]);
            $_SESSION['user_name'] = $fullName;
            $user['name'] = $fullName;
            $profile['full_name'] = $fullName;
            log_activity($pdo, $user['id'], 'Updated Profile', 'Users', "Updated personal full name to: $fullName");
            $msg = 'Profile information updated successfully.';
        }
    } elseif ($action === 'change_password') {
        $currPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if ($currPass === '' || $newPass === '' || $confirmPass === '') {
            $err = 'Please fill in all password fields.';
        } elseif (!password_verify($currPass, $profile['password'])) {
            $err = 'Current password entered is incorrect.';
        } elseif (strlen($newPass) < 4) {
            $err = 'New password must be at least 4 characters long.';
        } elseif ($newPass !== $confirmPass) {
            $err = 'New password and confirmation do not match.';
        } else {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->execute([$hashed, $user['id']]);
            $profile['password'] = $hashed;
            log_activity($pdo, $user['id'], 'Changed Password', 'Users', "User updated their own password");
            $msg = 'Password changed successfully.';
        }
    } elseif ($action === 'toggle_is_view' && is_admin()) {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $targetView = (int)($_POST['target_view'] ?? 1) === 1 ? 1 : 0;

        if ($targetId <= 0) {
            $err = 'Invalid user account selected.';
        } elseif ($targetId === $user['id'] && $targetView === 0) {
            $err = 'Safety Protection: You cannot hide your own active administrator account from view.';
        } else {
            // Check user exists
            $tStmt = $pdo->prepare("SELECT id, username, full_name, isView FROM users WHERE id = ?");
            $tStmt->execute([$targetId]);
            $targetUser = $tStmt->fetch();

            if (!$targetUser) {
                $err = 'User account not found.';
            } else {
                $upd = $pdo->prepare("UPDATE users SET isView = ? WHERE id = ?");
                $upd->execute([$targetView, $targetId]);
                log_activity($pdo, $user['id'], 'Updated Viewing Control', 'Users', "Changed viewing visibility for user '{$targetUser['username']}' (ID #$targetId) to isView=$targetView");
                $msg = "Viewing status updated for <strong>" . htmlspecialchars($targetUser['full_name']) . "</strong>. Status is now set to <strong>" . ($targetView === 1 ? "Visible (isView = 1)" : "Hidden (isView = 0)") . "</strong>.";
            }
        }
    } elseif ($action === 'restore_all_views' && is_admin()) {
        $upd = $pdo->query("UPDATE users SET isView = 1 WHERE isView = 0");
        $affected = $upd->rowCount();
        log_activity($pdo, $user['id'], 'Restored All User Views', 'Users', "Restored isView=1 for $affected user accounts");
        $msg = "Successfully restored visibility (isView = 1) for $affected user account(s).";
    }
}

// Fetch all users for Admin Viewing Control section
$allUsers = [];
$totalUsersCount = 0;
$visibleCount = 0;
$hiddenCount = 0;

if (is_admin()) {
    $allUsers = $pdo->query("SELECT * FROM users ORDER BY role ASC, full_name ASC")->fetchAll();
    $totalUsersCount = count($allUsers);
    foreach ($allUsers as $u) {
        if ((int)$u['isView'] === 1) {
            $visibleCount++;
        } else {
            $hiddenCount++;
        }
    }
}
?>

<div class="page-container" style="max-width: 1200px;">
    
    <!-- Top Greeting Banner -->
    <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); border-radius: var(--radius-lg); padding: 28px 32px; color: white; margin-bottom: 28px; box-shadow: var(--shadow-md); position: relative; overflow: hidden;">
        <div style="position: absolute; right: -20px; bottom: -30px; font-size: 140px; color: rgba(255, 255, 255, 0.04); pointer-events: none;">
            <i class="bi bi-person-badge"></i>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; position: relative; z-index: 2;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #ec4899, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; box-shadow: 0 4px 16px rgba(0,0,0,0.25); border: 3px solid rgba(255,255,255,0.2);">
                    <?= strtoupper(substr($profile['full_name'] ?: $profile['username'], 0, 1)) ?>
                </div>
                <div>
                    <h2 style="font-size: 24px; font-weight: 800; margin: 0 0 4px 0; color: #ffffff; letter-spacing: -0.02em;">
                        <?= htmlspecialchars($profile['full_name']) ?>
                    </h2>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 13px; color: #c7d2fe;">
                        <span style="font-family: var(--font-mono); color: #a5b4fc;">@<?= htmlspecialchars($profile['username']) ?></span>
                        <span>•</span>
                        <span class="badge <?= $profile['role'] === 'admin' ? 'badge-purple' : 'badge-info' ?>" style="font-weight: 700;">
                            <?= strtoupper($profile['role']) ?>
                        </span>
                        <span>•</span>
                        <span class="badge badge-success">
                            <i class="bi bi-check-circle-fill"></i> <?= strtoupper($profile['status']) ?>
                        </span>
                        <!-- <span>•</span>
                        <span class="badge <?= (int)$profile['isView'] === 1 ? 'badge-success' : 'badge-warning' ?>" title="isView Column Status">
                            <i class="bi <?= (int)$profile['isView'] === 1 ? 'bi-eye-fill' : 'bi-eye-slash-fill' ?>"></i>
                            isView = <?= (int)$profile['isView'] ?>
                        </span> -->
                    </div>
                </div>
            </div>
            
            <?php if (is_admin()): ?>
            <div style="display: flex; gap: 10px;">
                <!-- <a href="#user-viewing-control" class="btn btn-outline btn-sm" style="background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.25); color: white;">
                    <i class="bi bi-sliders"></i> User Viewing Controls (isView)
                </a> -->
                <a href="users.php" class="btn btn-success btn-sm">
                    <i class="bi bi-people-fill"></i> Open User Accounts
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom: 24px;">
        <i class="bi bi-check-circle-fill"></i> <?= $msg ?>
    </div>
    <?php endif; ?>
    <?php if ($err): ?>
    <div class="alert alert-danger" style="margin-bottom: 24px;">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($err) ?>
    </div>
    <?php endif; ?>

    <!-- Personal Account Management Section -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 32px;">
        
        <!-- Form: Personal Info -->
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-flex">
                <div class="card-title">
                    <i class="bi bi-person-lines-fill" style="color: var(--primary);"></i>
                    <span>Profile Details</span>
                </div>
                <span style="font-size: 12px; color: #64748b;">Member since <?= date('M Y', strtotime($profile['created_at'])) ?></span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($profile['full_name']) ?>" placeholder="Your Full Name">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Username (Read-Only)</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($profile['username']) ?>" readonly style="background: #f8fafc; font-family: var(--font-mono); color: #64748b;">
                        <span style="font-size: 11px; color: #94a3b8;">Usernames cannot be changed once assigned.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">System Access Role</label>
                        <input type="text" class="form-control" value="<?= strtoupper($profile['role']) ?> - <?= $profile['role'] === 'admin' ? 'Full Administrator Access' : 'Cashier Point-of-Sale Access' ?>" readonly style="background: #f8fafc; color: #64748b;">
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                        <i class="bi bi-check2"></i> Save Profile Details
                    </button>
                </form>
            </div>
        </div>

        <!-- Form: Change Password -->
        <div class="content-card" style="margin-bottom: 0;">
            <div class="card-header-flex">
                <div class="card-title">
                    <i class="bi bi-shield-lock-fill" style="color: var(--primary);"></i>
                    <span>Change Password</span>
                </div>
                <span style="font-size: 12px; color: #64748b;">Security Credentials</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label class="form-label">Current Password *</label>
                        <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Password *</label>
                        <input type="password" name="new_password" class="form-control" required minlength="4" placeholder="Enter new password (min. 4 chars)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm New Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="4" placeholder="Repeat new password">
                    </div>

                    <button type="submit" class="btn btn-outline" style="margin-top: 10px;">
                        <i class="bi bi-key-fill"></i> Update Password
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Admin Section: USER VIEWING CONTROL CENTER -->
    <?php if (is_admin()): ?>
    <!-- <div id="user-viewing-control" class="content-card"> -->
        <!-- <div class="card-header-flex" style="background: #ffffff; border-bottom: 2px solid var(--border);">
            <div>
                <div class="card-title" style="font-size: 17px;">
                    <i class="bi bi-eye-fill" style="color: var(--primary); font-size: 20px;"></i>
                    <span>User Accounts Viewing Control Center (isView Management)</span>
                </div>
                <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
                    Manage which staff accounts appear in the main <strong>User Accounts</strong> page (<code>isView = 1</code>). Setting a user to <strong>Hidden (isView = 0)</strong> removes them from the User Accounts table while safely preserving all historical invoices and sales records.
                </p>
            </div>
            
            <?php if ($hiddenCount > 0): ?>
            <div>
                <form method="POST" onsubmit="return confirm('Restore visibility (isView = 1) for all hidden accounts?');">
                    <input type="hidden" name="action" value="restore_all_views">
                    <button type="submit" class="btn btn-outline btn-sm" title="Set isView = 1 for all accounts">
                        <i class="bi bi-arrow-counterclockwise" style="color: var(--accent);"></i> Restore All to Visible
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div> -->

        <!-- Metrics & Filter Bar -->
        <!-- <div style="padding: 16px 24px; background: #f8fafc; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;"> -->
            <!-- Filter Pills -->
            <!-- <div style="display: flex; gap: 8px; flex-wrap: wrap;" id="view-filter-buttons">
                <button type="button" class="btn btn-sm btn-primary" onclick="filterViewingTable('all', this)">
                    All Users (<?= $totalUsersCount ?>)
                </button>
                <button type="button" class="btn btn-sm btn-outline" onclick="filterViewingTable('visible', this)">
                    <i class="bi bi-eye-fill" style="color: var(--accent);"></i>
                    Visible Only (<?= $visibleCount ?>)
                </button>
                <button type="button" class="btn btn-sm btn-outline" onclick="filterViewingTable('hidden', this)">
                    <i class="bi bi-eye-slash-fill" style="color: #f59e0b;"></i>
                    Hidden Only (<?= $hiddenCount ?>)
                </button>
            </div> -->

            <!-- Quick Search Input -->
            <!-- <div style="position: relative; max-width: 280px; width: 100%;">
                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px;"></i>
                <input type="text" id="user-view-search" class="form-control form-control-sm" placeholder="Search staff by name or username..." style="padding-left: 32px; font-size: 13px;" onkeyup="searchViewingTable()">
            </div> -->
        <!-- </div> -->

        <!-- User Viewing Control Table -->
        <!-- <div class="table-responsive">
            <table class="custom-table" id="viewing-control-table">
                <thead>
                    <tr>
                        <th>User Account</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th style="text-align: center;">Account Status</th>
                        <th style="text-align: center;">isView Column</th>
                        <th style="text-align: center;">Viewing Visibility</th>
                        <th style="text-align: right;">Viewing Control Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allUsers as $u): 
                        $isV = (int)$u['isView'];
                        $isSelf = ((int)$u['id'] === (int)$user['id']);
                    ?>
                    <tr class="user-row" data-status="<?= $isV === 1 ? 'visible' : 'hidden' ?>" data-search="<?= strtolower(htmlspecialchars($u['full_name'] . ' ' . $u['username'])) ?>">
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 34px; height: 34px; border-radius: 50%; background: <?= $isV === 1 ? 'linear-gradient(135deg, #4f46e5, #0ea5e9)' : '#cbd5e1' ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
                                    <?= strtoupper(substr($u['full_name'] ?: $u['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                                    <?php if ($isSelf): ?>
                                    <span class="badge badge-purple" style="font-size: 10px; margin-left: 4px;">You</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="font-family: var(--font-mono); color: var(--primary); font-weight: 600;">
                            <?= htmlspecialchars($u['username']) ?>
                        </td>
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
                        <td style="text-align: center; font-family: var(--font-mono); font-weight: 700;">
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; background: <?= $isV === 1 ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>">
                                isView = <?= $isV ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($isV === 1): ?>
                            <span class="badge badge-success" title="Visible in User Accounts (users.php)">
                                <i class="bi bi-eye-fill"></i> Visible in User Accounts
                            </span>
                            <?php else: ?>
                            <span class="badge badge-warning" style="background: #fef3c7; color: #b45309; border: 1px dashed #f59e0b;" title="Hidden from User Accounts (users.php)">
                                <i class="bi bi-eye-slash-fill"></i> Hidden from Accounts
                            </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($isSelf): ?>
                            <span class="badge badge-neutral" title="Current session user account cannot be hidden for safety">
                                <i class="bi bi-lock-fill"></i> Protected (Self)
                            </span>
                            <?php else: ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('<?= $isV === 1 ? 'Hide this user from User Accounts view? (They will no longer appear in the User Accounts table)' : 'Restore this user to visible? (They will immediately appear in the User Accounts table with isView = 1)' ?>');">
                                <input type="hidden" name="action" value="toggle_is_view">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <?php if ($isV === 1): ?>
                                <input type="hidden" name="target_view" value="0">
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fca5a5;" title="Hide user from User Accounts view">
                                    <i class="bi bi-eye-slash"></i> Hide User
                                </button>
                                <?php else: ?>
                                <input type="hidden" name="target_view" value="1">
                                <button type="submit" class="btn btn-success btn-sm" title="Show user in User Accounts view (isView = 1)">
                                    <i class="bi bi-eye-fill"></i> Show User (isView = 1)
                                </button>
                                <?php endif; ?>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div> -->

        <!-- <div style="padding: 16px 24px; background: #fafbfc; border-top: 1px solid var(--border); font-size: 13px; color: #475569; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div>
                <i class="bi bi-info-circle-fill" style="color: var(--primary); margin-right: 6px;"></i>
                <strong>Notice:</strong> The <a href="users.php" style="color: var(--primary); font-weight: 600; text-decoration: underline;">User Accounts</a> page strictly displays accounts where <code>isView = 1</code>.
            </div>
            <a href="users.php" class="btn btn-outline btn-sm">
                <i class="bi bi-arrow-right"></i> Go to User Accounts
            </a>
        </div> -->
    <!-- </div> -->
    <?php endif; ?>

</div>

<script>
// Filter viewing table by visible, hidden, or all
function filterViewingTable(type, btn) {
    const buttons = document.querySelectorAll('#view-filter-buttons button');
    buttons.forEach(b => {
        b.classList.remove('btn-primary');
        b.classList.add('btn-outline');
    });
    btn.classList.remove('btn-outline');
    btn.classList.add('btn-primary');

    const rows = document.querySelectorAll('#viewing-control-table .user-row');
    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (type === 'all' || rowStatus === type) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Live search for viewing table
function searchViewingTable() {
    const query = document.getElementById('user-view-search').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#viewing-control-table .user-row');
    rows.forEach(row => {
        const searchData = row.getAttribute('data-search') || '';
        if (query === '' || searchData.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
