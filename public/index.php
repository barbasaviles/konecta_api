<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(dirname(__FILE__)) . DS);
define('APP_PATH', ROOT . 'logica' . DS);
define('APP_CONFIG', ROOT . 'config' . DS);
define('UPLOADS', ROOT . 'uploads' . DS);
define('LIBRERY', ROOT . 'librery' . DS);
define('TMP', ROOT . 'tmp' . DS);
require_once ROOT . 'vendor' . DS . 'autoload.php';
try {
    require_once APP_CONFIG . 'Db.php';
    require_once APP_CONFIG . 'Constantes.php';
    require_once APP_CONFIG . 'Configuracion.php';
    Framework\Bootstrap::iniciar();
} catch (Exception $exc) {
    Framework\Logger::$log->error($exc->getMessage());
}
