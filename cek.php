<?php
echo json_encode([
    'extensions' => get_loaded_extensions(),
    'pdo_drivers' => PDO::getAvailableDrivers()
]);