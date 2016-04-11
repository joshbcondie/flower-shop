<?php

//Autoload PHP classes
function loadClass($class_name) {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/flower-shop/classes/' . $class_name . '.php';

    error_log('[settings][loadClass]::$path: ' . print_r($path, true));

    if (file_exists($path)) {
        require_once($path);
    }
    elseif (stream_resolve_include_path($class_name . 'php') !== false) {
        require_once($class_name . '.php');
    }
    else {
        $paths  = explode(':', get_include_path());
        foreach ($paths as $path) {
            $filepath = $path . DIRECTORY_SEPARATOR . $class_name . '.php';
            if (file_exists($filepath)) {

                require_once($filepath);
            }
        }
    }
}
spl_autoload_register('loadClass');