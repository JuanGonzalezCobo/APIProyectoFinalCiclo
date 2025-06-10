<?php

namespace App\Helpers;

use DateTime;
use Psr\Http\Message\ResponseInterface as Response;
use PDO;
use PDOException;
use Exception;

error_reporting(0);

class Peticiones
{
    /**
     * @param string $tabla         nombre de la tabla a obtener valores
     * @param array $primaryKey     parametros del primary key ['campo' => [valor, PDO::PARAM_INT], 'campo2 => [valor2, PDO::PARAM_STR]]
     * @param array $campos         lista de campos a mostrar ['campo1', 'campo2', ...]
     */
    public static function getDatosTabla(string $tabla, array $primaryKey, array $campos): mixed
    {

        $numPk = sizeof($primaryKey);
        $numCampos = sizeof($campos);
        if ($numPk > 0 && $numCampos > 0) {
            $sqlSelect = implode(",", $campos);
            $sqlWhere = "";
            $parametrosSql = [];
            foreach ($primaryKey as $nombre => $valor) {
                if ($sqlWhere != '') {
                    $sqlWhere = $sqlWhere . ' and ';
                }
                $sqlWhere = $sqlWhere . $nombre . '=:' . $nombre;
                $parametrosSql[$nombre] = $valor;
            }

            $sql = "SELECT $sqlSelect FROM $tabla WHERE $sqlWhere";
            $datos = Queries::leer($sql, $parametrosSql);

            if ($datos['status'] == 'ok' && isset($datos['data'][$campos[0]])) {
                return ($numCampos > 1) ? $datos['data'] : $datos['data'][$sqlSelect];
            }
        }
        return ($numCampos > 1) ? [] : 0;
    }

    public static function obtenerLocalTabla(string $local)
    {
        return str_pad($local, 4, '0', STR_PAD_LEFT);
    }
}
