<?php
require_once '../config/config.php';
$page_title = 'Manage Fishermen';

if ($_SESSION['role'] != 'admin') {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$message = '';
$action = $_REQUEST['action'] ?? '';
$fisherman_id = $_REQUEST['id'] ?? '';

// Handle Delete (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete' && !empty($fisherman_id)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Invalid CSRF token. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $stmt = $conn->prepare("UPDATE fishermen SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param('i', $fisherman_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Fisherman deleted successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
        $stmt->close();
    }
}

// Handle Add/Update
// Handle Add/Update (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($action === 'add' || $action === 'edit')) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Invalid CSRF token. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $fisherman_name = trim($_POST['fisherman_name'] ?? '');
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($fisherman_name) || empty($mobile_number) || empty($role)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> All fields are required! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
            // basic mobile validation (digits and +)
            if (!preg_match('/^[0-9+\-\s]{6,20}$/', $mobile_number)) {
                $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Invalid mobile number format! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                if (!empty($_POST['fisherman_id'])) {
                    $fid = intval($_POST['fisherman_id']);
                    $stmt = $conn->prepare("UPDATE fishermen SET fisherman_name = ?, mobile_number = ?, role = ?, address = ? WHERE id = ?");
                    $stmt->bind_param('ssssi', $fisherman_name, $mobile_number, $role, $address, $fid);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Fisherman updated successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    }
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("INSERT INTO fishermen (fisherman_name, mobile_number, role, address) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param('ssss', $fisherman_name, $mobile_number, $role, $address);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Fisherman added successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Get all fishermen
$fishermen = execute_query("SELECT * FROM fishermen WHERE status = 'active' ORDER BY fisherman_name");

require_once '../includes/header.php';
?>

<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users"></i> Manage Fishermen</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#fishermenModal">
            <i class="fas fa-plus"></i> Add Fisherman
        </button>
    </div>

    <?php echo $message; ?>

    <!-- Fishermen Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Mobile Number</th>
                        <th>Role</th>
                        <th>Address</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $fishermen->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['fisherman_name']); ?></strong></td>
                            <td><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['mobile_number']); ?></td>
                            <td><span class="badge bg-<?php echo ($row['role'] == 'Captain') ? 'primary' : 'secondary'; ?>"><?php echo htmlspecialchars($row['role']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#fishermenModal" onclick="editFisherman(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('<?php echo __('are_you_sure'); ?>')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <?php echo csrf_input(); ?>
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Fishermen Modal -->
<div class="modal fade" id="fishermenModal" tabindex="-1">
    <div class="modal-dialog">
            <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" id="fishermen_form_action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user"></i> <span id="modalTitle">Add Fisherman</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="fisherman_id" id="fisherman_id">
                    
                    <div class="mb-3">
                        <label for="fisherman_name" class="form-label">Fisherman Name *</label>
                        <input type="text" class="form-control" id="fisherman_name" name="fisherman_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="mobile_number" class="form-label">Mobile Number *</label>
                        <input type="tel" class="form-control" id="mobile_number" name="mobile_number" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role *</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="Captain">Captain</option>
                            <option value="Crew">Crew</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editFisherman(fisherman) {
    document.getElementById('fisherman_id').value = fisherman.id;
    document.getElementById('fisherman_name').value = fisherman.fisherman_name;
    document.getElementById('mobile_number').value = fisherman.mobile_number;
    document.getElementById('role').value = fisherman.role;
    document.getElementById('address').value = fisherman.address;
    document.getElementById('modalTitle').textContent = 'Edit Fisherman';
    document.getElementById('fishermen_form_action').value = 'edit';
}

document.getElementById('fishermenModal')?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('fishermenModal').querySelector('form').reset();
    document.getElementById('fisherman_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add Fisherman';
    document.getElementById('fishermen_form_action').value = 'add';
});
</script>

<?php require_once '../includes/footer.php'; ?>
