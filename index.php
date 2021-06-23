<?php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(dirname(__FILE__)) . DS);
define('APP_PATH', ROOT . 'logica' . DS);
define('APP_CONFIG', ROOT . 'config' . DS);
try {
    require_once APP_CONFIG . 'Db.php';
    require_once APP_CONFIG . 'Constantes.php';
    Framework\Bootstrap::iniciar();
} catch (Exception $exc) {
    Framework\Logger::$log->error($exc->getMessage());
}