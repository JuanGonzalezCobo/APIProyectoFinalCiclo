<?php

namespace App\Modules\Locales;

error_reporting(0);

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use PDO;

class LocalesController extends Controller
{
    public function listar(Request $request, Response $response): Response
    {
        $sql = <<<SQL
            SELECT est.ID, est.NOMBRE, est.TIPO, est.BORRADO
            FROM ESTABLECIMIENTO est
            WHERE est.BORRADO = 0;
        SQL;

        $respuesta_query = Queries::listar($sql, []);

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
            if (is_array($params) && array_key_exists('fecha', $params)) {
                $fechaInicio = $params['fecha'] . " 0:00:00";
                $fechaFin = $params['fecha'] . " 23:59:59";
                $conRecogida = true;
            }
            $localRecogidaEncargo = $args['local'];

            // SQL base
            $sql = <<<SQL
                SELECT enc.ID as ID_ENCARGO, cli.NOMBRE as NOMBRE_CLIENTE, enc.FECHA_HORA_ESTIMADA_RECOGIDA,
                enc.ENTREGADO, enc.VALORACION_FINAL, enc.A_CUENTA, enc.FINALIZADO
                FROM ENCARGO enc
                JOIN ESTABLECIMIENTO est ON enc.LOCAL_RECOGIDA = est.ID
                JOIN CLIENTE cli ON cli.ID = enc.CLIENTE_ID
                WHERE enc.LOCAL_RECOGIDA = :local
            SQL;

            if ($conRecogida) {
                $sql .= <<<SQL
                    AND (
                        (enc.FECHA_HORA_ESTIMADA_RECOGIDA BETWEEN :fechaInicio AND :fechaFin)
                        OR (enc.ENTREGADO = 0 AND enc.CANCELADO = 0 AND enc.FECHA_HORA_ESTIMADA_RECOGIDA < :fechaInicio)
                    )
                SQL;
            }

            $sql .= " ORDER BY enc.ENTREGADO ASC, enc.FECHA_HORA_ESTIMADA_RECOGIDA ASC";

            $paramSQL = ['local' => [$localRecogidaEncargo, PDO::PARAM_INT]];

            if ($conRecogida) {
                $paramSQL['fechaInicio'] = [$fechaInicio, PDO::PARAM_STR];
                $paramSQL['fechaFin'] = [$fechaFin, PDO::PARAM_STR];
            }
        } else {

            $cliente = $args['cliente'];


            //Devolvemos los encargos del cliente ordenados 
            $sql = <<<SQL
                SELECT enc.ID as ID_ENCARGO, cli.NOMBRE as NOMBRE_CLIENTE, enc.LOCAL_RECOGIDA, enc.ENTREGADO, enc.VALORACION_FINAL, 
                enc.FECHA_HORA_ESTIMADA_RECOGIDA, enc.A_CUENTA
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
        $sql = <<<SQL
            SELECT est.ID, est.NOMBRE, est.TIPO, est.BORRADO, est.COLOR
            FROM ESTABLECIMIENTO est
            WHERE est.ID = :id;
        SQL;

        $respuesta_query = Queries::leer($sql, ['id' => [$args['id'], PDO::PARAM_INT]]);

        if ($respuesta_query['status'] != 'ok') {
            return Utils::responseJsonError($response, message: $respuesta_query['data']);
        }

        return Utils::responseJsonOk($response, data: $respuesta_query['data']);
    }

    public function insertar(Request $request, Response $response): Response
    {
        //Miramos que tenemos los parámetros necesarios para insertar el local
        $datos = Utils::requiredParams(required: ['nombre', 'password', 'tipo', 'color'], request: $request);

        if ($datos != '') {
            return Utils::responseJsonError($response, message: $datos);
        }

        //Cogemos el body del URL
        //TODO: Mirar esto porque funciona con el getQueryParams() pero no el getParsedBody()
        /**
         * La diferencia es donde se pone la informacion, el getQueryParams, se pone en la
         * parte del URL
         * https://practica.twalmeria.es/juan/locales/?nombre=MiApartamento&tipo=Hogar
         * 
         * En cambio el getParsedBody() se pone en el BODY.
         */



        $params = $request->getParsedBody();

        $sql = <<<SQL
                SELECT est.NOMBRE
                FROM ESTABLECIMIENTO est
            SQL;

        $repuesta_sql = Queries::listar($sql, []);

        if ($repuesta_sql['status'] != 'ok' || !$repuesta_sql['data']) {
            return Utils::responseJsonError($response, 'No se pudo listar');
        }

        foreach ($repuesta_sql['data'] as $key => $value) {
            if ($value['NOMBRE'] == $params['nombre']) {
                return Utils::responseJsonError($response, 'El nombre del local ya existe');
            }
        }

        $sql = <<<SQL
            INSERT INTO ESTABLECIMIENTO(NOMBRE, PASSWORD, TIPO, TOKEN, COLOR)
            VALUES(:nombre, :password , :tipo, :token, :color);
        SQL;

        $respuesta_query = Queries::crear(sql: $sql, datos: [
            'nombre' => [$params['nombre'], PDO::PARAM_STR],
            'password' => [password_hash($params['password'], PASSWORD_DEFAULT), PDO::PARAM_STR],
            'tipo' => [$params['tipo'], PDO::PARAM_STR],
            'token' => [$params['token'], PDO::PARAM_STR],
            'color' => [$params['color'], PDO::PARAM_STR]
        ]);

        if ($respuesta_query['status'] != 'ok') {
            return Utils::responseJsonError($response, $respuesta_query['data']);
        }

        return Utils::responseJsonOk($response, $respuesta_query['data']);
    }

    public function editar(Request $request, Response $response, array $args): Response
    {
        $sql = <<<SQL
            UPDATE ESTABLECIMIENTO
            SET NOMBRE = :nombre, PASSWORD = :password, TIPO = :tipo, BORRADO = :borrado, TOKEN = :token, COLOR = :color
            WHERE ID = :id;
        SQL;


        $id_local = $args['id'];
        $nombre_local = $request->getAttribute('paramLocalNOMBRE');
        $password_local = $request->getAttribute('paramLocalPASSWORD');
        $tipo_local = $request->getAttribute('paramLocalTIPO');
        $borrado_local = $request->getAttribute('paramLocalBORRADO');
        $token_local = $request->getAttribute('paramLocalTOKEN');
        $color_local = $request->getAttribute('paramLocalCOLOR');

        $datos_body = $request->getParsedBody();

        $datos_sql = [
            'nombre' => [$nombre_local, PDO::PARAM_STR],
            'password' => [$password_local, PDO::PARAM_STR],
            'tipo' => [$tipo_local, PDO::PARAM_STR],
            'borrado' => [$borrado_local, PDO::PARAM_INT],
            'token' => [$token_local, PDO::PARAM_STR],
            'id' => [$id_local, PDO::PARAM_INT],
            'color' => [$color_local, PDO::PARAM_STR]
        ];


        $respuesta_query = Queries::actualizar(sql: $sql, datos: Utils::compareAndParseData($datos_sql, $datos_body));

        if ($respuesta_query['status'] != 'ok') {
            return Utils::responseJsonError($response, $respuesta_query['data']);
        }

        return Utils::responseJsonOk($response, $respuesta_query['data']);
    }


    public function borrar(Request $request, Response $response, array $args)
    {
        $sql = <<<SQL
            UPDATE ESTABLECIMIENTO
            SET BORRADO = :borrado
            WHERE ID = :id;
        SQL;

        $respuesta_query = Queries::actualizar($sql, [
            'borrado' => [1, PDO::PARAM_INT],
            'id' => [$args['id'], PDO::PARAM_INT]
        ]);

        if ($respuesta_query['status'] != 'ok') {
            return Utils::responseJsonError($response, $respuesta_query['data']);
        }

        return Utils::responseJsonOk($response, $respuesta_query['data']);
    }
}
