<?php

namespace App\Middlewares;


use App\Helpers\Utils;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;


class TokenOuterMiddleware
{
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface
    {

        $response = $handler->handle($request);
        $body = (string) $response->getBody();

        $tokenEntrada = $request->getAttribute('paramAuthToken');

        $tokenSalida = Utils::generateToken(['ID_USER' => $tokenEntrada->id_usuario, 'TIPO_USER' => $tokenEntrada->tipo]);


        $data = json_decode($body, true);
        $data['token'] = $tokenSalida;


        //Guardar el token en la base de datos
        $resultado_query = Utils::saveToken($tokenSalida, $tokenEntrada->id_usuario);

        if ($resultado_query['status'] != 'ok') {
            $response = $this->responseFactory->createResponse();
            return Utils::responseJsonError($response, message: $resultado_query['data']);
        }


        return new Response(200, [], json_encode($data));
    }
}
