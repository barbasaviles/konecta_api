<?php


class torneosModel extends \Framework\Model
{
    public function activos(){
        $sql = "SELECT * FROM public.t_torneos";
        return pg_fetch_all(pg_query($sql));
    }
}