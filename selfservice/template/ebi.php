<?php

// EBI Template - ebi.php
// Enhanced template for self-service system

// Configuration settings
$config = [
    'debug_mode' => true, // Enable debug mode for ZPL printing
    'portaria_filter' => 'active', // Default active filter
];

// Function to edit configuration settings
function editConfig($key, $value) {
    global $config;
    if (array_key_exists($key, $config)) {
        $config[$key] = $value;
    }
}

// Debug function for ZPL printing
function debugZPLPrint($zplData) {
    global $config;
    if ($config['debug_mode']) {
        // Log or display ZPL data
        error_log("Debug Mode: ZPL Data - " . $zplData);
    }
}

// Function to export data to CSV/Excel
function exportData($data, $filename = 'export.csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Output header row
    fputcsv($output, array_keys($data[0]));

    // Output data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

// Improved filtering function for portaria
function filterPortaria($entries, $status = 'active') {
    return array_filter($entries, function($entry) use ($status) {
        return $entry['status'] === $status;
    });
}

/**
 * Main execution logic (for demonstration purposes)
 */

$data = [
    ['id' => 1, 'name' => 'Sample 1', 'status' => 'active'],
    ['id' => 2, 'name' => 'Sample 2', 'status' => 'inactive'],
];

$filteredData = filterPortaria($data);
// Use exportData function to export filtered data
// exportData($filteredData);

?>
