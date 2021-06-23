<?php

abstract class conexion
{
    public static function login(int $id){
        $connection_string= "host=".DB_HOST." port=".DB_PORT." dbname=".DB_NAME." user=".DB_USER." password=".DB_PASS;
        pg_connect($connection_string) or die('connection failed');;
        $localConexion = self::instancia($id);
        self::Conexioncliente($localConexion);
    }

    private static function instancia(int $id){
        $sql="SELECT nombre_db FROM public.t_instancias WHERE id=$1";
        $result = pg_query_params($sql,[$id]);
        return pg_fetch_assoc($result);
    }

    private static function Conexioncliente($param){
        if($param){
            $connection_string= strtr("host=localhost port=5432 dbname=nombre_db user=postgres password=Apolo11",$param);
            pg_connect($connection_string) or die('connection failed');;
        }
    }

}