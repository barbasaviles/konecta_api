<?php


namespace Framework;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\ChromePHPHandler;

/**
 * Class Logger
 * @package Framework
 */
class Logger {
    /**
     * @var MonologLogger
     */
    public static $log;

    public static function init() {
        self::$log = new MonologLogger(APLICACION);
        self::$log->pushHandler(new ErrorLogHandler());

        if (DEBUG_MODE) {
            self::$log->pushHandler(new ChromePHPHandler());
        }
    }
}