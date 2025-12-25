<?php
require_once '../config/config.php';
$page_title = __('assign_fishermen');

if (!in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$message = '';

// Handle Assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trip_id = intval($_POST['trip_id'] ?? 0);
    $fishermen_ids = $_POST['fishermen_ids'] ?? [];
    if (empty($trip_id) || empty($fishermen_ids)) {
        $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Trip and fishermen selection are required! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        // Server-side enforcement: minimum 3 fishermen and at least 1 Captain
        $selected_count = count($fishermen_ids);
        $captain_count = 0;
        foreach ($fishermen_ids as $fid) {
            $row = execute_query("SELECT role FROM fishermen WHERE id = " . intval($fid))->fetch_assoc();
            if ($row && strtolower($row['role']) === 'captain') $captain_count++;
        }

        if ($selected_count < 3 || $captain_count < 1) {
            $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> Please select at least 3 fishermen and at least 1 Captain. Selected: <strong>' . $selected_count . '</strong>, Captains: <strong>' . $captain_count . '</strong>. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
            // Get trip date
            $trip = execute_query("SELECT trip_date FROM boat_trips WHERE id = $trip_id")->fetch_assoc();

            // Delete existing assignments for this trip
            execute_query("DELETE FROM trip_fishermen WHERE trip_id = $trip_id");

            // Insert new assignments
            foreach ($fishermen_ids as $fisherman_id) {
                $fisherman_id = intval($fisherman_id);
                $query = "INSERT INTO trip_fishermen (trip_id, fisherman_id, attendance_date) VALUES ($trip_id, $fisherman_id, '" . $trip['trip_date'] . "')";
                execute_query($query);
            }

            $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Fishermen assigned successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
}

// Get all trips for selection
$trips = execute_query("
    SELECT bt.id, bt.trip_id_auto, b.boat_name, bt.trip_date
    FROM boat_trips bt
    JOIN boats b ON bt.boat_id = b.id
    ORDER BY bt.trip_date DESC
");

// Get selected trip's fishermen
$selected_fishermen = [];
$trip_id_selected = $_POST['trip_id'] ?? $_GET['trip_id'] ?? '';
if (!empty($trip_id_selected)) {
    $trip_fishermen = execute_query("
        SELECT fisherman_id FROM trip_fishermen WHERE trip_id = " . intval($trip_id_selected)
    );
    while ($row = $trip_fishermen->fetch_assoc()) {
        $selected_fishermen[] = $row['fisherman_id'];
    }
}

// Determine fishermen list based on selected trip's boat (use boat_crew mapping if available)
$fishermen = null;
$boat_for_trip = 0;
if (!empty($trip_id_selected)) {
    $trip_info = execute_query("SELECT boat_id FROM boat_trips WHERE id = " . intval($trip_id_selected))->fetch_assoc();
    if ($trip_info) {
        $boat_for_trip = intval($trip_info['boat_id']);
    }
}

if ($boat_for_trip) {
    // Try to get fishermen assigned to this boat via boat_crew mapping
    $fishermen = execute_query("SELECT f.* FROM fishermen f JOIN boat_crew bc ON f.id = bc.fisherman_id WHERE bc.boat_id = $boat_for_trip AND f.status = 'active' ORDER BY f.fisherman_name");
    // If mapping table exists but returns zero rows, fall back to all fishermen
    if (!$fishermen || $fishermen->num_rows == 0) {
        $fishermen = execute_query("SELECT * FROM fishermen WHERE status = 'active' ORDER BY fisherman_name");
    }
} else {
    // No trip selected yet — show all fishermen
    $fishermen = execute_query("SELECT * FROM fishermen WHERE status = 'active' ORDER BY fisherman_name");
}

require_once '../includes/header.php';
?>

<div class="py-4">
    <h2 class="mb-4"><i class="fas fa-users"></i> <?php echo __('assign_fishermen'); ?></h2>

    <?php echo $message; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="trip_id" class="form-label"><?php echo __('select_trip'); ?> *</label>
                        <select class="form-control" id="trip_id" name="trip_id" required onchange="this.form.submit()">
                            <option value="">-- <?php echo __('select_trip'); ?> --</option>
                            <?php 
                            $trips->data_seek(0);
                            while ($trip = $trips->fetch_assoc()): ?>
                                <option value="<?php echo $trip['id']; ?>" <?php echo ($trip['id'] == $trip_id_selected) ? 'selected' : ''; ?>>
                                    <?php echo $trip['trip_id_auto']; ?> - <?php echo $trip['boat_name']; ?> (<?php echo date('d-M-Y', strtotime($trip['trip_date'])); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <?php if (!empty($trip_id_selected)): ?>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <label class="form-label"><strong><?php echo __('select_fishermen'); ?> *</strong></label>
                            <div class="fishermen-list">
                                <?php 
                                $fishermen->data_seek(0);
                                while ($fisherman = $fishermen->fetch_assoc()): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="fishermen_ids[]" value="<?php echo $fisherman['id']; ?>" id="fisherman_<?php echo $fisherman['id']; ?>" data-role="<?php echo $fisherman['role']; ?>" <?php echo in_array($fisherman['id'], $selected_fishermen) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="fisherman_<?php echo $fisherman['id']; ?>">
                                            <strong><?php echo $fisherman['fisherman_name']; ?></strong>
                                            <span class="badge bg-<?php echo ($fisherman['role'] == 'Captain') ? 'primary' : 'secondary'; ?>"><?php echo $fisherman['role']; ?></span>
                                            <small class="text-muted"><?php echo $fisherman['mobile_number']; ?></small>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                                        <p class="text-muted"><?php echo __('selected_fishermen'); ?>: <strong id="selectedCount">0</strong> (<?php echo __('minimum_required'); ?> <strong>3</strong> and at least 1 Captain)</p>
                                        <button type="submit" class="btn btn-primary" id="saveAssignmentBtn" disabled>
                            <i class="fas fa-save"></i> <?php echo __('save'); ?>
                        </button>
                        <a href="javascript:location.reload();" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> <?php echo __('reset'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if (!empty($trip_id_selected)): ?>
        <!-- Fishermen Count Summary -->
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-bar-chart"></i> Summary</h5>
            </div>
            <div class="card-body">
                <?php 
                $count = count($selected_fishermen);
                $captains = count(array_filter($selected_fishermen, function($id) {
                    $fisherman = execute_query("SELECT role FROM fishermen WHERE id = " . intval($id))->fetch_assoc();
                    return $fisherman && $fisherman['role'] == 'Captain';
                }));
                ?>
                <div class="row">
                        <div class="col-md-3">
                            <h6><?php echo __('total_fishermen'); ?></h6>
                            <h2 class="text-primary"><?php echo $count; ?></h2>
                        </div>
                        <div class="col-md-3">
                            <h6><?php echo __('captains'); ?></h6>
                            <h2 class="text-success"><?php echo $captains; ?></h2>
                        </div>
                        <div class="col-md-3">
                            <h6><?php echo __('crew_members'); ?></h6>
                            <h2 class="text-info"><?php echo ($count - $captains); ?></h2>
                        </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('input[name="fishermen_ids[]"]');
    const saveBtn = document.getElementById('saveAssignmentBtn');
    const countEl = document.getElementById('selectedCount');
    if (!checkboxes.length) return;

    function updateCount() {
        const selectedEls = Array.from(checkboxes).filter(cb => cb.checked);
        const selected = selectedEls.length;
        // count captains among selected
        const captainCount = selectedEls.filter(cb => (cb.getAttribute('data-role') || '').toLowerCase() === 'captain').length;
        countEl.textContent = selected;
        // Enable only if at least 3 selected AND at least 1 captain
        if (selected >= 3 && captainCount >= 1) {
            saveBtn.disabled = false;
            saveBtn.classList.remove('btn-secondary');
            saveBtn.classList.add('btn-primary');
        } else {
            saveBtn.disabled = true;
            saveBtn.classList.remove('btn-primary');
            saveBtn.classList.add('btn-secondary');
        }
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateCount));
    // initialize count based on any pre-checked boxes
    updateCount();

    // Prevent form submit if fewer than 5 selected (extra safety)
    const form = document.querySelector('form');
    form.addEventListener('submit', function (e) {
        const selectedEls = Array.from(checkboxes).filter(cb => cb.checked);
        const selected = selectedEls.length;
        const captainCount = selectedEls.filter(cb => (cb.getAttribute('data-role') || '').toLowerCase() === 'captain').length;
        if (selected < 3 || captainCount < 1) {
            e.preventDefault();
            alert('Please select at least 3 fishermen and at least 1 Captain before saving the assignment.');
        }
    });
});
</script>
