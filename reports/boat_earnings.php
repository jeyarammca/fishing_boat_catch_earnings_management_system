<?php
require_once '../config/config.php';
$page_title = 'Boat Earnings Report';

if (!in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$boat_id = $_GET['boat'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get all boats for filter
$boats = execute_query("SELECT * FROM boats WHERE status = 'active' ORDER BY boat_name");

// Build query
$where = "WHERE bt.trip_date BETWEEN '$start_date' AND '$end_date'";
if (!empty($boat_id)) {
    $where .= " AND bt.boat_id = " . intval($boat_id);
}

// Get earnings data
$earnings = execute_query("
    SELECT 
        bt.id,
        bt.trip_id_auto,
        b.boat_name,
        b.owner_name,
        bt.trip_date,
        bt.started_at,
        bt.ended_at,
        COALESCE(SUM(dc.quantity_kg), 0) as total_kg,
        COALESCE(SUM(dc.total_amount), 0) as total_income,
        COALESCE((SELECT SUM(amount) FROM expenses WHERE trip_id = bt.id), 0) as total_expenses,
        bt.net_profit,
        (SELECT COUNT(*) FROM trip_fishermen WHERE trip_id = bt.id) as fishermen_count
    FROM boat_trips bt
    JOIN boats b ON bt.boat_id = b.id
    LEFT JOIN daily_catch dc ON bt.id = dc.trip_id
    $where
    GROUP BY bt.id
    ORDER BY bt.trip_date DESC
");

$total_kg = 0;
$total_income = 0;
$total_expenses = 0;
$total_profit = 0;

require_once '../includes/header.php';
?>

<div class="py-4">
    <h2 class="mb-4"><i class="fas fa-coins"></i> <?php echo __('boat_earnings'); ?></h2>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3">
                    <label class="form-label"><?php echo __('boat'); ?></label>
                    <select class="form-control" name="boat" required>
                        <option value=""><?php echo __('select_boat_option'); ?></option>
                        <?php 
                        $boats->data_seek(0);
                        while ($boat = $boats->fetch_assoc()): ?>
                            <option value="<?php echo $boat['id']; ?>" <?php echo ($boat['id'] == $boat_id) ? 'selected' : ''; ?>><?php echo $boat['boat_name']; ?> - <?php echo $boat['owner_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo __('from_date'); ?></label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo __('to_date'); ?></label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> <?php echo __('filter'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($boat_id)): ?>
        <!-- Report Table -->
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between">
                <h5 class="mb-0"><i class="fas fa-table"></i> <?php echo __('earnings_from'); ?> <?php echo date('d-M-Y', strtotime($start_date)); ?> <?php echo __('to'); ?> <?php echo date('d-M-Y', strtotime($end_date)); ?></h5>
                <button class="btn btn-sm btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> <?php echo __('print'); ?>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('trip_id'); ?></th>
                            <th><?php echo __('date'); ?></th>
                            <th><?php echo __('catch_kg'); ?></th>
                            <th><?php echo __('income'); ?> (₹)</th>
                            <th><?php echo __('expenses'); ?> (₹)</th>
                            <th><?php echo __('net_profit'); ?> (₹)</th>
                            <th><?php echo __('fishermen'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $has_records = false;
                        while ($row = $earnings->fetch_assoc()): 
                            $has_records = true;
                            $total_kg += $row['total_kg'];
                            $total_income += $row['total_income'];
                            $total_expenses += $row['total_expenses'];
                            $total_profit += $row['net_profit'];
                        ?>
                            <tr>
                                    <td><a href="<?php echo SITE_URL; ?>staff/trip_view.php?id=<?php echo $row['id']; ?>"><code><?php echo $row['trip_id_auto']; ?></code></a></td>
                                    <td><?php echo date('d-M-Y', strtotime($row['trip_date'])); ?></td>
                                    <td><?php echo (!empty($row['started_at'])) ? date('d-M-Y H:i', strtotime($row['started_at'])) : '<span class="text-muted">-</span>'; ?></td>
                                    <td><?php echo (!empty($row['ended_at'])) ? date('d-M-Y H:i', strtotime($row['ended_at'])) : '<span class="text-muted">-</span>'; ?></td>
                                    <td><?php echo number_format($row['total_kg'], 2); ?></td>
                                    <td><strong class="text-success">₹<?php echo number_format($row['total_income'], 2); ?></strong></td>
                                    <td><span class="text-danger">₹<?php echo number_format($row['total_expenses'], 2); ?></span></td>
                                    <td><strong>₹<?php echo number_format($row['net_profit'], 2); ?></strong></td>
                                    <td><span class="badge bg-info"><?php echo $row['fishermen_count']; ?></span></td>
                                </tr>
                        <?php endwhile; ?>
                        <?php if (!$has_records): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3"><?php echo __('no_records_boat_date'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($has_records): ?>
                <div class="card-footer bg-light">
                    <div class="row">
                        <div class="col-md-3">
                            <h6><?php echo __('total_catch'); ?>: <strong><?php echo number_format($total_kg, 2); ?> Kg</strong></h6>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-success"><?php echo __('total_income'); ?>: <strong>₹<?php echo number_format($total_income, 2); ?></strong></h6>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-danger"><?php echo __('total_expenses'); ?>: <strong>₹<?php echo number_format($total_expenses, 2); ?></strong></h6>
                        </div>
                        <div class="col-md-3">
                            <h6><?php echo __('total_profit'); ?>: <strong>₹<?php echo number_format($total_profit, 2); ?></strong></h6>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <?php echo __('please_select_boat_msg'); ?>
        </div>
    <?php endif; ?>
</div>

<style>
    @media print {
        .navbar, .sidebar, .btn, form { display: none; }
        .main-content { margin-left: 0; }
    }
</style>

<?php require_once '../includes/footer.php'; ?>
