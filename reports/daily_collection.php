<?php
require_once '../config/config.php';
$page_title = __('daily_collection_report');

if (!in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$report_date = $_GET['date'] ?? date('Y-m-d');
$boat_filter = $_GET['boat'] ?? '';

// Get all boats for filter
$boats = execute_query("SELECT * FROM boats WHERE status = 'active' ORDER BY boat_name");

// Build query
$where = "WHERE bt.trip_date = '$report_date'";
if (!empty($boat_filter)) {
    $where .= " AND bt.boat_id = " . intval($boat_filter);
}

// Get daily collection
$collections = execute_query("
    SELECT 
        bt.id,
        bt.trip_id_auto,
        b.boat_name,
        b.boat_number,
        bt.trip_date,
        bt.started_at,
        bt.ended_at,
        COALESCE(SUM(dc.quantity_kg), 0) as total_kg,
        COALESCE(SUM(dc.total_amount), 0) as total_amount,
        (SELECT COUNT(*) FROM trip_fishermen WHERE trip_id = bt.id) as fishermen_count
    FROM boat_trips bt
    JOIN boats b ON bt.boat_id = b.id
    LEFT JOIN daily_catch dc ON bt.id = dc.trip_id
    $where
    GROUP BY bt.id
    ORDER BY bt.trip_date DESC, b.boat_name
");

$total_kg = 0;
$total_amount = 0;
$trip_count = 0;

require_once '../includes/header.php';
?>

<div class="py-4">
    <h2 class="mb-4"><i class="fas fa-chart-bar"></i> <?php echo __('daily_collection_report'); ?></h2>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-4">
                    <label class="form-label"><?php echo __('date_label'); ?></label>
                    <input type="date" class="form-control" name="date" value="<?php echo $report_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo __('boat_label'); ?></label>
                    <select class="form-control" name="boat">
                        <option value=""><?php echo __('all_boats'); ?></option>
                        <?php 
                        $boats->data_seek(0);
                        while ($boat = $boats->fetch_assoc()): ?>
                            <option value="<?php echo $boat['id']; ?>" <?php echo ($boat['id'] == $boat_filter) ? 'selected' : ''; ?>><?php echo $boat['boat_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> <?php echo __('filter'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between">
            <h5 class="mb-0"><i class="fas fa-table"></i> <?php echo __('daily_collection'); ?> for <?php echo date('d-M-Y', strtotime($report_date)); ?></h5>
            <button class="btn btn-sm btn-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> <?php echo __('print'); ?>
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('trip_id'); ?></th>
                        <th><?php echo __('boat_name'); ?></th>
                        <th><?php echo __('boat_number'); ?></th>
                        <th><?php echo __('start'); ?></th>
                        <th><?php echo __('end'); ?></th>
                        <th><?php echo __('fishermen'); ?></th>
                        <th><?php echo __('total_kg'); ?></th>
                        <th><?php echo __('total_amount'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $has_records = false;
                    while ($row = $collections->fetch_assoc()): 
                        $has_records = true;
                        $total_kg += $row['total_kg'];
                        $total_amount += $row['total_amount'];
                        $trip_count++;
                    ?>
                        <tr>
                            <td><a href="<?php echo SITE_URL; ?>staff/trip_view.php?id=<?php echo $row['id']; ?>"><code><?php echo $row['trip_id_auto']; ?></code></a></td>
                            <td><strong><?php echo $row['boat_name']; ?></strong></td>
                            <td><?php echo $row['boat_number']; ?></td>
                            <td><?php echo (!empty($row['started_at'])) ? date('d-M-Y H:i', strtotime($row['started_at'])) : '<span class="text-muted">-</span>'; ?></td>
                            <td><?php echo (!empty($row['ended_at'])) ? date('d-M-Y H:i', strtotime($row['ended_at'])) : '<span class="text-muted">-</span>'; ?></td>
                            <td><span class="badge bg-info"><?php echo $row['fishermen_count']; ?></span></td>
                            <td><?php echo number_format($row['total_kg'], 2); ?></td>
                            <td><strong>₹<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_records): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3"><?php echo __('no_records_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($has_records): ?>
            <div class="card-footer bg-light">
                <div class="row">
                    <div class="col-md-6">
                        <h6><?php echo __('total_trips'); ?>: <strong><?php echo $trip_count; ?></strong></h6>
                    </div>
                    <div class="col-md-3">
                        <h6><?php echo __('total_catch'); ?>: <strong><?php echo number_format($total_kg, 2); ?> Kg</strong></h6>
                    </div>
                    <div class="col-md-3">
                        <h6><?php echo __('total_collection'); ?>: <strong>₹<?php echo number_format($total_amount, 2); ?></strong></h6>
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
