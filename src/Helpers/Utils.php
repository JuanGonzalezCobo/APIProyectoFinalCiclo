<?php

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use PDO;

class Utils
{

    static $validacion_entero = "[0-9]+";
    static $validacion_hora = "(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d";
    static $validacion_fecha = "\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])";


    /**
     * Function que devuelve un array con el token generado
     *
     * @param array $usuario    Array con los datos del usuario
     * 
     * @return string           El token generado
     */
    public static function generateToken(array $usuario): string
    {
        $ahora = time();

        //CREACION DE LOS PAYLOADS PARA EL TOKEN

        $payloadAccessToken = [
            'iss' => API_URL,
            'aud' => API_URL,
            'iat' => $ahora,
            'nbf' => $ahora,
            'exp' => $ahora + TOKEN_EXPIRATION,
            'inv' => false,
            'id_usuario' => $usuario['ID_USER'],
            'tipo' => $usuario['TIPO_USER']
        ];

        //CREACION DEL TOKEN

        return JWT::encode($payloadAccessToken, SECRET_KEY_TOKEN_ACCESS, 'HS256');
    }

    /**
     * Function que devuelve un array con el token ya invalidado
     *
     * @param object $token    Token a invalidar
     *
     * @return array            Array con el token invalidado
     */
    public static function invalidateToken(object $token): array
    {
        $token->inv = true;

        return ['id_usuario' => $token->id_usuario, 'token' => JWT::encode($token, SECRET_KEY_TOKEN_ACCESS, 'HS256')];
    }


    /**
     * Function que devuelve un array con datos del SQL modificado por los datos del request
     *
     * @param array $array_externo    Array con los datos del SQL
     * @param array $array_interno    Array con los datos del request
     *
     * @return array                  Array con los datos del SQL modificados
     */
    public static function compareAndParseData(array $array_externo, array $array_interno): array
    {
        foreach ($array_externo as $clave_externa => $valor_externo) {
            foreach ($array_interno as $clave_interna => $valor_interno) {
                if ($clave_externa == $clave_interna) {
                    //Vamos a comprobar su el cambio es la contraseña para hashearla
                    if ($clave_externa == 'password') {
                        $valor_interno = password_hash($valor_interno, PASSWORD_DEFAULT);
                        var_dump($valor_interno);
                    }
                    $array_externo[$clave_externa] = [$valor_interno, $valor_externo[1]];
                    break;
                }
            }
        }
        return $array_externo;
    }


    /**
     * Function que devuelve un array con la información de el update del token en la base de datos
     *
     * @param object $token    Token a guardar en la base de datos
     * @param int $id_user     ID del usuario a guardar en la base de datos
     *
     * @return array           Array con la información de el update del token en la base de dato
     */
    public static function saveToken($token, $id_user)
    {
        $sql = <<<SQL
            UPDATE ESTABLECIMIENTO est SET est.TOKEN = :token WHERE est.ID = :id_user
        SQL;

        return Queries::actualizar($sql, [
            'token' => [$token, PDO::PARAM_STR],
            'id_user' => [$id_user, PDO::PARAM_INT]
        ]);
    }



    /**
     * Function que devuelve una respuesta sin status
     * 
     * @param Response $response    Objeto Response a modificar     
     * @param mixed $data           Datos de la respuesta.
     * @param int $code             Código del estado de la respuesta, por defecto `200`
     * 
     * @return Response             Respuesta modificada
     */
    public static function responseJson(Response $response, $data, int $code = 200): Response
    {
        $payload = json_encode($data, JSON_PRETTY_PRINT);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    }


    /**
     * Método que detiene la ejecución si falta algún parámetro
     * @param array $required       Lista de parámetros requeridos
     * @param mixed $request        
     * 
     * @return string               Devuelve mensaje de error si falta algún parámetro
     *                              Si es correcto devuelve cadena vacía
     */
    public static function requiredParams(array $required, mixed $request): string
    {
        $params = array_merge($request->getParsedBody() ?? [], $request->getQueryParams());

        $headers = $request->getHeaders();

        if (in_array('token', $required)) {
            if (!array_key_exists('Authorization', $headers)) {
                return "Autorización requerida";
            } else {
                if (explode(' ', $headers['Authorization'][0])[0] != 'Bearer') {
                    return "Método de autorización inválido";
                }
            }

            $required = array_diff($required, ['token']);
        }

        foreach ($required as $value) {
            if (!isset($params[$value]) || $params[$value] === null) {
                return "Parámetro '$value' requerido";
            }
        }
        return '';
    }


    /**
     * Function que devuelve una respuesta correcta
     * 
     * @param Response $response    Objeto Response a modificar     
     * @param mixed $data           Datos de la respuesta.
     * @param int $code             Código del estado de la respuesta, por defecto `200`
     * 
     * @return Response             Respuesta modificada
     */
    public static function responseJsonOk(Response $response, mixed $data, $caducidad = '', int $code = 200): Response
    {
        $data = ['status' => 'ok', 'data' => $data];
        if ($caducidad != '') {
            $data['expiration'] = $caducidad;
        }

        $payload = json_encode($data);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    }


    /**
     * Function que devuelve una respuesta en caso de error
     * 
     * @param Response $response    Objeto Response a modificar
     * @param string $message       Mensaje de error
     * @param mixed $data           Datos de la respuesta. Por defecto `''`
     * @param int $code             Código del estado de la respuesta, por defecto `401`
     * 
     * @return Response             Respuesta modificada
     */
    public static function responseJsonError(Response $response, string $message, $data = '', $caducidad = '', int $code = 401): Response
    {
        $respuesta = ['status' => 'error', 'data' => $message];
        if ($data != '') {
            $respuesta['data'] = $data;
        }
        if ($caducidad != '') {
            $respuesta['expiration'] = $caducidad;
        }

        $array_message = explode('$$$$$', $message);

        if (sizeof($array_message) > 1) {
            $respuesta['data'] = $array_message[1];
        }

        $payload = json_encode($respuesta, JSON_PRETTY_PRINT);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    }
}
