<?php
require_once '../config/config.php';
$page_title = __('language_settings');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$available = [
    'en' => 'English',
    'ta' => 'தமிழ்',
    'ml' => 'മലയാളം',
    'kn' => 'ಕನ್ನಡ',
    'hi' => 'हिन्दी'
];

$message = '';

// Create table if not exists helper
function ensure_language_table() {
    $create = "CREATE TABLE IF NOT EXISTS language_settings (
        lang_code VARCHAR(10) NOT NULL PRIMARY KEY,
        enabled TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    execute_query($create);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = $_POST['langs'] ?? [];
    ensure_language_table();
    foreach ($available as $code => $label) {
        $enabled = in_array($code, $selected) ? 1 : 0;
        $code_esc = escape_string($code);
        execute_query("INSERT INTO language_settings (lang_code, enabled) VALUES ('$code_esc', $enabled) ON DUPLICATE KEY UPDATE enabled = $enabled");
    }
    $message = '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle"></i> ' . __('language_settings_updated') . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

// Load current settings
$enabled_map = [];
$check = execute_query("SHOW TABLES LIKE 'language_settings'");
if ($check && $check->num_rows > 0) {
    $res = execute_query("SELECT lang_code, enabled FROM language_settings");
    while ($r = $res->fetch_assoc()) {
        $enabled_map[$r['lang_code']] = intval($r['enabled']);
    }
} else {
    // default: enable all
    foreach ($available as $c => $_) $enabled_map[$c] = 1;
}

require_once '../includes/header.php';
?>
<div class="py-4">
    <h2><i class="fas fa-language"></i> Language Settings</h2>
    <?php echo $message; ?>

    <div class="card mt-3">
        <div class="card-body">
            <form method="POST">
                <p>Select languages you want available in the front-end language dropdown.</p>
                <div class="row">
                    <?php foreach ($available as $code => $label): ?>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="langs[]" value="<?php echo $code; ?>" id="lang_<?php echo $code; ?>" <?php echo (!empty($enabled_map[$code]) ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="lang_<?php echo $code; ?>"><?php echo $label; ?> (<?php echo $code; ?>)</label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
