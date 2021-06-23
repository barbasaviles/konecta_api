<?php

namespace Framework;

class Bootstrap extends Loader {

    public static function iniciar() {
        Logger::init();
        request::urlParser();
        request::inputParser();
        self::core();
    }

    private static function core() {
        $controller = request::controlador();
        $method = request::metodo();
        $instance_controller = new Integrity($controller, 1);
        if (!is_callable([$controller . 'controller', $method], true)) {
            $response = ['error' => 'method_not_callable', 'error_description' => "method $method from controller $controller is not callable"];
            self::responseHandler($response, 500);
            throw new \Exception($response['error_description']);
        }
        setlocale(LC_TIME, FRAMEWORK_LOCALE);
        self::responseHandler($instance_controller->$method(...Request::args()));
    }

    private static function responseHandler($response, $http_statuscode = 200) {
        http_response_code($http_statuscode);
        if (is_array($response)) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            echo $response;
        }
    }
}
