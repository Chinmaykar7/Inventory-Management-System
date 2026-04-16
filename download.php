<?php
session_start();

// 1. Connect to the database to get the live data!
include 'db_connect.php';

// Security: Only logged-in users can download
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$zipFileName = 'Live_Inventory_Dataset_' . date('Y-m-d_H-i') . '.zip';
$zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;
$zip = new ZipArchive();

if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    
    // 2. Map the desired CSV filenames to our actual database tables
    $tables = [
        'Stock Master.csv' => 'stock_master',
        'Transaction Master.csv' => 'transaction_master',
        'Lost Table.csv' => 'lost_table',
        'Waste Table.csv' => 'waste_table'
    ];
    
    $tempFiles = []; // Keep track of temporary files to clean up later

    // 3. Loop through each table, pull the live data, and write it to a CSV
    foreach ($tables as $fileName => $tableName) {
        // Create a temporary physical file (better for memory with 100k+ rows)
        $tempCsv = tempnam(sys_get_temp_dir(), 'csv_');
        $filePointer = fopen($tempCsv, 'w');
        
        $result = $conn->query("SELECT * FROM $tableName");
        
        if ($result && $result->num_rows > 0) {
            // Grab the column names for the CSV header
            $fields = $result->fetch_fields();
            $headers = [];
            foreach ($fields as $field) {
                $headers[] = $field->name;
            }
            fputcsv($filePointer, $headers);
            
            // Loop through the database rows and write them to the CSV
            while ($row = $result->fetch_assoc()) {
                fputcsv($filePointer, $row);
            }
        } else {
            fputcsv($filePointer, ['No data found']);
        }
        
        fclose($filePointer);
        
        // Add the freshly generated CSV to our Zip file
        $zip->addFile($tempCsv, $fileName);
        $tempFiles[] = $tempCsv;
    }
    
    $zip->close();
    
    // 4. Force the browser to download the Zip file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
    header('Content-Length: ' . filesize($zipFilePath));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($zipFilePath);
    
    // 5. Clean up: Delete the zip and all temporary CSVs from the server
    unlink($zipFilePath);
    foreach ($tempFiles as $temp) {
        unlink($temp);
    }
    exit();
} else {
    echo "Error: Failed to create the zip archive on the server.";
}
?>