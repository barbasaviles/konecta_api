<?php
namespace Framework;

abstract class request
{
    private static $controlador;
    private static $metodo;
    private static $parametros;
    private static $http_params;

    public static function contentType() {
        return filter_input(INPUT_SERVER, 'CONTENT_TYPE');
    }

    public static function urlParser() {
        $url = array_filter(explode('/', filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL)));

        self::$controlador = array_shift($url);
        self::$metodo = array_shift($url);
        self::$parametros = $url ?? [];
    }

    public static function controlador() {
        return self::$controlador;
    }

    public static function metodo() {
        return self::$metodo;
    }

    public static function args() {
        return self::$parametros;
    }

    public static function inputParser() {
        $input = file_get_contents('php://input');
        if (strpos(self::contentType(), 'application/json') !== false) {
            $params = json_decode($input, true);
        } else {
            mb_parse_str($input, $params);
        }
        self::$http_params = array_merge($_GET, $params);
    }

    public static function getString(string $path, int $size = null) {
        return Filters::filterString(Filters::filterArrayPath($path, self::$http_params), $size);
    }

    public static function getInt(string $path, array $options = ['default' => null, 'min_range' => 1]) {
        return Filters::filterInt(Filters::filterArrayPath($path, self::$http_params), $options);
    }
}