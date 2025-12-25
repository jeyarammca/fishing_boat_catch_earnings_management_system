<?php
require_once '../config/config.php';
$page_title = 'Fish Type Report';

if (!in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$fish_type = $_GET['fish_type'] ?? '';

// Get all fish types for filter
$fish_types = execute_query("SELECT * FROM fish_types WHERE status = 'active' ORDER BY fish_name");

// Build query
$where = "WHERE dc.catch_date BETWEEN '$start_date' AND '$end_date'";
if (!empty($fish_type)) {
    $where .= " AND ft.id = " . intval($fish_type);
}

// Get fish type wise report
$fish_report = execute_query("
    SELECT 
        ft.id,
        ft.fish_name,
        ft.category,
        COUNT(DISTINCT dc.id) as total_records,
        COALESCE(SUM(dc.quantity_kg), 0) as total_quantity,
        COALESCE(SUM(dc.total_amount), 0) as total_earnings,
        COALESCE(AVG(dc.rate_per_kg), 0) as avg_rate
    FROM daily_catch dc
    JOIN fish_types ft ON dc.fish_type_id = ft.id
    $where
    GROUP BY ft.id
    ORDER BY total_earnings DESC
");

require_once '../includes/header.php';
?>

<div class="py-4">
    <h2 class="mb-4"><i class="fas fa-fish"></i> Fish Type Wise Report</h2>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fish Type</label>
                    <select class="form-control" name="fish_type">
                        <option value="">-- All Fish Types --</option>
                        <?php 
                        $fish_types->data_seek(0);
                        while ($ft = $fish_types->fetch_assoc()): ?>
                            <option value="<?php echo $ft['id']; ?>" <?php echo ($ft['id'] == $fish_type) ? 'selected' : ''; ?>><?php echo $ft['fish_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
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
            <h5 class="mb-0"><i class="fas fa-table"></i> Report from <?php echo date('d-M-Y', strtotime($start_date)); ?> to <?php echo date('d-M-Y', strtotime($end_date)); ?></h5>
            <button class="btn btn-sm btn-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fish Name</th>
                        <th>Category</th>
                        <th>Records</th>
                        <th>Total Quantity (Kg)</th>
                        <th>Total Earnings (₹)</th>
                        <th>Average Rate/Kg</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_qty = 0;
                    $total_earnings = 0;
                    $has_records = false;
                    
                    while ($row = $fish_report->fetch_assoc()): 
                        $has_records = true;
                        $total_qty += $row['total_quantity'];
                        $total_earnings += $row['total_earnings'];
                    ?>
                        <tr>
                            <td><strong><?php echo $row['fish_name']; ?></strong></td>
                            <td><span class="badge bg-info"><?php echo $row['category']; ?></span></td>
                            <td><?php echo $row['total_records']; ?></td>
                            <td><?php echo number_format($row['total_quantity'], 2); ?></td>
                            <td><strong>₹<?php echo number_format($row['total_earnings'], 2); ?></strong></td>
                            <td>₹<?php echo number_format($row['avg_rate'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_records): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No records found for the selected period.</td>
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
                        <h6>Total Quantity: <strong><?php echo number_format($total_qty, 2); ?> Kg</strong></h6>
                    </div>
                    <div class="col-md-3">
                        <h6>Total Earnings: <strong>₹<?php echo number_format($total_earnings, 2); ?></strong></h6>
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
