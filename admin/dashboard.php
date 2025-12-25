<?php
require_once '../config/config.php';
$page_title = __('dashboard');

if ($_SESSION['role'] != 'admin') {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

// Get statistics
$boats_count = execute_query("SELECT COUNT(*) as count FROM boats WHERE status = 'active'")->fetch_assoc()['count'];
$fishermen_count = execute_query("SELECT COUNT(*) as count FROM fishermen WHERE status = 'active'")->fetch_assoc()['count'];
$trips_today = execute_query("SELECT COUNT(*) as count FROM boat_trips WHERE trip_date = CURDATE()")->fetch_assoc()['count'];
$today_collection = execute_query("SELECT COALESCE(SUM(total_income), 0) as total FROM boat_trips WHERE trip_date = CURDATE()")->fetch_assoc()['total'];

// Get recent trips
$recent_trips = execute_query("
    SELECT bt.*, b.boat_name, 
    (SELECT COUNT(*) FROM trip_fishermen WHERE trip_id = bt.id) as fishermen_count
    FROM boat_trips bt
    JOIN boats b ON bt.boat_id = b.id
    ORDER BY bt.trip_date DESC
    LIMIT 5
");

require_once '../includes/header.php';
?>

<div class="py-4">
    <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> <?php echo __('dashboard'); ?></h2>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0"><?php echo __('active_boats'); ?></h6>
                            <h2 class="mb-0"><?php echo $boats_count; ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-ship fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0"><?php echo __('active_fishermen'); ?></h6>
                            <h2 class="mb-0"><?php echo $fishermen_count; ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0"><?php echo __('trips_today'); ?></h6>
                            <h2 class="mb-0"><?php echo $trips_today; ?></h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-water fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0"><?php echo __('todays_income'); ?></h6>
                            <h2 class="mb-0"><?=CURRENCY_SYMBOL?><?php echo number_format($today_collection, 0); ?></h2>
                        </div>
                        <div class="stat-icon">
                            <span class="fa-3x opacity-50" style="font-weight: bold; font-style: normal;"><?=CURRENCY_SYMBOL?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Trips Table -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-list"></i> <?php echo __('recent_trips'); ?></h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('trip_id'); ?></th>
                        <th><?php echo __('boat'); ?></th>
                        <th><?php echo __('date'); ?></th>
                        <th><?php echo __('fishermen'); ?></th>
                        <th><?php echo __('total_catch_kg'); ?></th>
                        <th><?php echo __('income'); ?></th>
                        <th><?php echo __('status'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($trip = $recent_trips->fetch_assoc()): ?>
                        <tr>
                            <td><code><?php echo $trip['trip_id_auto']; ?></code></td>
                            <td><strong><?php echo $trip['boat_name']; ?></strong></td>
                            <td><?php echo date('d-M-Y', strtotime($trip['trip_date'])); ?></td>
                            <td><span class="badge bg-info"><?php echo $trip['fishermen_count']; ?></span></td>
                            <td><?php echo number_format($trip['total_catch_kg'], 2); ?> Kg</td>
                            <td><strong><?=CURRENCY_SYMBOL?><?php echo number_format($trip['total_income'], 2); ?></strong></td>
                            <td><span class="badge bg-<?php echo ($trip['status'] == 'completed') ? 'success' : (($trip['status'] == 'pending') ? 'warning' : 'danger'); ?>"><?php echo ucfirst($trip['status']); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
