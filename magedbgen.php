<?php

echo "MySQL dump to process: ";
$mysqlDump = null;
if (!empty($argv[1])) {
    $filePath = $argv[1];
    echo $filePath . "\n";
} else {
    $handle = fopen("php://stdin","r");
    $filePath = trim(fgets($handle));
    fclose($handle);
    if (strpos($filePath, "\n") !== "false") {
        $mysqlDump = $filePath;
    }
}

if (!file_exists($filePath) && empty($mysqlDump)) {
    die("File '${filePath}' could not be found or is not accessible.\n");
}



?>
