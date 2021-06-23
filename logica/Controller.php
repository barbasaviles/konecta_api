<?php

namespace Framework;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

abstract class Controller extends Loader {

    private $controlador;
    protected $modelo;

    public function __construct() {
        $this->controlador = str_replace('Controller', '', get_class($this));
        $this->modelo = $this->cargarModelo($this->controlador);
    }

    protected function cargarControlador($model) {
        return new Integrity($model, 1);
    }

    protected function cargarModelo($model) {
        return new Integrity($model, 2);
    }

}