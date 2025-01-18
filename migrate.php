<?php

require_once __DIR__ . '/vendor/autoload.php';

use YoutubeChatCapture\Database\MigrationManager;
use YoutubeChatCapture\Database\DB;

try {
    $manager = new MigrationManager(DB::getInstance());
    $manager->migrate();
    echo "Migrations completed successfully!\n";
} catch (Exception $e) {
    echo "Error running migrations: " . $e->getMessage() . "\n";
    exit(1);
} 