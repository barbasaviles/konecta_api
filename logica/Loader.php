<?php

namespace Framework;

abstract class Loader {

    private static $instances = array();

    /**
     * @return Loader
     */
    final public static function getInstancia() {
        $class = get_called_class();

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

}
