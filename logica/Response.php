<?php

namespace Framework;

abstract class Response {

    private static $salida;

    public static function operacion(string $operacion) {
        self::$salida['operacion'] = $operacion;
    }

    public static function estado(string $estado) {
        self::$salida['estado'] = $estado;
    }

    public static function mensaje(string $mensaje) {
        self::$salida['mensaje'] = (self::$salida['mensaje'] ?? '') . ' ' . $mensaje;
    }

    public static function payload(array $payload) {
        self::$salida['payload'] = $payload;
    }
    
    public static function value(int $value) {
        self::$salida['value'] = $value;
    }

    public static function all(string $operacion = null, string $estado = null, string $mensaje = null, array $payload = null, int $value = null) {
        if ($operacion) {
            self::operacion($operacion);
        }

        if ($estado) {
            self::estado($estado);
        }

        if ($mensaje) {
            self::mensaje($mensaje);
        }

        if ($payload) {
            self::payload($payload);
        }
        
        if ($value) {
            self::value($value);
        }
    }

    public static function salida() {
        return self::$salida;
    }

}
