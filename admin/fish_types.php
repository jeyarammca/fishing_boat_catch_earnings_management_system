<?php
require_once '../config/config.php';
$page_title = 'Manage Fish Types';

if ($_SESSION['role'] != 'admin') {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$message = '';
$action = $_REQUEST['action'] ?? '';
$fish_type_id = $_REQUEST['id'] ?? '';

// Handle Delete (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete' && !empty($fish_type_id)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Invalid CSRF token. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $stmt = $conn->prepare("UPDATE fish_types SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param('i', $fish_type_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Fish type deleted successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
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
        $fish_name = trim($_POST['fish_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $default_rate = $_POST['default_rate'] ?? '';

        if (empty($fish_name) || empty($category)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> All fields are required! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } elseif ($default_rate !== '' && !is_numeric($default_rate)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Default rate must be a number! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
            $default_rate = floatval($default_rate ?: 0);
            if (!empty($_POST['fish_type_id'])) {
                $ftid = intval($_POST['fish_type_id']);
                $stmt = $conn->prepare("UPDATE fish_types SET fish_name = ?, category = ?, default_rate = ? WHERE id = ?");
                $stmt->bind_param('ssdi', $fish_name, $category, $default_rate, $ftid);
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Fish type updated successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO fish_types (fish_name, category, default_rate) VALUES (?, ?, ?)");
                $stmt->bind_param('ssd', $fish_name, $category, $default_rate);
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Fish type added successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }
                $stmt->close();
            }
        }
    }
}

// Get all fish types
$fish_types = execute_query("SELECT * FROM fish_types WHERE status = 'active' ORDER BY fish_name");

require_once '../includes/header.php';
?>

<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-fish"></i> Manage Fish Types</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#fishTypeModal">
            <i class="fas fa-plus"></i> Add Fish Type
        </button>
    </div>

    <?php echo $message; ?>

    <!-- Fish Types Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fish Name</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Default Rate</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $fish_types->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['fish_name']); ?></strong></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($row['category']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['unit']); ?></td>
                            <td><strong>₹<?php echo number_format($row['default_rate'], 2); ?></strong></td>
                            <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#fishTypeModal" onclick="editFishType(<?php echo htmlspecialchars(json_encode($row)); ?>)">
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

<!-- Fish Type Modal -->
<div class="modal fade" id="fishTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" id="fish_type_form_action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-fish"></i> <span id="modalTitle">Add Fish Type</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="fish_type_id" id="fish_type_id">
                    
                    <div class="mb-3">
                        <label for="fish_name" class="form-label">Fish Name *</label>
                        <input type="text" class="form-control" id="fish_name" name="fish_name" placeholder="e.g., Vanjaram, Tuna" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Category *</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Fish">Fish</option>
                            <option value="Prawn">Prawn</option>
                            <option value="Crab">Crab</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="default_rate" class="form-label">Default Rate per Kg (₹)</label>
                        <input type="number" class="form-control" id="default_rate" name="default_rate" step="0.01" min="0">
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
function editFishType(fishType) {
    document.getElementById('fish_type_id').value = fishType.id;
    document.getElementById('fish_name').value = fishType.fish_name;
    document.getElementById('category').value = fishType.category;
    document.getElementById('default_rate').value = fishType.default_rate;
    document.getElementById('modalTitle').textContent = 'Edit Fish Type';
    document.getElementById('fish_type_form_action').value = 'edit';
}

document.getElementById('fishTypeModal')?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('fishTypeModal').querySelector('form').reset();
    document.getElementById('fish_type_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add Fish Type';
    document.getElementById('fish_type_form_action').value = 'add';
});
</script>

<?php require_once '../includes/footer.php'; ?>
