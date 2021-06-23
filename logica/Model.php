<?php

namespace Framework;

use PommProject\Foundation\Pomm;
use PommProject\Foundation\QueryManager\SimpleQueryManager;
use Framework\Session;

/**
 * Class Model
 * @package Framework
 * @property SimpleQueryManager $db
 */
abstract class Model extends \Framework\Loader {

    protected $db;
    protected $pomm;
    protected $log;

    public function __construct() {
        $dsn = $this->getConnections();
//        $this->pomm = new Pomm([
//            'instance' => ['dsn' => $dsn['instance']],
//            'login' => ['dsn' => $dsn['login']]
//        ]);

//        $session = $this->pomm->getSession('instance');
//        $this->db = $session->getQueryManager();
//        $uuid_keycloak = Session::get('sub');
//        $rol =  Session::get('roles')[0];
//        $this->db->query("SET SESSION fp.uuid_keycloak = '$uuid_keycloak'");
//        $this->db->query("SET SESSION fp.nom_rol = '$rol'");
//        $this->log = Logger::$log;
    }

    private function getConnections() {

        $raw_conn_string = 'host=:host port=:port dbname=:dbname user=:user password=:password';
        $params = [
            ':host' => DB_HOST,
            ':port' => DB_PORT, ':dbname' => DB_NAME,
            ':user' => DB_USER, ':password' => DB_PASS
        ];

        $connection = @pg_connect(strtr($raw_conn_string, $params));
        if (!$connection) {
            http_response_code(500);
            throw new \Exception('Ocurrió un problema al conectar a la base de datos');
        }

//        $port = DEBUG_PORT ?? 'puerto';
//        $sql = "SELECT concat('pgsql://', usuario_db, ':', contrasenia_db, '@', servidor, ':', $port , '/', nombre_db) dsn
//        FROM t_instancias WHERE uuid = $1";
//        $result = pg_query_params($connection, $sql, [Session::get('conn')]);
//        if (pg_last_error() || !$result || !pg_num_rows($result)) {
//            http_response_code(500);
//            throw new \Exception('No se pudo obtener la instancia para esta base de datos');
//        }
//
//        $dsn = [];
//        $dsn['instance'] = pg_fetch_result($result, 0, 'dsn');
//        $dsn['login'] = strtr('pgsql://:user::password@:host::port/:dbname', $params);
//
//        return $dsn;
    }

    protected function verificarUnicidad($tabla, $registros, $llave_primaria = NULL) {
        $id = $llave_primaria ?? 'id';
        $unicidades = $this->obtenerIndices($tabla);
        $num_index = count($unicidades);

        if (!$unicidades || ($num_index === 1 && $unicidades[0][0] === $id)) {
            // no hay unicidades que verificar
            return true;
        }

        $unicidad_conjunta = function ($unicidad) use ($registros) {
            $num_colums = count($unicidad);
            $arr_unicidad = [];
            for ($i = 0; $i < $num_colums; $i++) {
                $col_name = $unicidad[$i];
                if (isset($registros[$col_name])) {
                    $value = pg_escape_literal($registros[$col_name]);
                    $arr_unicidad[] = "$col_name::TEXT ILIKE $value";
                }
            }
            return !empty($arr_unicidad) ? '(' . implode(' AND ', $arr_unicidad) . ')' : '';
        };

        $arr_condiciones = [];
        for ($j = 0; $j < $num_index; $j++) {
            $arr_condiciones[] = $unicidad_conjunta($unicidades[$j]);
        }
        $sql_condiciones = implode(' OR ', array_filter($arr_condiciones));

        $sql = "SELECT $id FROM $tabla WHERE ($sql_condiciones)";
        $sql .= isset($registros[$id]) ? " AND $id::TEXT <> " . pg_escape_literal($registros[$id]) : '';
        $sql .= ' LIMIT 1';
        return pg_num_rows(pg_query($sql)) === 0;
    }

    protected function validarDatos(string $tabla, array $params) {
        $reglas = $this->reglasValidacion($tabla);

        if (isset($params[0])) {
            $status = true;
            foreach ($params as $param) {
                if (!$this->validarFila($reglas, $param)) {
                    $status = false;
                    break;
                }
            }
        } else {
            $status = $this->validarFila($reglas, $params);
        }

        return $status;
    }

    private function validarFila($reglas, $row) {
        $isValid = true;

        foreach ($row as $col_name => $value) {
            $regla = $reglas[$col_name] ?? null;

            if ($regla === null || ($regla['is_nullable'] === 'NO' && empty($value))) {
                $isValid = false;
                break;
            }

            $filter = $regla['data_type'];

            if (empty($value) || $filter === null) {
                continue;
            }

            if (($filter !== 'filterString' && Filters::$filter($value) === null) ||
                (Filters::filterString($value, $regla['character_maximum_length']) != $value)
            ) {
                $isValid = false;
                break;
            }
        }

        return $isValid;
    }

    private function reglasValidacion(string $tabla) {
        $sql = 'SELECT column_name, is_nullable, data_type, character_maximum_length,
        numeric_precision, numeric_scale
        FROM information_schema.columns
        WHERE table_name = $1';

        $tabla_schema = explode('.', $tabla);
        if (count($tabla_schema) > 1) {
            $params['table'] = $tabla_schema[1];
            $params['schema'] = $tabla_schema[0];

            $sql .= " AND table_schema=$2";
        } else {
            $params['table'] = $tabla;
        }

        $raw_validaciones = pg_fetch_all(pg_query_params($sql, $params));
        $validaciones = [];

        $filterName = function ($data_type) {
            switch ($data_type) {
                case 'integer':
                case 'bigint':
                    $filter = 'filterInt';
                    break;
                case 'numeric':
                    $filter = 'filterFloat';
                    break;
                case 'character varying':
                case 'text':
                case 'char':
                    $filter = 'filterString';
                    break;
                case 'boolean':
                    $filter = 'filterBool';
                    break;
                default :
                    $filter = null;
            }
            return $filter;
        };

        foreach ($raw_validaciones as $validacion) {
            $validacion['data_type'] = $filterName($validacion['data_type']);
            $validaciones[$validacion['column_name']] = $validacion;
        }

        return $validaciones;
    }

    /**
     * función auxiliar de verificarUnicidad, consulta las uniqueKeys de la tabla que se pase
     * por argumentos
     * @param string $tabla nombre de la tabla a consultar: t_personas, t_clases, etc
     * @return array array con las llaves únicas indicando la(s) columna(s) que afectan.
     */
    private function obtenerIndices($tabla) {
        $table_schema = explode('.', $tabla);
        if (count($table_schema) > 1) {
            $sql_table = "AND ix.indrelid = '$tabla'::regclass";
        } else {
            $sql_table = "AND t.relname = '$tabla'";
        }

        $sql = "SELECT i.relname index_name, array_agg(a.attname::VARCHAR) AS columns
        FROM pg_class t, pg_class i, pg_index ix, pg_attribute a
        WHERE t.oid = ix.indrelid
        AND i.oid = ix.indexrelid
        AND a.attrelid = t.oid
        AND a.attnum = ANY(ix.indkey)
        $sql_table
        GROUP BY t.relname, i.relname";

        $datos = $this->pg_fetch_all_parsed(pg_query($sql));
        return array_column($datos, 'columns');
    }

    /**
     * Metodo auxiliar de pg_fetch_parsed. Contiene las reglas usadas para convertir
     * un string retornado por pg_fetch_all(por ejemplo) a un valor nativo de php
     * @return array con las reglas usadas para la conversion de tipos.
     */
    private function pgToPHPMap($result) {
        $map_types = [
            'int4' => function ($int) {
                return (int)$int;
            },
            '_varchar' => function ($char_array) {
                return explode(',', substr($char_array, 1, -1));
            },
            '_text' => function ($char_array) {
                return explode(',', substr($char_array, 1, -1));
            },
            '_int4' => function ($int_array) {
                $substring = substr($int_array, 1, -1);
                if ($substring === '') {
                    return [];
                }
                $array = explode(',', $substring);
                foreach ($array as &$val) {
                    $val = (int)$val;
                }
                unset($val);
                return $array;
            },
            'numeric' => function ($number) {
                return (float)$number;
            },
            'float8' => function ($float) {
                return (float)$float;
            },
            'bool' => function ($bool) {
                return $bool === 't';
            },
            'json' => function ($json) {
                return json_decode($json, true);
            },
            'jsonb' => function ($json) {
                return json_decode($json, true);
            },
            'hstore' => function ($hstore) {
                $hstore_arr = explode(',', str_replace(['"', ' '], '', $hstore));
                $parsed = [];
                foreach ($hstore_arr as $hs_pairs) {
                    list($key, $value) = explode('=>', $hs_pairs);
                    $parsed[$key] = $value !== 'NULL' ? $value : null;
                }
                return $parsed;
            }
        ];

        $num_colums = pg_num_fields($result);
        $parser = [];

        for ($i = 0; $i < $num_colums; $i++) {
            $field_type = pg_field_type($result, $i);
            if (isset($map_types[$field_type])) {
                $parser[pg_field_name($result, $i)] = $map_types[$field_type];
            }
        }

        return $parser;
    }

    /**
     * Obtiene el resultado de una consulta postgres haciendo la conversión para los tipos
     * de datos mas comunes.
     * @param resource $result pg query result, retornado por pg_query_params, pg_query, pg_execute...
     * @return array
     */
    protected function pg_fetch_one_parsed($result) {
        if (pg_num_rows($result) < 1) {
            return [];
        }

        $parser = $this->pgToPHPMap($result);
        $row = pg_fetch_assoc($result);

        foreach ($row as $col_name => &$value) {
            if ($value !== null && isset($parser[$col_name])) {
                $value = $parser[$col_name]($value);
            }
        }

        unset($value);
        return $row;
    }

    /**
     * Obtiene los resultados de una consulta postgres haciendo la conversión para los tipos
     * de datos mas comunes.
     * @param resource $result pg query result, retornado por pg_query_params, pg_query, pg_execute...
     * @return array
     */
    protected function pg_fetch_all_parsed($result): array {
        if (pg_num_rows($result) < 1) {
            return [];
        }

        $parser = $this->pgToPHPMap($result);
        $rows = pg_fetch_all($result);

        foreach ($rows as &$row) {
            foreach ($row as $col_name => &$value) {
                if ($value !== null && isset($parser[$col_name])) {
                    $value = $parser[$col_name]($value);
                }
            }
        }

        unset($row, $value);
        return $rows;
    }

    /**
     * Carga un modelo
     * @param string $model modelo a cargar
     * @return \Integrity instancia del modelo solicitado
     */
    protected function cargarModelo(string $model) {
        return new Integrity($model, 2);
    }

    public static function tableValidations(string $tabla) {
        $sql = 'SELECT column_name, is_nullable, data_type, character_maximum_length,
        numeric_precision, numeric_scale
        FROM information_schema.columns
        WHERE table_name = $1';

        $result = pg_query_params($sql, [$tabla]);
        return pg_fetch_all($result);
    }

    static function setPostgresSession(string $variable, string $valor = null) {
        $sql = "SET SESSION $variable = " . pg_escape_literal($valor);
        $result = pg_query($sql);
        return $result ? true : false;
    }

    public static function getInfoModulo(string $nom_modulo) {
        $sql = 'SELECT * FROM t_modulos WHERE nom_modulo = $1';
        $result = pg_query_params($sql, [$nom_modulo]);

        return pg_fetch_assoc($result) ?: [];
    }

}
