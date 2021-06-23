<?php


class torneosController extends \Framework\Controller
{
    public function activos(){
        return $this->modelo->activos();
    }

}