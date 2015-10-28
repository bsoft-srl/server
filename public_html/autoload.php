<?php
if (!defined('SIDECO_INIT')) die('You shall not pass!');

define('ROOT_PATH', __DIR__ . '/../');

require_once ROOT_PATH . '/vendor/autoload.php';

spl_autoload_register(function ($class) {
    $prefix = 'Sideco\\';
    $base_dir = ROOT_PATH . '/src/';

    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) return;

    $relative_class = substr($class, $len);

    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (is_readable($file)) require $file;
});
