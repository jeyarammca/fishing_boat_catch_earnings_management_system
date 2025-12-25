<?php
require_once '../config/config.php';
$page_title = 'Fishermen Attendance Report';

if (!in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$month = $_GET['month'] ?? date('Y-m');
$fisherman_filter = $_GET['fisherman'] ?? '';

// Get all fishermen for filter
$fishermen = execute_query("SELECT * FROM fishermen WHERE status = 'active' ORDER BY fisherman_name");

// Build query
$start_date = $month . '-01';
$end_date = date('Y-m-t', strtotime($month . '-01'));

$where = "WHERE tf.attendance_date BETWEEN '$start_date' AND '$end_date'";
if (!empty($fisherman_filter)) {
    $where .= " AND f.id = " . intval($fisherman_filter);
}

// Get attendance report
$attendance = execute_query("
    SELECT 
        f.id,
        f.fisherman_name,
        f.mobile_number,
        f.role,
        COUNT(DISTINCT tf.id) as days_worked,
        COUNT(DISTINCT tf.trip_id) as trips_count,
        COUNT(DISTINCT bt.boat_id) as boats_worked
    FROM trip_fishermen tf
    JOIN fishermen f ON tf.fisherman_id = f.id
    JOIN boat_trips bt ON tf.trip_id = bt.id
    $where
    GROUP BY f.id
    ORDER BY days_worked DESC, f.fisherman_name
");

require_once '../includes/header.php';
?>

<div class="py-4">
    <h2 class="mb-4"><i class="fas fa-users"></i> Fishermen Attendance Report</h2>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-5">
                    <label class="form-label">Month</label>
                    <input type="month" class="form-control" name="month" value="<?php echo $month; ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Fisherman (Optional)</label>
                    <select class="form-control" name="fisherman">
                        <option value="">-- All Fishermen --</option>
                        <?php 
                        $fishermen->data_seek(0);
                        while ($fish = $fishermen->fetch_assoc()): ?>
                            <option value="<?php echo $fish['id']; ?>" <?php echo ($fish['id'] == $fisherman_filter) ? 'selected' : ''; ?>><?php echo $fish['fisherman_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between">
            <h5 class="mb-0"><i class="fas fa-table"></i> Attendance for <?php echo date('M-Y', strtotime($month . '-01')); ?></h5>
            <button class="btn btn-sm btn-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fisherman Name</th>
                        <th>Role</th>
                        <th>Mobile</th>
                        <th>Days Worked</th>
                        <th>Trips</th>
                        <th>Boats Worked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_days = 0;
                    $total_trips = 0;
                    $has_records = false;
                    
                    while ($row = $attendance->fetch_assoc()): 
                        $has_records = true;
                        $total_days += $row['days_worked'];
                        $total_trips += $row['trips_count'];
                    ?>
                        <tr>
                            <td><strong><?php echo $row['fisherman_name']; ?></strong></td>
                            <td><span class="badge bg-<?php echo ($row['role'] == 'Captain') ? 'primary' : 'secondary'; ?>"><?php echo $row['role']; ?></span></td>
                            <td><i class="fas fa-phone"></i> <?php echo $row['mobile_number']; ?></td>
                            <td><span class="badge bg-success"><?php echo $row['days_worked']; ?></span></td>
                            <td><?php echo $row['trips_count']; ?></td>
                            <td><?php echo $row['boats_worked']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_records): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No attendance records found for the selected period.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($has_records): ?>
            <div class="card-footer bg-light">
                <div class="row">
                    <div class="col-md-6">
                    </div>
                    <div class="col-md-3">
                        <h6>Total Days Worked: <strong><?php echo $total_days; ?></strong></h6>
                    </div>
                    <div class="col-md-3">
                        <h6>Total Trips: <strong><?php echo $total_trips; ?></strong></h6>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    @media print {
        .navbar, .sidebar, .btn, form { display: none; }
        .main-content { margin-left: 0; }
    }
</style>

<?php require_once '../includes/footer.php'; ?>
