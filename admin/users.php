<?php
require_once '../config/config.php';
$page_title = 'Manage Users';

if ($_SESSION['role'] != 'admin') {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$message = '';
$action = $_REQUEST['action'] ?? '';
$user_id = $_REQUEST['id'] ?? '';
// (fixed) ensure $message initialized only once

// Handle Delete (only via POST with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete' && !empty($user_id)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Invalid CSRF token. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        // Prevent deleting the current user
        if ($user_id == $_SESSION['user_id']) {
            $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> You cannot delete your own account! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
            // Prevent deleting last admin
            $row = execute_query("SELECT role FROM users WHERE id = " . intval($user_id))->fetch_assoc();
            if ($row && $row['role'] === 'admin') {
                $countAdmin = execute_query("SELECT COUNT(*) as cnt FROM users WHERE role = 'admin' AND status = 'active'")->fetch_assoc()['cnt'];
                if ($countAdmin <= 1) {
                    $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Cannot delete the last admin account. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                } else {
                    $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                    $stmt->bind_param('i', $user_id);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> User deleted successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    }
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                $stmt->bind_param('i', $user_id);
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> User deleted successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }
                $stmt->close();
            }
        }
    }
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($action === 'add' || $action === 'edit')) {
    // CSRF validation
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Invalid CSRF token. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($email) || empty($full_name) || empty($role)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> All required fields must be filled! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Invalid email address! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
            // sanitize for DB using prepared statements
            if (!empty($_POST['user_id'])) {
                // Update
                $uid = intval($_POST['user_id']);
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, mobile = ?, role = ?, password = ? WHERE id = ?");
                    $stmt->bind_param('ssssssi', $username, $email, $full_name, $mobile, $role, $hashed_password, $uid);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, mobile = ?, role = ? WHERE id = ?");
                    $stmt->bind_param('sssssi', $username, $email, $full_name, $mobile, $role, $uid);
                }
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> User updated successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }
                $stmt->close();
            } else {
                // Insert: check username/email uniqueness
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
                $stmt->bind_param('ss', $username, $email);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->fetch_assoc()) {
                    $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Username or email already exists! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $stmt->close();
                } else {
                    $stmt->close();
                    if (empty($password)) {
                        $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Password is required for new users! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $conn->prepare("INSERT INTO users (username, email, full_name, mobile, role, password) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param('ssssss', $username, $email, $full_name, $mobile, $role, $hashed_password);
                        if ($stmt->execute()) {
                            $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> User added successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }
}

// Get user for edit
$user = null;
if ($action == 'edit' && !empty($user_id)) {
    $stmt = $conn->prepare("SELECT id, username, email, full_name, mobile, role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get all users
$users = execute_query("SELECT * FROM users WHERE status = 'active' ORDER BY full_name");

require_once '../includes/header.php';
?>

<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users-cog"></i> Manage Users</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>

    <?php echo $message; ?>

    <!-- Users Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($row['username']); ?></code></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['mobile']); ?></td>
                            <td><span class="badge bg-<?php echo ($row['role'] == 'admin') ? 'danger' : 'secondary'; ?>"><?php echo htmlspecialchars(ucfirst($row['role'])); ?></span></td>
                            <td><span class="badge bg-success"><?php echo __('active') ?? 'Active'; ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#userModal" onclick="editUser(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('<?php echo __('are_you_sure') ?? 'Are you sure?'; ?>')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                        <?php echo csrf_input(); ?>
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" id="form_action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user"></i> <span id="modalTitle">Add New User</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="mobile" class="form-label">Mobile Number</label>
                        <input type="tel" class="form-control" id="mobile" name="mobile">
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role *</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span id="passwordRequired">*</span></label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="text-muted" id="passwordHint">Leave blank to keep the current password (edit mode)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('user_id').value = user.id;
    document.getElementById('username').value = user.username;
    document.getElementById('email').value = user.email;
    document.getElementById('full_name').value = user.full_name;
    document.getElementById('mobile').value = user.mobile;
    document.getElementById('role').value = user.role;
    document.getElementById('password').value = '';
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('passwordRequired').textContent = '';
    document.getElementById('passwordHint').textContent = 'Leave blank to keep the current password';
    document.getElementById('password').required = false;
    document.getElementById('form_action').value = 'edit';
}

const userModal = document.getElementById('userModal');
userModal?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('userModal').querySelector('form').reset();
    document.getElementById('user_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('passwordRequired').textContent = '*';
    document.getElementById('passwordHint').textContent = '';
    document.getElementById('password').required = true;
    document.getElementById('form_action').value = 'add';
});
</script>

<?php require_once '../includes/footer.php'; ?>
