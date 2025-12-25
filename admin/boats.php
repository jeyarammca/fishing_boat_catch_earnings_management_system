<?php
require_once '../config/config.php';
$page_title = 'Manage Boats';

if ($_SESSION['role'] != 'admin') {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$message = '';
$action = $_REQUEST['action'] ?? '';
$boat_id = $_REQUEST['id'] ?? '';

// Handle Delete (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete' && !empty($boat_id)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Invalid CSRF token. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $stmt = $conn->prepare("UPDATE boats SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param('i', $boat_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Boat deleted successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
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
        $boat_name = trim($_POST['boat_name'] ?? '');
        $boat_number = trim($_POST['boat_number'] ?? '');
        $owner_name = trim($_POST['owner_name'] ?? '');
        $owner_contact = trim($_POST['owner_contact'] ?? '');
        $boat_type = trim($_POST['boat_type'] ?? '');
        $registration_number = trim($_POST['registration_number'] ?? '');

        if (empty($boat_name) || empty($boat_number) || empty($owner_name) || empty($boat_type)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> All fields are required! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
            if (!empty($_POST['boat_id'])) {
                // Update
                $bid = intval($_POST['boat_id']);
                $stmt = $conn->prepare("UPDATE boats SET boat_name = ?, boat_number = ?, owner_name = ?, owner_contact = ?, boat_type = ?, registration_number = ? WHERE id = ?");
                $stmt->bind_param('ssssssi', $boat_name, $boat_number, $owner_name, $owner_contact, $boat_type, $registration_number, $bid);
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Boat updated successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }
                $stmt->close();
            } else {
                // Check if boat number already exists
                $stmt = $conn->prepare("SELECT id FROM boats WHERE boat_number = ? LIMIT 1");
                $stmt->bind_param('s', $boat_number);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->fetch_assoc()) {
                    $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Boat number already exists! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $stmt->close();
                } else {
                    $stmt->close();
                    $stmt = $conn->prepare("INSERT INTO boats (boat_name, boat_number, owner_name, owner_contact, boat_type, registration_number) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('ssssss', $boat_name, $boat_number, $owner_name, $owner_contact, $boat_type, $registration_number);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Boat added successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Get boat for edit
$boat = null;
if ($action == 'edit' && !empty($boat_id)) {
    $boat = execute_query("SELECT * FROM boats WHERE id = " . intval($boat_id))->fetch_assoc();
}

// Get all boats
$boats = execute_query("SELECT * FROM boats WHERE status = 'active' ORDER BY boat_name");

require_once '../includes/header.php';
?>

<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-ship"></i> Manage Boats</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#boatModal">
            <i class="fas fa-plus"></i> Add New Boat
        </button>
    </div>

    <?php echo $message; ?>

    <!-- Boats Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Boat Name</th>
                        <th>Boat Number</th>
                        <th>Owner Name</th>
                        <th>Owner Contact</th>
                        <th>Boat Type</th>
                        <th>Registration</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $boats->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['boat_name']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($row['boat_number']); ?></code></td>
                            <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['owner_contact'] ?? ''); ?></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($row['boat_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['registration_number'] ?? ''); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#boatModal" onclick="editBoat(<?php echo htmlspecialchars(json_encode($row)); ?>)">
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

<!-- Boat Modal -->
<div class="modal fade" id="boatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="boatForm">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" id="boat_form_action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-ship"></i> <span id="modalTitle">Add New Boat</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="boat_id" id="boat_id">
                    
                    <div class="mb-3">
                        <label for="boat_name" class="form-label">Boat Name *</label>
                        <input type="text" class="form-control" id="boat_name" name="boat_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="boat_number" class="form-label">Boat Number *</label>
                        <input type="text" class="form-control" id="boat_number" name="boat_number" required>
                    </div>

                    <div class="mb-3">
                        <label for="owner_name" class="form-label">Owner Name *</label>
                        <input type="text" class="form-control" id="owner_name" name="owner_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="owner_contact" class="form-label">Owner Contact</label>
                        <input type="tel" class="form-control" id="owner_contact" name="owner_contact">
                    </div>

                    <div class="mb-3">
                        <label for="boat_type" class="form-label">Boat Type *</label>
                        <select class="form-control" id="boat_type" name="boat_type" required>
                            <option value="">Select Type</option>
                            <option value="Mechanized">Mechanized</option>
                            <option value="Fiber">Fiber</option>
                            <option value="Catamaran">Catamaran</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="registration_number" class="form-label">Registration Number</label>
                        <input type="text" class="form-control" id="registration_number" name="registration_number">
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
function editBoat(boat) {
    document.getElementById('boat_id').value = boat.id;
    document.getElementById('boat_name').value = boat.boat_name;
    document.getElementById('boat_number').value = boat.boat_number;
    document.getElementById('owner_name').value = boat.owner_name;
    document.getElementById('owner_contact').value = boat.owner_contact;
    document.getElementById('boat_type').value = boat.boat_type;
    document.getElementById('registration_number').value = boat.registration_number;
    document.getElementById('modalTitle').textContent = 'Edit Boat';
    document.getElementById('boat_form_action').value = 'edit';
}

const boatModal = document.getElementById('boatModal');
boatModal?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('boatForm').reset();
    document.getElementById('boat_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add New Boat';
    document.getElementById('boat_form_action').value = 'add';
});
</script>

<?php require_once '../includes/footer.php'; ?>
