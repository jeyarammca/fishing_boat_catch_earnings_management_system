<?php
require_once '../config/config.php';
$page_title = __('boat_trips');

if (!in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$message = '';
$action = $_GET['action'] ?? '';
$trip_id = $_GET['id'] ?? '';

// Handle start/end actions via GET
if (!empty($action) && !empty($trip_id)) {
    $tid = intval($trip_id);
    if ($action === 'delete') {
        // Only admins can delete trips
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . SITE_URL . 'staff/boat_trips.php?msg=forbidden');
            exit();
        }
        // Delete trip and rely on ON DELETE CASCADE to remove related records
        execute_query("DELETE FROM boat_trips WHERE id = $tid");
        header('Location: ' . SITE_URL . 'staff/boat_trips.php?msg=deleted');
        exit();
    }
    if ($action === 'start') {
        execute_query("UPDATE boat_trips SET started_at = NOW() WHERE id = $tid");
        header('Location: ' . SITE_URL . 'staff/boat_trips.php');
        exit();
    }
    if ($action === 'end') {
        // Recalculate net profit from stored totals
        $trip_data = execute_query("SELECT total_income, total_expenses FROM boat_trips WHERE id = $tid")->fetch_assoc();
        $income = floatval($trip_data['total_income']);
        $expenses = floatval($trip_data['total_expenses']);
        $net = $income - $expenses;
        execute_query("UPDATE boat_trips SET ended_at = NOW(), status = 'completed', net_profit = $net WHERE id = $tid");
        header('Location: ' . SITE_URL . 'staff/boat_trips.php');
        exit();
    }
}

// Handle Add/Update Trip
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $boat_id = intval($_POST['boat_id'] ?? 0);
    $trip_date = escape_string($_POST['trip_date'] ?? '');
    $trip_reference = escape_string($_POST['trip_reference'] ?? '');
    $force_create = isset($_POST['force_create']) && $_POST['force_create'] == '1';

    if (empty($boat_id) || empty($trip_date)) {
        $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Boat and Date are required! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        // Generate Trip ID
        $trip_id_auto = 'TRP' . date('YmdHis') . rand(100, 999);

        if ($_POST['trip_id'] ?? '' != '') {
            // Update
            $query = "UPDATE boat_trips SET boat_id = $boat_id, trip_date = '$trip_date', trip_reference = '$trip_reference' WHERE id = " . intval($_POST['trip_id']);
            if (execute_query($query)) {
                $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Trip updated successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } else {
            // Insert - but ensure only one trip per boat per day unless forced by admin
            $existing = execute_query("SELECT id, trip_id_auto FROM boat_trips WHERE boat_id = $boat_id AND trip_date = '$trip_date' AND status != 'cancelled' LIMIT 1")->fetch_assoc();
            if ($existing && !$force_create) {
                $message = '<div class="alert alert-warning alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> A trip for this boat on the selected date already exists: <strong>' . $existing['trip_id_auto'] . '</strong>. <a href="' . SITE_URL . 'staff/trip_view.php?id=' . $existing['id'] . '" class="btn btn-sm btn-outline-primary ms-2">View Trip</a> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                if ($existing && $force_create && $_SESSION['role'] !== 'admin') {
                    $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Only administrators can force-create duplicate trips for the same boat/date. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                } else {
                    $query = "INSERT INTO boat_trips (boat_id, trip_date, trip_reference, trip_id_auto) VALUES ($boat_id, '$trip_date', '$trip_reference', '$trip_id_auto')";
                    if (execute_query($query)) {
                        $new_trip_id = get_last_id();
                        header('Location: ' . SITE_URL . 'staff/assign_fishermen.php?trip_id=' . $new_trip_id);
                        exit();
                    }
                }
            }
        }
    }
}

// Get all boats
$boats = execute_query("SELECT * FROM boats WHERE status = 'active' ORDER BY boat_name");

// Get all trips
$trips = execute_query("
    SELECT bt.*, b.boat_name
    FROM boat_trips bt
    JOIN boats b ON bt.boat_id = b.id
    ORDER BY bt.trip_date DESC, bt.created_at DESC
");

// Get trip for edit
$trip = null;
if ($action == 'edit' && !empty($trip_id)) {
    $trip = execute_query("SELECT * FROM boat_trips WHERE id = " . intval($trip_id))->fetch_assoc();
}

require_once '../includes/header.php';
?>

<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-ship"></i> <?php echo __('boat_trips'); ?></h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tripModal">
            <i class="fas fa-plus"></i> <?php echo __('create_trip'); ?>
        </button>
    </div>

    <?php echo $message; ?>

    <!-- Trips Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('trip_id'); ?></th>
                        <th><?php echo __('boat'); ?></th>
                        <th><?php echo __('date'); ?></th>
                        <th><?php echo __('reference'); ?></th>
                        <th><?php echo __('total_catch'); ?></th>
                        <th><?php echo __('income'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $trips->fetch_assoc()): ?>
                        <tr>
                            <td><code><?php echo $row['trip_id_auto']; ?></code></td>
                            <td><?php echo $row['boat_name']; ?></td>
                            <td><?php echo date('d-M-Y', strtotime($row['trip_date'])); ?></td>
                            <td><?php echo $row['trip_reference']; ?></td>
                            <td><?php echo number_format($row['total_catch_kg'], 2); ?> Kg</td>
                            <td><?php echo CURRENCY_SYMBOL . number_format($row['total_income'], 2); ?></td>
                            <td><span class="badge bg-<?php echo ($row['status'] == 'completed') ? 'success' : (($row['status'] == 'pending') ? 'warning' : 'danger'); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#tripModal" onclick="editTrip(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (empty($row['started_at'])): ?>
                                    <a href="?action=start&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary ms-1" title="<?php echo __('start'); ?>"><i class="fas fa-play"></i></a>
                                <?php endif; ?>
                                <?php if ($row['status'] != 'completed'): ?>
                                    <a href="?action=end&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success ms-1" title="<?php echo __('end'); ?>"><i class="fas fa-stop"></i></a>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger ms-1" onclick="return confirm('<?php echo addslashes(__('delete') . " this trip and all related records?"); ?>');"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Trip Modal -->
<div class="modal fade" id="tripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-ship"></i> <span id="modalTitle"><?php echo __('create_trip'); ?></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="trip_id" id="trip_id">
                    
                    <div class="mb-3">
                        <label for="boat_id" class="form-label"><?php echo __('select_boat'); ?> *</label>
                        <select class="form-control" id="boat_id" name="boat_id" required>
                            <option value="">Select Boat</option>
                            <?php 
                            $boats->data_seek(0);
                            while ($boat = $boats->fetch_assoc()): ?>
                                <option value="<?php echo $boat['id']; ?>"><?php echo $boat['boat_name']; ?> (<?php echo $boat['boat_number']; ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="trip_date" class="form-label"><?php echo __('trip_date'); ?> *</label>
                        <input type="date" class="form-control" id="trip_date" name="trip_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="trip_reference" class="form-label"><?php echo __('trip_reference'); ?></label>
                        <input type="text" class="form-control" id="trip_reference" name="trip_reference" placeholder="e.g., Morning Trip">
                    </div>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="force_create" name="force_create" value="1">
                            <label class="form-check-label" for="force_create"><?php echo __('force_create_text') ?? 'Force create trip even if one exists for same boat/date (Admin only)'; ?></label>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo __('save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTrip(trip) {
    document.getElementById('trip_id').value = trip.id;
    document.getElementById('boat_id').value = trip.boat_id;
    document.getElementById('trip_date').value = trip.trip_date;
    document.getElementById('trip_reference').value = trip.trip_reference;
    document.getElementById('modalTitle').textContent = '<?php echo addslashes(__('edit_trip')); ?>';
}

document.getElementById('tripModal')?.addEventListener('hidden.bs.modal', function () {
    document.querySelector('form').reset();
    document.getElementById('trip_id').value = '';
    document.getElementById('trip_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('modalTitle').textContent = '<?php echo addslashes(__('create_trip')); ?>';
});
</script>

<?php require_once '../includes/footer.php'; ?>
