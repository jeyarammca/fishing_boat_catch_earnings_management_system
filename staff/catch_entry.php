<?php
require_once '../config/config.php';
$page_title = 'Daily Catch Entry';

if (!in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$message = '';
$action = $_GET['action'] ?? '';
$catch_id = $_GET['id'] ?? '';

// Handle Delete
if ($action == 'delete' && !empty($catch_id)) {
    $catch = execute_query("SELECT trip_id, quantity_kg, total_amount FROM daily_catch WHERE id = " . intval($catch_id))->fetch_assoc();
    if ($catch) {
        // Update trip totals
        execute_query("
            UPDATE boat_trips SET 
            total_catch_kg = total_catch_kg - " . $catch['quantity_kg'] . ",
            total_income = total_income - " . $catch['total_amount'] . "
            WHERE id = " . $catch['trip_id']
        );
        execute_query("DELETE FROM daily_catch WHERE id = " . intval($catch_id));
        $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Catch record deleted successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trip_id = intval($_POST['trip_id'] ?? 0);
    $fish_type_id = intval($_POST['fish_type_id'] ?? 0);
    $quantity_kg = floatval($_POST['quantity_kg'] ?? 0);
    $rate_per_kg = floatval($_POST['rate_per_kg'] ?? 0);
    $catch_date = escape_string($_POST['catch_date'] ?? '');
    $notes = escape_string($_POST['notes'] ?? '');

    if (empty($trip_id) || empty($fish_type_id) || empty($quantity_kg) || empty($rate_per_kg) || empty($catch_date)) {
        $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> All fields are required! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $total_amount = $quantity_kg * $rate_per_kg;

        if ($_POST['catch_id'] ?? '' != '') {
            // Update
            $old_catch = execute_query("SELECT trip_id, quantity_kg, total_amount FROM daily_catch WHERE id = " . intval($_POST['catch_id']))->fetch_assoc();
            
            // Update trip totals
            execute_query("
                UPDATE boat_trips SET 
                total_catch_kg = total_catch_kg - " . $old_catch['quantity_kg'] . " + $quantity_kg,
                total_income = total_income - " . $old_catch['total_amount'] . " + $total_amount
                WHERE id = " . $old_catch['trip_id']
            );

            $query = "UPDATE daily_catch SET fish_type_id = $fish_type_id, quantity_kg = $quantity_kg, rate_per_kg = $rate_per_kg, total_amount = $total_amount, catch_date = '$catch_date', notes = '$notes' WHERE id = " . intval($_POST['catch_id']);
            if (execute_query($query)) {
                $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Catch record updated successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } else {
            // Insert
            $query = "INSERT INTO daily_catch (trip_id, fish_type_id, quantity_kg, rate_per_kg, total_amount, catch_date, notes) VALUES ($trip_id, $fish_type_id, $quantity_kg, $rate_per_kg, $total_amount, '$catch_date', '$notes')";
            if (execute_query($query)) {
                // Update trip totals
                execute_query("
                    UPDATE boat_trips SET 
                    total_catch_kg = total_catch_kg + $quantity_kg,
                    total_income = total_income + $total_amount
                    WHERE id = $trip_id
                ");
                $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Catch record added successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        }
    }
}

// Get all trips
$trips = execute_query("
    SELECT bt.id, bt.trip_id_auto, b.boat_name, bt.trip_date
    FROM boat_trips bt
    JOIN boats b ON bt.boat_id = b.id
    ORDER BY bt.trip_date DESC
");

// Get all fish types
$fish_types = execute_query("SELECT * FROM fish_types WHERE status = 'active' ORDER BY fish_name");

// Get catch for edit
$catch_record = null;
if ($action == 'edit' && !empty($catch_id)) {
    $catch_record = execute_query("SELECT * FROM daily_catch WHERE id = " . intval($catch_id))->fetch_assoc();
}

// Get all catch records
$catches = execute_query("
    SELECT dc.*, bt.trip_id_auto, b.boat_name, ft.fish_name
    FROM daily_catch dc
    JOIN boat_trips bt ON dc.trip_id = bt.id
    JOIN boats b ON bt.boat_id = b.id
    JOIN fish_types ft ON dc.fish_type_id = ft.id
    ORDER BY dc.catch_date DESC, dc.created_at DESC
");

require_once '../includes/header.php';
?>

<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-fish"></i> Daily Catch Entry</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#catchModal">
            <i class="fas fa-plus"></i> Add Catch Record
        </button>
    </div>

    <?php echo $message; ?>

    <!-- Catch Records Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Trip ID</th>
                        <th>Boat</th>
                        <th>Date</th>
                        <th>Fish Type</th>
                        <th>Quantity (Kg)</th>
                        <th>Rate/Kg</th>
                        <th>Total Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $catches->fetch_assoc()): ?>
                        <tr>
                            <td><code><?php echo $row['trip_id_auto']; ?></code></td>
                            <td><?php echo $row['boat_name']; ?></td>
                            <td><?php echo date('d-M-Y', strtotime($row['catch_date'])); ?></td>
                            <td><strong><?php echo $row['fish_name']; ?></strong></td>
                            <td><?php echo number_format($row['quantity_kg'], 2); ?></td>
                            <td>₹<?php echo number_format($row['rate_per_kg'], 2); ?></td>
                            <td><strong>₹<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#catchModal" onclick="editCatch(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Catch Modal -->
<div class="modal fade" id="catchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-fish"></i> <span id="modalTitle">Add Catch Record</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="catch_id" id="catch_id">
                    
                    <div class="mb-3">
                        <label for="trip_id" class="form-label">Trip *</label>
                        <select class="form-control" id="trip_id" name="trip_id" required>
                            <option value="">Select Trip</option>
                            <?php 
                            $trips->data_seek(0);
                            while ($trip = $trips->fetch_assoc()): ?>
                                <option value="<?php echo $trip['id']; ?>"><?php echo $trip['trip_id_auto']; ?> - <?php echo $trip['boat_name']; ?> (<?php echo date('d-M-Y', strtotime($trip['trip_date'])); ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="fish_type_id" class="form-label">Fish Type *</label>
                        <select class="form-control" id="fish_type_id" name="fish_type_id" required onchange="updateDefaultRate()">
                            <option value="">Select Fish Type</option>
                            <?php 
                            $fish_types->data_seek(0);
                            while ($ft = $fish_types->fetch_assoc()): ?>
                                <option value="<?php echo $ft['id']; ?>" data-rate="<?php echo $ft['default_rate']; ?>"><?php echo $ft['fish_name']; ?> (₹<?php echo number_format($ft['default_rate'], 2); ?>/Kg)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quantity_kg" class="form-label">Quantity (Kg) *</label>
                        <input type="number" class="form-control" id="quantity_kg" name="quantity_kg" step="0.01" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label for="rate_per_kg" class="form-label">Rate per Kg (₹) *</label>
                        <input type="number" class="form-control" id="rate_per_kg" name="rate_per_kg" step="0.01" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label for="catch_date" class="form-label">Catch Date *</label>
                        <input type="date" class="form-control" id="catch_date" name="catch_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
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
function editCatch(catchRecord) {
    document.getElementById('catch_id').value = catchRecord.id;
    document.getElementById('trip_id').value = catchRecord.trip_id;
    document.getElementById('fish_type_id').value = catchRecord.fish_type_id;
    document.getElementById('quantity_kg').value = catchRecord.quantity_kg;
    document.getElementById('rate_per_kg').value = catchRecord.rate_per_kg;
    document.getElementById('catch_date').value = catchRecord.catch_date;
    document.getElementById('notes').value = catchRecord.notes || '';
    document.getElementById('modalTitle').textContent = 'Edit Catch Record';
}

function updateDefaultRate() {
    const selected = document.getElementById('fish_type_id').options[document.getElementById('fish_type_id').selectedIndex];
    const rate = selected.getAttribute('data-rate');
    if (rate) {
        document.getElementById('rate_per_kg').value = rate;
    }
}

document.getElementById('catchModal')?.addEventListener('hidden.bs.modal', function () {
    document.querySelector('form').reset();
    document.getElementById('catch_id').value = '';
    document.getElementById('catch_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('modalTitle').textContent = 'Add Catch Record';
});
</script>

<?php require_once '../includes/footer.php'; ?>
