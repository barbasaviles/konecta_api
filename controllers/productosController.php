<?php


class torneosController extends \Framework\Controller
{
    public function productos(){
        return $this->modelo->productos();
    }

}