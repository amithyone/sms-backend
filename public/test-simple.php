<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://fadsms.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, X-Requested-With');

echo json_encode([
    'status' => 'success',
    'message' => 'Simple PHP test working',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
]);
?>

