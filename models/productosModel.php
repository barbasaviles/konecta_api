<?php


class productosModel extends \Framework\Model
{
    public function productos(){
        $sql = "SELECT * FROM productos";
        return mysqli_fetch_all(mysqli_query($this->link,$sql));
    }

    public function deportistasTop(){

    }
}