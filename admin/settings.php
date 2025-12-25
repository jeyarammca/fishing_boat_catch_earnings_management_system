<?php
require_once __DIR__ . '/../config/config.php';
$page_title = 'System Settings';

if ($_SESSION['role'] != 'admin') {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

require_once __DIR__ . '/../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token($_POST['csrf_token'] ?? '');
    $currency = $_POST['currency_symbol'] ?? '₹';
    // Update setting in DB
    update_setting($conn, 'currency_symbol', $currency);
    // Reload to reflect change
    header('Location: ' . SITE_URL . 'admin/settings.php?success=1');
    exit();
}

// Get current currency symbol
$current_currency = get_setting($conn, 'currency_symbol', '₹');
?>

<div class="py-4">
    <h2 class="mb-4"><i class="fas fa-sliders-h"></i> System Settings</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Settings updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <?php echo csrf_input(); ?>
                <div class="mb-3">
                    <label for="currency_symbol" class="form-label">Currency Symbol</label>
                    <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?php echo htmlspecialchars($current_currency); ?>" required>
                    <div class="form-text">Enter the symbol to be used throughout the application (e.g., ₹, $, €).</div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
