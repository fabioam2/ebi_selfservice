<?php

// config_manager.php - Admin interface for editing config.ini settings

// Include necessary files
include 'config.php';

// Function to read configurations
function readConfig() {
    return parse_ini_file('config.ini');
}

// Function to write configurations
function writeConfig($data) {
    $content = '';
    foreach ($data as $key => $value) {
        $content .= "$key = '$value'\n";
    }
    return file_put_contents('config.ini', $content);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configData = [
        'security_password' => $_POST['security_password'],
        'security_protection' => $_POST['security_protection'],
        'printer_zpl' => $_POST['printer_zpl'],
        'interface_customization' => $_POST['interface_customization'],
        'resource_enabled' => $_POST['resource_enabled']
    ];

    // Validate input (simple validation)
    foreach ($configData as $key => $value) {
        if (empty($value)) {
            die('Error: ' . ucfirst($key) . ' cannot be empty.');
        }
    }

    // Write changes to config.ini
    if (writeConfig($configData)) {
        echo 'Configuration updated successfully.';
    } else {
        echo 'Error writing to configuration file.';
    }
}

// Read current configurations
$config = readConfig();

?>

<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Config Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { background: #f4f4f4; padding: 20px; border-radius: 5px; }
        input, select { margin-bottom: 15px; width: 100%; padding: 10px; }
        input[type='submit'] { background: #5cb85c; color: white; border: none; }
    </style>
</head>
<body>
    <h2>Configuration Manager</h2>
    <form method='POST'>
        <h3>Security Settings</h3>
        <label for='security_password'>Password:</label>
        <input type='text' id='security_password' name='security_password' value='<?php echo htmlspecialchars($config['security_password']); ?>'>
        <label for='security_protection'>Protection Options:</label>
        <input type='text' id='security_protection' name='security_protection' value='<?php echo htmlspecialchars($config['security_protection']); ?>'>

        <h3>Printer Settings</h3>
        <label for='printer_zpl'>ZPL Parameters:</label>
        <input type='text' id='printer_zpl' name='printer_zpl' value='<?php echo htmlspecialchars($config['printer_zpl']); ?>'>

        <h3>Interface Customization</h3>
        <label for='interface_customization'>Customization Options:</label>
        <input type='text' id='interface_customization' name='interface_customization' value='<?php echo htmlspecialchars($config['interface_customization']); ?>'>

        <h3>Resource Management</h3>
        <label for='resource_enabled'>Enable/Disable Resource:</label>
        <select id='resource_enabled' name='resource_enabled'>
            <option value='1' <?php echo $config['resource_enabled'] == 1 ? 'selected' : ''; ?>>Enabled</option>
            <option value='0' <?php echo $config['resource_enabled'] == 0 ? 'selected' : ''; ?>>Disabled</option>
        </select>

        <input type='submit' value='Save Changes'>
    </form>
</body>
</html>
