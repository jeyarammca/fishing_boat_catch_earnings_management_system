<?php
require_once '../config/config.php';
$page_title = 'Monthly Earnings Report';

if (!in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$month = $_GET['month'] ?? date('Y-m');
$boat_filter = $_GET['boat'] ?? '';

// Get all boats for filter
$boats = execute_query("SELECT * FROM boats WHERE status = 'active' ORDER BY boat_name");

// Build query
$start_date = $month . '-01';
$end_date = date('Y-m-t', strtotime($month . '-01'));

$where = "WHERE MONTH(bt.trip_date) = MONTH('$start_date') AND YEAR(bt.trip_date) = YEAR('$start_date')";
if (!empty($boat_filter)) {
    $where .= " AND bt.boat_id = " . intval($boat_filter);
} else {
    $where .= " GROUP BY bt.boat_id";
}

// Get monthly earnings
$monthly_earnings = execute_query("
    SELECT 
        b.id,
        b.boat_name,
        b.owner_name,
        COUNT(DISTINCT bt.id) as trip_count,
        COALESCE(SUM(dc.quantity_kg), 0) as total_kg,
        COALESCE(SUM(dc.total_amount), 0) as total_revenue,
        COALESCE((SELECT SUM(amount) FROM expenses e WHERE e.trip_id IN (
            SELECT bt2.id FROM boat_trips bt2 WHERE MONTH(bt2.trip_date) = MONTH('$start_date') 
            AND YEAR(bt2.trip_date) = YEAR('$start_date') AND bt2.boat_id = b.id
        )), 0) as total_expenses
    FROM boat_trips bt
    JOIN boats b ON bt.boat_id = b.id
    LEFT JOIN daily_catch dc ON bt.id = dc.trip_id
    $where
    ORDER BY b.boat_name
");

require_once '../includes/header.php';
?>

<div class="py-4">
    <h2 class="mb-4"><i class="fas fa-calendar-alt"></i> Monthly Earnings Report</h2>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-5">
                    <label class="form-label">Month</label>
                    <input type="month" class="form-control" name="month" value="<?php echo $month; ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Boat (Optional)</label>
                    <select class="form-control" name="boat">
                        <option value="">-- All Boats --</option>
                        <?php 
                        $boats->data_seek(0);
                        while ($boat = $boats->fetch_assoc()): ?>
                            <option value="<?php echo $boat['id']; ?>" <?php echo ($boat['id'] == $boat_filter) ? 'selected' : ''; ?>><?php echo $boat['boat_name']; ?></option>
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
            <h5 class="mb-0"><i class="fas fa-table"></i> Monthly Summary for <?php echo date('M-Y', strtotime($month . '-01')); ?></h5>
            <button class="btn btn-sm btn-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Boat Name</th>
                        <th>Owner</th>
                        <th>Trips</th>
                        <th>Total Catch (Kg)</th>
                        <th>Revenue (<?=CURRENCY_SYMBOL?>)</th>
                        <th>Expenses (<?=CURRENCY_SYMBOL?>)</th>
                        <th>Net Income (<?=CURRENCY_SYMBOL?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_trips = 0;
                    $total_kg = 0;
                    $total_revenue = 0;
                    $total_expenses = 0;
                    $has_records = false;
                    
                    while ($row = $monthly_earnings->fetch_assoc()): 
                        $has_records = true;
                        $net_income = $row['total_revenue'] - $row['total_expenses'];
                        $total_trips += $row['trip_count'];
                        $total_kg += $row['total_kg'];
                        $total_revenue += $row['total_revenue'];
                        $total_expenses += $row['total_expenses'];
                    ?>
                        <tr>
                            <td><strong><?php echo $row['boat_name']; ?></strong></td>
                            <td><?php echo $row['owner_name']; ?></td>
                            <td><span class="badge bg-primary"><?php echo $row['trip_count']; ?></span></td>
                            <td><?php echo number_format($row['total_kg'], 2); ?></td>
                            <td><span class="text-success"><strong><?=CURRENCY_SYMBOL?><?php echo number_format($row['total_revenue'], 2); ?></strong></span></td>
                            <td><span class="text-danger"><?=CURRENCY_SYMBOL?><?php echo number_format($row['total_expenses'], 2); ?></span></td>
                            <td><strong><?=CURRENCY_SYMBOL?><?php echo number_format($net_income, 2); ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_records): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No records found for the selected month.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($has_records): ?>
            <div class="card-footer bg-light">
                <div class="row">
                    <div class="col-md-2">
                        <h6>Total Trips: <strong><?php echo $total_trips; ?></strong></h6>
                    </div>
                    <div class="col-md-2">
                        <h6>Total Catch: <strong><?php echo number_format($total_kg, 2); ?> Kg</strong></h6>
                    </div>
                    <div class="col-md-2">
                        <h6 class="text-success">Total Revenue: <strong><?=CURRENCY_SYMBOL?><?php echo number_format($total_revenue, 2); ?></strong></h6>
                    </div>
                    <div class="col-md-2">
                        <h6 class="text-danger">Total Expenses: <strong><?=CURRENCY_SYMBOL?><?php echo number_format($total_expenses, 2); ?></strong></h6>
                    </div>
                    <div class="col-md-2">
                        <h6>Net Income: <strong><?=CURRENCY_SYMBOL?><?php echo number_format($total_revenue - $total_expenses, 2); ?></strong></h6>
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
