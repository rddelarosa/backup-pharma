<?php
spl_autoload_register(function ($class_name) {
    $base_dir = __DIR__ . '/google-api-client/src/'; 
    
    $class_file = $base_dir . str_replace('\\', '/', $class_name) . '.php';

    if (file_exists($class_file)) {
        require_once $class_file;
    } else {
        error_log("Autoload failed for class: $class_name, file not found: $class_file");
    }
});
