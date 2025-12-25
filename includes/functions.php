<?php
/**
 * Helper Functions
 */
// Ensure config is loaded to have $conn
if (!isset($conn)) {
    $configPath = __DIR__.'/../config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}
// Ensure the system_settings table exists
if (!function_exists('ensure_system_settings_table')) {
    function ensure_system_settings_table($conn) {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(255) UNIQUE NOT NULL,
                setting_value VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            mysqli_query($conn, $sql);
        } catch (Exception $e) {
            // Silently fail if table already exists or creation fails
        }
    }
}

if (isset($conn) && $conn instanceof mysqli) {
    ensure_system_settings_table($conn);
}


if (!function_exists('get_setting')) {
    /**
     * Get a setting value from the database
     * @param mysqli $conn Database connection
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @return mixed Setting value
     */
    function get_setting($conn, $key, $default = null) {
        if (!$conn || !($conn instanceof mysqli)) return $default;
        
        $key = mysqli_real_escape_string($conn, $key);
        $sql = "SELECT setting_value FROM system_settings WHERE setting_key = '$key'";
        
        try {
            $result = mysqli_query($conn, $sql);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return $row['setting_value'];
            }
        } catch (Exception $e) {
            // If table doesn't exist, it will throw an exception in PHP 8.1+
            // We can try to create the table and then try again, or just return default
            ensure_system_settings_table($conn);
        }
        
        return $default;
    }
}

if (!function_exists('update_setting')) {
    /**
     * Update or Insert a setting value
     * @param mysqli $conn Database connection
     * @param string $key Setting key
     * @param string $value Setting value
     * @return bool Success or failure
     */
    function update_setting($conn, $key, $value) {
        if (!$conn || !($conn instanceof mysqli)) return false;
        
        $key = mysqli_real_escape_string($conn, $key);
        $value = mysqli_real_escape_string($conn, $value);
        
        try {
            // Check if exists
            $check = mysqli_query($conn, "SELECT id FROM system_settings WHERE setting_key = '$key'");
            
            if ($check && mysqli_num_rows($check) > 0) {
                $sql = "UPDATE system_settings SET setting_value = '$value' WHERE setting_key = '$key'";
            } else {
                $sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES ('$key', '$value')";
            }
            
            return mysqli_query($conn, $sql);
        } catch (Exception $e) {
            ensure_system_settings_table($conn);
            return false;
        }
    }
}
?>
