<?php
include_once('conexion.php');

class model
{
    public function __construct($id)
    {
        conexion::login($id);
    }

    public function listCupos(){
        $sql = "SELECT tc.id,tc.nom_curso
                FROM public.t_admis_cupos_cursos tacc
                INNER JOIN public.t_configs_generales_anios tcga ON tcga.anio_lectivo=tacc.anio_cupos 
                AND tcga.en_admisiones=TRUE
                INNER JOIN public.t_cursos tc ON tacc.id_curso = tc.id";
        $result = pg_query($sql);
        return pg_fetch_all($result);
    }

    public function save($params_persona,$params_inscrip){
        pg_query('BEGIN');
        $sql_anio = "SELECT tcga.anio_lectivo
                     FROM public.t_configs_generales_anios tcga
                     WHERE en_admisiones=TRUE";
        $config = pg_fetch_assoc(pg_query($sql_anio));
        if($config){
            pg_query('SET SESSION cs.id_usuario=1');
            $sql_person = "INSERT INTO public.t_personas(id_tipo_documento,num_documento,nombres,apellidos,
                           fecha_nacimiento,sexo,correo,telefonos) 
                           VALUES($1,$2,$3,$4,$5,$6,$7,$8) ON CONFLICT(num_documento) 
                           DO UPDATE SET num_documento=EXCLUDED.num_documento,correo=EXCLUDED.correo RETURNING id";
            $result = pg_query_params($sql_person,$params_persona);
            $persona = pg_fetch_assoc($result);

            pg_query_params('INSERT INTO public.t_estudiantes(id_persona) 
                                       VALUES($1) ON CONFLICT DO NOTHING',[$persona['id']]);

            $params_inscrip['anio_solicitud'] = $config['anio_lectivo'];
            $params_inscrip['nuevo_estudiante'] = 'true';
            $params_inscrip['fecha_solic'] = 'now()';
            $params_inscrip['id_estudiante_solic'] = $persona['id'];
            $params_inscrip['admitido'] = 'false';
            $params_inscrip['id_estado_solic'] = '1';

            $sql_inscrip = "INSERT INTO public.t_matric_solicitudes(id_curso_aspira,referencia_pago,id_upload_pago,
                            anio_solicitado,nuevo_estudiante,fecha_solic,id_estudiante_solic,admitido,id_estado_solic)
                            VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9)  ON CONFLICT DO NOTHING  RETURNING id";
            $result = pg_query_params($sql_inscrip,$params_inscrip);
            pg_query('COMMIT');
            return pg_fetch_assoc($result);
        }else{
            return -1;
        }
    }

    public function saveUpload($params)
    {

        $sql = 'INSERT INTO public.t_uploads (nom_archivo, ruta, tamanio, mime, id_usuario) 
                    VALUES ($1, $2, $3, $4, 1) RETURNING *';

        $result = pg_query_params($sql, $params);
        if (pg_last_error() || !$result || !pg_affected_rows($result)) {
            return -3;
        }

        return pg_fetch_assoc($result);
    }

}