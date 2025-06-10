<?php

namespace App\Middlewares;

use PDO;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Firebase\JWT\ExpiredException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;

error_reporting(0);

class TokenMiddleware
{

    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }


    public function __invoke(Request $request, RequestHandler $handler): Response
    {

        $tokenHeader = $request->getHeaderLine('Authorization');

        //** Verificar si el token está presente y tiene el formato correcto, si es correcto, se guarda en $matches[1]

        if (!$tokenHeader || !preg_match('/Bearer\s(\S+)/', $tokenHeader, $matches)) {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, "Token no proporcionado");
        }

        $tokenAuth = $matches[1];

        //** Verificar si el token existe en la base de datos
        $sql = <<<SQL
            SELECT COUNT(est.NOMBRE) as EXISTE_USUARIO
            FROM ESTABLECIMIENTO est
            WHERE est.TOKEN = :token AND est.BORRADO = 0
        SQL;


        $respuesta_query = Queries::leer($sql, [
            'token' => [$tokenAuth, PDO::PARAM_STR]
        ]);


        if ($respuesta_query['status'] != 'ok' || $respuesta_query['data']['EXISTE_USUARIO'] == 0) {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, 'Token no valido o no existe');
        }


        try {
            //Decodificar el token
            $tokenDecoded = JWT::decode($tokenAuth, SECRET_KEY_TOKEN_ACCESS, ['HS256']);

            //** Verificar si el token es invalido
            if ($tokenDecoded->inv) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, "Token invalidado");
            }

            $request = $request->withAttribute('paramAuthToken', $tokenDecoded);
            
        } catch (ExpiredException $e) {

            //** Verificar si el token ha expirado

            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, "Token expirado");

        } catch (\Exception $e) {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, $e);
        }

        return $handler->handle($request);
    }
}
