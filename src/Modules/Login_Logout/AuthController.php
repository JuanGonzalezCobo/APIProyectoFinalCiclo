<?php

namespace App\Modules\Login_Logout;

error_reporting(0);

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Helpers\Queries;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class AuthController extends Controller
{

    public function login(Request $request, Response $response): Response
    {
        $combrobacionDatosExistentes = Utils::requiredParams(['usuario', 'password', 'tipo'], $request);

        if ($combrobacionDatosExistentes != '') {
            return Utils::responseJsonError($response, message: $combrobacionDatosExistentes);
        }

        $params = $request->getParsedBody();

        $sql = <<<SQL
            SELECT est.ID as ID_USER, est.NOMBRE as NOMBRE_USER, est.PASSWORD as PASSWORD_USER, est.TIPO as TIPO_USER
            FROM ESTABLECIMIENTO est
            WHERE est.NOMBRE = :usuario AND est.BORRADO = 0
        SQL;

        $respuesta_query = Queries::leer($sql, [
            'usuario' => [$params['usuario'], PDO::PARAM_STR]
        ]);

        if ($respuesta_query['status'] != 'ok' || empty($respuesta_query['data'])) {
            return Utils::responseJsonError($response, 'No se ha encontrado el usuario');
        }

        if (!password_verify($params['password'], $respuesta_query['data']['PASSWORD_USER'])) {
            return Utils::responseJsonError($response, message: 'Contraseña incorrecta');
        }
        if ($respuesta_query['data']['TIPO_USER'] != $params['tipo']) {
            return Utils::responseJsonError($response, 'No está accediendo con la aplicación correspondiente');
        }


        $token = Utils::generateToken([
            'ID_USER' => $respuesta_query['data']['ID_USER'],
            'TIPO_USER' => $respuesta_query['data']['TIPO_USER'],
        ]);

        $salvarToken = Utils::saveToken($token, $respuesta_query['data']['ID_USER']);

        if ($salvarToken['status'] != 'ok') {
            return Utils::responseJsonError($response, message: $salvarToken['data']);
        }

        return Utils::responseJsonOk($response, data: $token);
    }



    public function logout(Request $request, Response $response, array $args): Response
    {
        //Tenemos en cuenta que el token lo tenemos en el header y que lo pasamos por el middleware
        $token = $request->getAttribute('paramAuthToken');

        $data = Utils::invalidateToken($token);

        $respuesta_query = Utils::saveToken($data['token'], $data['id_usuario']);

        if ($respuesta_query['status'] != 'ok') {
            return Utils::responseJsonError($response, message: $respuesta_query['data']);
        }

        return Utils::responseJsonOk($response, data: 'Logout realizado correctamente');
    }
}
