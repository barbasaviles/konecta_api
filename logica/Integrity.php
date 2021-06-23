<?php

namespace Framework;

class Integrity extends Loader
{

    private $instance_class;

    public function __construct($class, $type)
    {
        switch ($type) {
            case 1:
                $class .= 'Controller';
                $directory = 'controllers' . DS;
                break;
            case 2:
                $class .= 'Model';
                $directory = 'models' . DS;
                break;
            case 3:
                $class .= 'Report';
                $directory = 'reports-pdf' . DS;
                break;
        }

        $rutaClase = ROOT . $directory . $class . '.php';
        if (is_readable($rutaClase)) {
            require_once $rutaClase;
            $this->instance_class = $class::getInstancia();
            return $this->instance_class;
        }else {
            http_response_code(500);
            throw new \Exception('No se puede cargar la clase ' . $class);
        }
    }

    private function reflectionFilterType($type)
    {
        return null;
        $filter = 'filter' . ucfirst($type);
        return is_callable(['Framework\Filters', $filter]) ? $filter : null;
    }

    private function reflectionCheckTypeParams(\ReflectionMethod $reflect, $arguments)
    {
        $reflect_arguments = $reflect->getParameters();
        $num_arguments = count($arguments);
        $valid_arguments = true;
        for ($i = 0; $i < $num_arguments; $i++) {
            if (!$reflect_arguments[$i]->hasType()) {
                continue;
            }
            $argument_type = $reflect_arguments[$i]->getType();
            $filter = $this->reflectionFilterType($argument_type);

            if (!$filter) {
                continue;
            }

            $filtered_var = Filters::$filter($arguments[$i]);

            if ($argument_type != 'bool') {
                if ($filtered_var !== false && ($filtered_var == $arguments[$i] || $argument_type === 'string')) {
                    $arguments[$i] = $filtered_var;
                } elseif ($reflect_arguments[$i]->isOptional()) {
                    $arguments[$i] = $reflect_arguments[$i]->getDefaultValue();
                } elseif ($filtered_var !== null) {
                    $valid_arguments = false;
                    break;
                }
            } else {
                if ($filtered_var !== null) {
                    $arguments[$i] = $filtered_var;
                } elseif ($reflect_arguments[$i]->isOptional()) {
                    $arguments[$i] = $reflect_arguments[$i]->getDefaultValue();
                } else {
                    $valid_arguments = false;
                    break;
                }
            }
        }
        return $valid_arguments ? $arguments : false;
    }

    private function reflectionCheckEmptyParam($param)
    {
        $is_valid = false;
        if (is_array($param)) {
            foreach ($param as $p) {
                if ($this->reflectionCheckEmptyParam($p)) {
                    $is_valid = true;
                    break;
                }
            }
        } elseif (!empty($param)) {
            $is_valid = true;
        }
        return $is_valid;
    }

    private function reflectionCheckRequiredParams(\ReflectionMethod $reflectmethod, $arguments)
    {
        $parametros_obligatorios = $reflectmethod->getNumberOfRequiredParameters();

        if (!$parametros_obligatorios) {
            return true;
        }

        if (count($arguments) < $parametros_obligatorios) {
            return false;
        }

        $execute = true;
        for ($i = 0; $i < $parametros_obligatorios; $i++) {
            if (!$this->reflectionCheckEmptyParam($arguments[$i])) {
                $execute = false;
                break;
            }
        }
        return $execute;
    }

    public function __call($method, $arguments)
    {
        if (!is_callable([$this->instance_class, $method])) {
            http_response_code(500);
            throw new \Exception('No existe el metodo ' . $method);
        }

        $reflectmethod = new \ReflectionMethod($this->instance_class, $method);

        if (!$this->reflectionCheckRequiredParams($reflectmethod, $arguments)) {
            http_response_code(403);
            throw new \Exception('Faltan parametros requeridos para el metodo ' . $method);
        }

        $filtered_arguments = $this->reflectionCheckTypeParams($reflectmethod, $arguments);
        if (!is_array($filtered_arguments)) {
            http_response_code(403);
            throw new \Exception('La validacion de campos fallo para el metodo ' . $method);
        }

        return $this->instance_class->$method(...$filtered_arguments);
    }

}
