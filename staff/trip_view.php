<?php
require_once '../config/config.php';
$page_title = 'Trip Details';

if (!in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$trip_id = intval($_GET['id'] ?? 0);
if (empty($trip_id)) {
    header('Location: ' . SITE_URL . 'staff/boat_trips.php');
    exit();
}

$trip = execute_query("SELECT bt.*, b.boat_name, b.boat_number FROM boat_trips bt JOIN boats b ON bt.boat_id = b.id WHERE bt.id = $trip_id")->fetch_assoc();
$fishermen = execute_query("SELECT f.* FROM trip_fishermen tf JOIN fishermen f ON tf.fisherman_id = f.id WHERE tf.trip_id = $trip_id");
$catches = execute_query("SELECT dc.*, ft.fish_name FROM daily_catch dc JOIN fish_types ft ON dc.fish_type_id = ft.id WHERE dc.trip_id = $trip_id ORDER BY dc.catch_date");
$expenses = execute_query("SELECT * FROM expenses WHERE trip_id = $trip_id ORDER BY expense_date");

require_once '../includes/header.php';
?>

<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-ship"></i> Trip Details - <code><?php echo $trip['trip_id_auto']; ?></code></h2>
        <a href="<?php echo SITE_URL; ?>staff/boat_trips.php" class="btn btn-secondary">Back to Trips</a>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <h6>Boat</h6>
            <p><?php echo $trip['boat_name']; ?> (<?php echo $trip['boat_number']; ?>)</p>
        </div>
        <div class="col-md-4">
            <h6>Trip Date</h6>
            <p><?php echo date('d-M-Y', strtotime($trip['trip_date'])); ?></p>
        </div>
        <div class="col-md-4">
            <h6>Status</h6>
            <p><?php echo ucfirst($trip['status']); ?></p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <h6>Started At</h6>
            <p><?php echo (!empty($trip['started_at'])) ? date('d-M-Y H:i', strtotime($trip['started_at'])) : '-'; ?></p>
        </div>
        <div class="col-md-3">
            <h6>Ended At</h6>
            <p><?php echo (!empty($trip['ended_at'])) ? date('d-M-Y H:i', strtotime($trip['ended_at'])) : '-'; ?></p>
        </div>
        <div class="col-md-3">
            <h6>Total Catch (Kg)</h6>
            <p><?php echo number_format($trip['total_catch_kg'], 2); ?></p>
        </div>
        <div class="col-md-3">
            <h6>Net Profit (<?=CURRENCY_SYMBOL?>)</h6>
            <p><?=CURRENCY_SYMBOL?><?php echo number_format($trip['net_profit'], 2); ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Assigned Fishermen</h5>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <?php while ($f = $fishermen->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo $f['fisherman_name']; ?></strong>
                            <div class="text-muted small"><?php echo $f['mobile_number']; ?> — <?php echo $f['role']; ?></div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-light"><h6 class="mb-0">Catch Records</h6></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Date</th><th>Fish</th><th>Qty (Kg)</th><th>Rate/Kg</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($c = $catches->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d-M-Y', strtotime($c['catch_date'])); ?></td>
                                    <td><?php echo $c['fish_name']; ?></td>
                                    <td><?php echo number_format($c['quantity_kg'],2); ?></td>
                                    <td><?=CURRENCY_SYMBOL?><?php echo number_format($c['rate_per_kg'],2); ?></td>
                                    <td><?=CURRENCY_SYMBOL?><?php echo number_format($c['total_amount'],2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-light"><h6 class="mb-0">Expenses</h6></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($e = $expenses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d-M-Y', strtotime($e['expense_date'])); ?></td>
                                    <td><?php echo $e['expense_type']; ?></td>
                                    <td><?=CURRENCY_SYMBOL?><?php echo number_format($e['amount'],2); ?></td>
                                    <td><?php echo substr($e['description'],0,80); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>
