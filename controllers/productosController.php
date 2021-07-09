<?php

use Framework\request;
use Framework\Response;

class productosController extends \Framework\Controller
{
    public function inventario()
    {
        $id = request::getInt("id");
        return $this->modelo->inventario($id);
    }

    public function guardar()
    {
        $id = Request::getInt('id');
        $nom = Request::getString('nom', 100);
        $ref = Request::getString('ref', 100);
        $cat = Request::getString("cat", 100);
        $peso = Request::getInt("peso");
        $precio = Request::getInt("precio");
        $stock = Request::getInt("stock");
        $edit = $id ? ['id' => $id] : [];

        $params = $edit + [
                'nom_producto' => $nom,
                'referencia' => $ref,
                'categoria' => $cat,
                'precio' => $precio,
                'peso' => $peso,
                'stock' => $stock
            ];

        $this->modelo->guardar($params);
        $mensaje = $id ? "Producto Actualizado" : "Producto Registrado";
        Response::all($id ? 'edicion' : 'nuevo', 'success', $mensaje);
        return Response::salida();
    }

    public function eliminar()
    {
        $id = request::getInt("id");
        var_dump($id);
        $this->modelo->eliminar($id);
    }

}