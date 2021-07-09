<?php


class productosModel extends \Framework\Model
{
    public function inventario(){
        $sql = "SELECT * FROM productos";
        return mysqli_fetch_all(mysqli_query($this->link,$sql),MYSQLI_ASSOC);
    }

    public function deportistasTop(){

    }
}