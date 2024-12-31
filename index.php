<?php

// Code pour utiliser la librairie `crypto.php`

require "crypto.php";

$pdo = get_pdo();
$json_api_response = get_api_json();
// $json_api_response = get_sample_api_json();  // Pour les tests
$api_response = json_to_array($json_api_response);

try {
    put_data_to_tables($api_response, $pdo);
    echo "Data inserted successfully.<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

try {
    $tables_data = get_data_from_tables($pdo);
    echo "Data extracted from tables successfully.<br>";
    echo "Last tables data: " . array_to_json($tables_data);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

?>