<?php

namespace App\Modules\Encargos;

error_reporting(0);

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use PDO;

class EncargosController extends Controller
{
    public function listar(Request $request, Response $response, array $args): Response
    {
        $sql = <<<SQL
            SELECT enc.ID as ID_ENCARGO,
                enc.FECHA_HORA_ESTIMADA_RECOGIDA as FECHA_RECOGIDA,
                enc.FINALIZADO as FINALIZADO,
                enc.IMPRESO as IMPRESO,
                enc.NOTAS as NOTAS,
                est.NOMBRE as NOMBRE_EST_RECOGIDA,
                est.COLOR as COLOR_EST_RECOGIDA,
                cli.NOMBRE as NOMBRE_CLIENTE,
                cli.TELEFONO as TELEFONO
            FROM ENCARGO enc
            JOIN ESTABLECIMIENTO est ON enc.LOCAL_RECOGIDA = est.ID
            JOIN CLIENTE cli ON cli.ID = enc.CLIENTE_ID
            WHERE enc.FECHA_HORA_ESTIMADA_RECOGIDA BETWEEN :horaIncio AND :horaFinal
            AND est.BORRADO = 0
            AND(
                (1=:sin_filtro) 
                OR 
                (0=:sin_filtro
                    AND ((1=:impreso and enc.IMPRESO = 1)
                        OR (1=:finalizado and enc.FINALIZADO = 1)
                        OR (1=:cancelado and enc.CANCELADO = 1)
                        OR (0=:cancelado and enc.CANCELADO = 0 AND 0=:finalizado AND enc.FINALIZADO = 0 AND 0=:impreso AND enc.IMPRESO = 0)
                    )
                )
            )
            AND(cli.TELEFONO like :telefonoCliente)
            AND(est.NOMBRE like :nombreLocal)
            ORDER BY enc.ID;
        SQL;

        $parametros = $request->getQueryParams();
        if (!array_key_exists('sin_filtro', $parametros)) {
            $parametros['sin_filtro'] = 0;
        }

        if (!array_key_exists('telefonoCliente', $parametros)) {
            $parametros['telefonoCliente'] = '%';
            
        }

        if (!array_key_exists('nombreLocal', $parametros)) {
            $parametros['nombreLocal'] = '%';
            
        }

        $datos = [
            'horaIncio' => [$parametros['fecha'] . ' 00:00:00', PDO::PARAM_STR],
            'horaFinal' => [$parametros['fecha'] . ' 23:59:59', PDO::PARAM_STR],
            'sin_filtro' => [$parametros['sin_filtro'], PDO::PARAM_INT],
            'telefonoCliente' => [$parametros['telefonoCliente'], PDO::PARAM_STR],
            'impreso' => [$parametros['impreso'], PDO::PARAM_INT],
            'finalizado' => [$parametros['finalizado'], PDO::PARAM_INT],
            'cancelado' => [$parametros['cancelado'], PDO::PARAM_INT],
            'nombreLocal' => [$parametros['nombreLocal'], PDO::PARAM_STR]
        ];


        $respuesta_query = Queries::listar($sql, $datos);

        if ($respuesta_query['status'] != 'ok') {
            return Utils::responseJsonError($response, message: $respuesta_query['data']);
        }

        return Utils::responseJsonOk($response, data: $respuesta_query['data']);
    }

    public function listarEncargosFuturos(Request $request, Response $response, array $args): Response
    {
        $sql = <<<SQL
            SELECT enc.ID as ID_ENCARGO,
                enc.FECHA_HORA_ESTIMADA_RECOGIDA as FECHA_RECOGIDA,
                enc.FINALIZADO as FINALIZADO,
                enc.IMPRESO as IMPRESO,
                enc.NOTAS as NOTAS,
                est.NOMBRE as NOMBRE_EST_RECOGIDA,
                est.COLOR as COLOR_EST_RECOGIDA,
                cli.NOMBRE as NOMBRE_CLIENTE
            FROM ENCARGO enc
            JOIN ESTABLECIMIENTO est ON enc.LOCAL_RECOGIDA = est.ID
            JOIN CLIENTE cli ON cli.ID = enc.CLIENTE_ID
            WHERE enc.FECHA_HORA_ESTIMADA_RECOGIDA > :fecha
            AND est.BORRADO = 0
            AND(
                (1=:sin_filtro) 
                OR 
                (0=:sin_filtro
                    AND ((1=:impreso and enc.IMPRESO = 1)
                        OR (1=:finalizado and enc.FINALIZADO = 1)
                        OR (1=:cancelado and enc.CANCELADO = 1)
                        OR (0=:cancelado and enc.CANCELADO = 0 AND 0=:finalizado AND enc.FINALIZADO = 0 AND 0=:impreso AND enc.IMPRESO = 0)
                    )
                )
            )
            AND(cli.TELEFONO like :telefonoCliente)
            AND(est.NOMBRE like :nombreLocal)
            ORDER BY enc.ID;
        SQL;

        $parametros = $request->getQueryParams();
        if (!array_key_exists('sin_filtro', $parametros)) {
            $parametros['sin_filtro'] = 0;
        }

        if (!array_key_exists('telefonoCliente', $parametros)) {
            $parametros['telefonoCliente'] = '%';
            
        }

        if (!array_key_exists('nombreLocal', $parametros)) {
            $parametros['nombreLocal'] = '%';
            
        }

        $datos = [
            'fecha' => [$parametros['fecha'], PDO::PARAM_STR],
            'sin_filtro' => [$parametros['sin_filtro'], PDO::PARAM_INT],
            'telefonoCliente' => [$parametros['telefonoCliente'], PDO::PARAM_STR],
            'impreso' => [$parametros['impreso'], PDO::PARAM_INT],
            'finalizado' => [$parametros['finalizado'], PDO::PARAM_INT],
            'cancelado' => [$parametros['cancelado'], PDO::PARAM_INT],
            'nombreLocal' => [$parametros['nombreLocal'], PDO::PARAM_STR]
        ];


        $respuesta_query = Queries::listar($sql, $datos);

        if ($respuesta_query['status'] != 'ok') {
            return Utils::responseJsonError($response, message: $respuesta_query['data']);
        }

        return Utils::responseJsonOk($response, data: $respuesta_query['data']);
    }

    public function listarEncargos(Request $request, Response $response, array $args): Response
    {
        // captura parametros del body
        $conRecogida = false;
        $params = $request->getQueryParams();
        $fechaInicio = "";
        $fechaFin = "";

        $esDeCliente = array_key_exists('cliente', $args);

        // Obtener encargos según el caso (por local o por cliente)
        if (!$esDeCliente) {

            if (is_array($params) && array_key_exists('fecha_recogida', $params)) {
                $fechaInicio = $params['fecha_recogida'] . " 0:00:00";
                $fechaFin = $params['fecha_recogida'] . " 23:59:59";
                $conRecogida = true;
            }
            $localRecogidaEncargo = $args['local'];

            // Definimos el SQL
            $sql = <<<SQL
                SELECT enc.ID as ID_ENCARGO, cli.NOMBRE as NOMBRE_CLIENTE, enc.FECHA_HORA_ESTIMADA_RECOGIDA,
                enc.ENTREGADO, enc.VALORACION_FINAL, enc.A_CUENTA, enc.FINALIZADO, cli.TELEFONO as TELEFONO
                FROM ENCARGO enc
                JOIN ESTABLECIMIENTO est ON enc.LOCAL_RECOGIDA = est.ID
                JOIN CLIENTE cli ON cli.ID = enc.CLIENTE_ID
                WHERE enc.LOCAL_RECOGIDA =:local
                ORDER BY enc.ENTREGADO ASC, enc.FECHA_HORA_ESTIMADA_RECOGIDA ASC
            SQL;

            $paramSQL = [];
            $paramSQL['local'] = [$localRecogidaEncargo, PDO::PARAM_INT];

            if ($conRecogida) {
                $sql .= " AND(          
          (enc.FECHA_HORA_ESTIMADA_RECOGIDA between :fechaInicio and :fechaFin) OR
          (enc.ENTREGADO = 0 AND enc.CANCELADO = 0 and enc.FECHA_HORA_ESTIMADA_RECOGIDA < :fechaInicio)
        )";

                $paramSQL['fechaInicio'] = [$fechaInicio, PDO::PARAM_STR];
                $paramSQL['fechaFin'] = [$fechaFin, PDO::PARAM_STR];
            }
        } else {

            $cliente = $args['cliente'];


            //Devolvemos los encargos del cliente ordenados 
            $sql = <<<SQL
                SELECT enc.ID as ID_ENCARGO, cli.NOMBRE as NOMBRE_CLIENTE, enc.LOCAL_RECOGIDA, enc.ENTREGADO, enc.VALORACION_FINAL, 
                enc.FECHA_HORA_ESTIMADA_RECOGIDA, enc.A_CUENTA, enc.CANCELADO, enc.FINALIZADO, cli.TELEFONO as TELEFONO
                FROM ENCARGO enc
                JOIN CLIENTE cli ON cli.ID = enc.CLIENTE_ID
                WHERE cli.ID = :id 
                ORDER BY enc.FECHA_HORA_ESTIMADA_RECOGIDA DESC 
            SQL;

            $paramSQL = [];
            $paramSQL['id'] = [$cliente, PDO::PARAM_INT];
        }
        $resp = Queries::listar($sql, $paramSQL);

        if ($resp['status'] != 'ok') {
            return Utils::responseJsonError($response, 'Error');
        }

        return Utils::responseJsonOk($response, $resp['data']);
    }

    public function leer(Request $request, Response $response, array $args): Response
    {

        $data = [
            'ID_ENCARGO' => $request->getAttribute('paramEncargoId'),
            'LOCAL_CREACION' => $request->getAttribute('paramEncargoLocalCreacion'),
            'LOCAL_RECOGIDA_ID' => $request->getAttribute('paramEncargoLocalRecogidaId'),
            'LOCAL_RECOGIDA_NOMBRE' => $request->getAttribute('paramEncargoLocalRecogidaNombre'),
            'ID_CLIENTE' => $request->getAttribute('paramEncargoClienteId'),
            'NOMBRE_CLIENTE' => $request->getAttribute('paramEncargoNombreCliente'),
            'TELEFONO_CLIENTE' => $request->getAttribute('paramEncargoTelfCliente'),
            'FECHA_HORA_ESTIMADA_RECOGIDA' => $request->getAttribute('paramEncargoFechaEstimadaRecogida'),
            'FECHA_HORA_RECOGIDA' => $request->getAttribute('paramEncargoFechaRecogida'),
            'FINALIZADO' => $request->getAttribute('paramEncargoFinalizado'),
            'ENTREGADO' => $request->getAttribute('paramEncargoEntregado'),
            'CANCELADO' => $request->getAttribute('paramEncargoCancelado'),
            'A_CUENTA' => $request->getAttribute('paramEncargoACuenta'),
            'NUM_VELAS' => $request->getAttribute('paramEncargoNumVelas'),
            'VALORACION_INICIAL' => $request->getAttribute('paramEncargoValoracionInicial'),
            'VALORACION_FINAL' => $request->getAttribute('paramEncargoValoracionFinal'),
            'DESCRIPCION' => $request->getAttribute('paramEncargoDescripcion'),
            'NOTAS' => $request->getAttribute('paramEncargoNotas'),
            'TEXTO_PERSO' => $request->getAttribute('paramEncargoTextoPerso'),
        ];

        return Utils::responseJsonOk($response, data: $data);
    }

    public function crear(Request $request, Response $response, array $args): Response
    {
        // Compruebo que vengan los parámetros obligatorios
        $mensaje = Utils::requiredParams(['LOCAL_CREACION', 'LOCAL_RECOGIDA', 'CLIENTE_ID', 'FECHA_HORA_ESTIMADA_RECOGIDA', 'A_CUENTA', 'NUM_VELAS', 'TEXTO_PERSO', 'VALORACION_INICIAL', 'VALORACION_FINAL', 'DESCRIPCION', 'NOTAS'], $request);

        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        $params = $request->getParsedBody();

        // Definimos el SQL
        $sql = <<<SQL
            INSERT INTO ENCARGO(LOCAL_CREACION,LOCAL_RECOGIDA,FECHA_CREACION,CLIENTE_ID,FECHA_HORA_ESTIMADA_RECOGIDA,FECHA_HORA_RECOGIDA,IMPRESO, FINALIZADO, ENTREGADO, CANCELADO, A_CUENTA, NUM_VELAS,TEXTO_PERSO, VALORACION_INICIAL,VALORACION_FINAL,DESCRIPCION,NOTAS)
            VALUES (:localc, :localr, NOW(), :cliente,:fecha,'0000-00-00 00:00:00', 0, 0, 0, 0, :cuenta, :velas, :texto, :valoracioni, :valoracionf, :descripcion, :notas)
        SQL;

        $resp = Queries::crear(sql: $sql, datos: [
            'localc' => [$params['LOCAL_CREACION'], PDO::PARAM_INT],
            'localr' => [$params['LOCAL_RECOGIDA'], PDO::PARAM_INT],
            'cliente' => [$params['CLIENTE_ID'], PDO::PARAM_INT],
            'fecha' => [$params['FECHA_HORA_ESTIMADA_RECOGIDA'], PDO::PARAM_STR],
            'cuenta' => [$params['A_CUENTA'], PDO::PARAM_INT],
            'velas' => [$params['NUM_VELAS'], PDO::PARAM_INT],
            'texto' => [$params['TEXTO_PERSO'], PDO::PARAM_STR],
            'valoracioni' => [$params['VALORACION_INICIAL'], PDO::PARAM_INT],
            'valoracionf' => [$params['VALORACION_FINAL'], PDO::PARAM_INT],
            'descripcion' => [$params['DESCRIPCION'], PDO::PARAM_STR],
            'notas' => [$params['NOTAS'], PDO::PARAM_STR],
        ]);

        // Si la consulta falla, mostramos el error
        if ($resp['status'] != 'ok') {
            return Utils::responseJsonError($response, $resp['data']);
        }

        // Consulta para obtener el encargo recién insertado
        $sqlSelect = <<<SQL
            SELECT ID as ID_ENCARGO, LOCAL_CREACION, LOCAL_RECOGIDA, CLIENTE_ID, 
                   FECHA_HORA_ESTIMADA_RECOGIDA as FECHA_ESTIMADA, FECHA_HORA_RECOGIDA, 
                   FINALIZADO, ENTREGADO, CANCELADO, A_CUENTA, NUM_VELAS, TEXTO_PERSO, 
                   VALORACION_INICIAL, VALORACION_FINAL, DESCRIPCION, NOTAS
            FROM ENCARGO 
            WHERE ID = LAST_INSERT_ID()
        SQL;

        $encargoResp = Queries::leer(sql: $sqlSelect, datos: []);

        // Si la consulta falla, devolvemos al menos la confirmación básica
        if ($encargoResp['status'] != 'ok' || !$encargoResp['data']) {
            $encargoData = [
                'mensaje' => 'Encargo creado correctamente',
                'ID_ENCARGO' => $resp['data'] // Asumiendo que resp['data'] contiene el ID generado
            ];

            // Ponemos los datos en un array
            return Utils::responseJsonOk($response, [$encargoData]);
        }

        // Devolvemos un array con la información del encargo
        return Utils::responseJsonOk($response, [$encargoResp['data']]);
    }

    public function editar(Request $request, Response $response, array $args): Response
    {
        $sql = <<<SQL
            UPDATE ENCARGO enc
            JOIN CLIENTE cli ON cli.ID = enc.CLIENTE_ID
            SET cli.NOMBRE = :nombre_cliente, cli.TELEFONO = :telefono_cliente, enc.LOCAL_RECOGIDA = :local_recogida, enc.FECHA_HORA_ESTIMADA_RECOGIDA = :fecha_recogida,
            enc.VALORACION_FINAL = :val_final, enc.NUM_VELAS = :num_velas, enc.DESCRIPCION = :descripcion, enc.NOTAS = :notas, enc.TEXTO_PERSO = :texto_personalizado
            WHERE enc.ID = :id;
        SQL;

        $id_encargo = $args['id'];

        $datos_body = $request->getParsedBody();

        $datos_sql = [
            'nombre_cliente' => [$request->getAttribute('paramEncargoNombreCliente'), PDO::PARAM_STR],
            'telefono_cliente' => [$request->getAttribute('paramEncargoTelfCliente'), PDO::PARAM_STR],
            'local_recogida' => [$request->getAttribute('paramEncargoLocalRecogida'), PDO::PARAM_INT],
            'fecha_recogida' => [$request->getAttribute('paramEncargoFechaRecogida'), PDO::PARAM_STR],
            'val_final' => [$request->getAttribute('paramEncargoValoracionFinal'), PDO::PARAM_STR],
            'num_velas' => [$request->getAttribute('paramEncargoNumVelas'), PDO::PARAM_INT],
            'descripcion' => [$request->getAttribute('paramEncargoDescripcion'), PDO::PARAM_STR],
            'notas' => [$request->getAttribute('paramEncargoNotas'), PDO::PARAM_STR],
            'texto_personalizado' => [$request->getAttribute('paramEncargoTextoPerso'), PDO::PARAM_STR],
            'id' => [$id_encargo, PDO::PARAM_INT],
        ];

        $respuesta_query = Queries::actualizar($sql, datos: Utils::compareAndParseData($datos_sql, $datos_body));

        if ($respuesta_query['status'] != 'ok') {
            return Utils::responseJsonError($response, message: $respuesta_query['data']);
        }

        return Utils::responseJsonOk($response, data: $respuesta_query['data']);
    }

    public function borrar(Request $request, Response $response, array $args): Response
    {
        $sql = <<<SQL
            UPDATE ENCARGO enc
            SET enc.CANCELADO = :cancelado
            WHERE enc.ID = :id;
        SQL;

        $datos = [
            'cancelado' => [1, PDO::PARAM_INT],
            'id' => [$args['id'], PDO::PARAM_INT],
        ];

        $respuesta_query = Queries::actualizar($sql, $datos);

        if ($respuesta_query['status'] != 'ok') {
            return Utils::responseJsonError($response, message: $respuesta_query['data']);
        }

        return Utils::responseJsonOk($response, data: $respuesta_query['data']);
    }

    public function finalizar(Request $request, Response $response, array $args): Response
    {
        $sql = <<<SQL
            UPDATE ENCARGO enc
            SET enc.FINALIZADO = :finalizado, enc.VALORACION_FINAL = :valoracion_final
            WHERE enc.ID = :id;
        SQL;

        $datos = [
            'finalizado' => [1, PDO::PARAM_INT],
            'valoracion_final' => [$request->getParsedBody()['valoracion_final'], PDO::PARAM_STR],
            'id' => [$args['id'], PDO::PARAM_INT],
        ];

        $respuesta_query = Queries::actualizar($sql, $datos);

        if ($respuesta_query['status'] != 'ok') {
            return Utils::responseJsonError($response, message: $respuesta_query['data']);
        }

        return Utils::responseJsonOk($response, data: $respuesta_query['data']);
    }

    public function entregar(Request $request, Response $response, array $args): Response
    {
        
        $idEncargo = intval($args['id']);
        $params = $request->getParsedBody();

        // Verificamos si se proporcionó la fecha de recogida
        if (!isset($params['FECHA_HORA_RECOGIDA'])) {
            return Utils::responseJsonError($response, 'FECHA_HORA_RECOGIDA es requerida');
        }

        // Actualizamos el estado a entregado y la fecha de recogida
        $sql = <<<SQL
            UPDATE ENCARGO SET
            ENTREGADO = 1,
            FECHA_HORA_RECOGIDA = :fecha_recogida
            WHERE ID = :id
        SQL;

        $resp = Queries::actualizar(sql: $sql, datos: [
            'id' => [$idEncargo, PDO::PARAM_INT],
            'fecha_recogida' => [$params['FECHA_HORA_RECOGIDA'], PDO::PARAM_STR]
        ]);

        // Si la consulta falla, mostramos el error
        if ($resp['status'] != 'ok') {
            return Utils::responseJsonError($response, $resp['data']);
        }

        // Consulta para obtener los datos actualizados del encargo
        $sqlSelect = <<<SQL
            SELECT 
              ID as ID_ENCARGO,
              LOCAL_CREACION,
              LOCAL_RECOGIDA,
              CLIENTE_ID,
              FECHA_HORA_ESTIMADA_RECOGIDA as FECHA_ESTIMADA,
              FECHA_HORA_RECOGIDA,
              FINALIZADO,
              ENTREGADO,
              CANCELADO,
              A_CUENTA,
              NUM_VELAS,
              TEXTO_PERSO,
              VALORACION_INICIAL,
              VALORACION_FINAL,
              DESCRIPCION,
              NOTAS
            FROM ENCARGO 
            WHERE ID = :id
        SQL;

        $encargoResp = Queries::leer(sql: $sqlSelect, datos: [
            'id' => [$idEncargo, PDO::PARAM_INT],
        ]);

        // Si hay error al obtener los datos actualizados
        if ($encargoResp['status'] != 'ok' || !$encargoResp['data']) {
            return Utils::responseJsonError($response, 'Error al obtener los datos actualizados del encargo');
        }

        // Devolvemos un array con la información actualizada del encargo
        return Utils::responseJsonOk($response, [$encargoResp['data']]);
    }

    public function imprimir(Request $request, Response $response, array $args): Response
    {
        $sql = <<<SQL
            UPDATE ENCARGO enc
            SET enc.IMPRESO = :impreso
            WHERE enc.ID = :id;
        SQL;

        $datos = [
            'impreso' => [1, PDO::PARAM_INT],
            'id' => [$args['id'], PDO::PARAM_INT],
        ];

        $respuesta_query = Queries::actualizar($sql, $datos);

        if ($respuesta_query['status'] != 'ok') {
            return Utils::responseJsonError($response, message: $respuesta_query['data']);
        }

        return Utils::responseJsonOk($response, data: $respuesta_query['data']);
    }
}
