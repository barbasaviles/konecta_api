<?php


class productosModel extends \Framework\Model
{
    public function inventario(int $id = null){
        $sql = "SELECT * FROM productos WHERE TRUE ";
        $sql.= $id? " AND id=$id": "";
        $result = mysqli_query($this->link,$sql);
        return $id ? mysqli_fetch_assoc($result) : mysqli_fetch_all($result,MYSQLI_ASSOC);
    }

    public function guardar($params){
        if(isset($params['id'])) {
            $sql ="UPDATE productos SET nom_producto='".$params['nom_producto']."',referencia='".$params['referencia']."',categoria='".$params['categoria']."',precio=".$params['precio'].",peso=".$params['peso'].",stock=".$params['stock']." WHERE id=".$params['id'];
            $result =mysqli_query($this->link,$sql);
        }else{
            $sql = "INSERT INTO productos(nom_producto,referencia,categoria,precio,peso,stock) VALUES('".$params['nom_producto']."','".$params['referencia']."','".$params['categoria']."',".$params['precio'].",".$params['peso'].",".$params['stock'].")";
            $result =mysqli_query($this->link,$sql);
        }
    }

    public function venta($params){
            $sql = "INSERT INTO ventas(id_producto,cantidad,total) VALUES(".$params['id_producto'].",".$params['cantidad'].",".$params['total'].")";
            $result =mysqli_query($this->link,$sql);

            $sql_descuento = "UPDATE productos SET stock=(stock-".$params['cantidad']."), fecha_ultima_venta=now() WHERE id=".$params['id_producto'];
            $result =mysqli_query($this->link,$sql_descuento);
    }

    public function eliminar(int $id = null){
        mysqli_query($this->link,"DELETE FROM productos WHERE id=$id");
    }

    public function ventas(int $id = null){
        $sql = "SELECT v.*,p.nom_producto 
                FROM ventas v
                INNER JOIN productos p ON p.id=v.id_producto
                WHERE TRUE ";
        $sql.= $id? " AND id=$id": "";
        $result = mysqli_query($this->link,$sql);
        return $id ? mysqli_fetch_assoc($result) : mysqli_fetch_all($result,MYSQLI_ASSOC);
    }

    public function validarStock(int $id,int $cant){
        $existencia = false;
        $sql = "SELECT p.stock from productos p where p.id=$id";
        $result = mysqli_query($this->link,$sql);
        $pro = mysqli_fetch_assoc($result);
        if($pro['stock']>=$cant){
            $existencia = true;
        }
        return $existencia;
    }

}