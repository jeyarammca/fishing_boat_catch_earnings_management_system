<?php
require_once '../config/config.php';
$page_title = 'Daily Expenses';

if (!in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$message = '';
$action = $_GET['action'] ?? '';
$expense_id = $_GET['id'] ?? '';

// Handle Delete
if ($action == 'delete' && !empty($expense_id)) {
    $expense = execute_query("SELECT trip_id, amount FROM expenses WHERE id = " . intval($expense_id))->fetch_assoc();
    if ($expense) {
        // Update trip totals
        execute_query("
            UPDATE boat_trips SET 
            total_expenses = total_expenses - " . $expense['amount'] . ",
            net_profit = total_income - (total_expenses - " . $expense['amount'] . ")
            WHERE id = " . $expense['trip_id']
        );
        execute_query("DELETE FROM expenses WHERE id = " . intval($expense_id));
        $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Expense deleted successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trip_id = intval($_POST['trip_id'] ?? 0);
    $expense_type = escape_string($_POST['expense_type'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $description = escape_string($_POST['description'] ?? '');
    $expense_date = escape_string($_POST['expense_date'] ?? '');

    if (empty($trip_id) || empty($expense_type) || empty($amount) || empty($expense_date)) {
        $message = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> All fields are required! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        if ($_POST['expense_id'] ?? '' != '') {
            // Update
            $old_expense = execute_query("SELECT trip_id, amount FROM expenses WHERE id = " . intval($_POST['expense_id']))->fetch_assoc();
            
            // Update trip totals
            execute_query("
                UPDATE boat_trips SET 
                total_expenses = total_expenses - " . $old_expense['amount'] . " + $amount,
                net_profit = total_income - (total_expenses - " . $old_expense['amount'] . " + $amount)
                WHERE id = " . $old_expense['trip_id']
            );

            $query = "UPDATE expenses SET trip_id = $trip_id, expense_type = '$expense_type', amount = $amount, description = '$description', expense_date = '$expense_date' WHERE id = " . intval($_POST['expense_id']);
            if (execute_query($query)) {
                $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Expense updated successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } else {
            // Insert
            $query = "INSERT INTO expenses (trip_id, expense_type, amount, description, expense_date) VALUES ($trip_id, '$expense_type', $amount, '$description', '$expense_date')";
            if (execute_query($query)) {
                // Update trip totals
                $trip_data = execute_query("SELECT total_income, total_expenses FROM boat_trips WHERE id = $trip_id")->fetch_assoc();
                $new_total_expenses = $trip_data['total_expenses'] + $amount;
                $new_net_profit = $trip_data['total_income'] - $new_total_expenses;
                
                execute_query("
                    UPDATE boat_trips SET 
                    total_expenses = $new_total_expenses,
                    net_profit = $new_net_profit
                    WHERE id = $trip_id
                ");
                $message = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> Expense added successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
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

// Get expense for edit
$expense_record = null;
if ($action == 'edit' && !empty($expense_id)) {
    $expense_record = execute_query("SELECT * FROM expenses WHERE id = " . intval($expense_id))->fetch_assoc();
}

// Get all expenses
$expenses = execute_query("
    SELECT e.*, bt.trip_id_auto, b.boat_name
    FROM expenses e
    JOIN boat_trips bt ON e.trip_id = bt.id
    JOIN boats b ON bt.boat_id = b.id
    ORDER BY e.expense_date DESC, e.created_at DESC
");

require_once '../includes/header.php';
?>

<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-money-bill"></i> Daily Expenses</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal">
            <i class="fas fa-plus"></i> Add Expense
        </button>
    </div>

    <?php echo $message; ?>

    <!-- Expenses Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Trip ID</th>
                        <th>Boat</th>
                        <th>Date</th>
                        <th>Expense Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $expenses->fetch_assoc()): ?>
                        <tr>
                            <td><code><?php echo $row['trip_id_auto']; ?></code></td>
                            <td><?php echo $row['boat_name']; ?></td>
                            <td><?php echo date('d-M-Y', strtotime($row['expense_date'])); ?></td>
                            <td><span class="badge bg-warning"><?php echo $row['expense_type']; ?></span></td>
                            <td><strong><?=CURRENCY_SYMBOL?><?php echo number_format($row['amount'], 2); ?></strong></td>
                            <td><?php echo substr($row['description'], 0, 30); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#expenseModal" onclick="editExpense(<?php echo htmlspecialchars(json_encode($row)); ?>)">
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

<!-- Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-money-bill"></i> <span id="modalTitle">Add Expense</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="expense_id" id="expense_id">
                    
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
                        <label for="expense_type" class="form-label">Expense Type *</label>
                        <select class="form-control" id="expense_type" name="expense_type" required>
                            <option value="">Select Type</option>
                            <option value="Fuel">Fuel</option>
                            <option value="Ice">Ice</option>
                            <option value="Food">Food</option>
                            <option value="Net Repair">Net Repair</option>
                            <option value="Port Charges">Port Charges</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (<?=CURRENCY_SYMBOL?>) *</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label for="expense_date" class="form-label">Expense Date *</label>
                        <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
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
function editExpense(expense) {
    document.getElementById('expense_id').value = expense.id;
    document.getElementById('trip_id').value = expense.trip_id;
    document.getElementById('expense_type').value = expense.expense_type;
    document.getElementById('amount').value = expense.amount;
    document.getElementById('expense_date').value = expense.expense_date;
    document.getElementById('description').value = expense.description || '';
    document.getElementById('modalTitle').textContent = 'Edit Expense';
}

document.getElementById('expenseModal')?.addEventListener('hidden.bs.modal', function () {
    document.querySelector('form').reset();
    document.getElementById('expense_id').value = '';
    document.getElementById('expense_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('modalTitle').textContent = 'Add Expense';
});
</script>

<?php require_once '../includes/footer.php'; ?>
