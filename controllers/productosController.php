<?php


class productosController extends \Framework\Controller
{
    public function inventario(){
        return $this->modelo->inventario();
    }

}