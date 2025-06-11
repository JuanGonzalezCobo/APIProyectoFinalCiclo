<?php

namespace App\Middlewares;

error_reporting(0);

use PDO;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

class ClientesMiddleware
{
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {

        // obtengo los argumentos de la ruta y comprueba si existe el tipo de app
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $args = $route->getArguments();

        // $caducidad = $request->getAttribute('paramCaducidad');

        // comprobar que exista el argumento {cliente} de la ruta
        if (array_key_exists('cliente', $args)) {

            // Obtengo el id del cliente forzando que sea entero
            $idCliente = intval($args['cliente']);
            // comprobamos que exista el cliente en la base de datos

            // Definimos el SQL
            $sql = <<<SQL
                    SELECT c.TELEFONO, c.NOMBRE
                    FROM CLIENTE c
                    WHERE ID = :id      
                    SQL;
            $datos = Queries::leer($sql, [
                'id' => [$idCliente, PDO::PARAM_INT],
            ]);

            if ($datos['status'] != 'ok' || !$datos['data']) {
                // detener la peticion
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, "El cliente '$idCliente' no encontrado");
            }

            $request = $request->withAttribute('paramClienteNombre', $datos['data']['NOMBRE']);
            $request = $request->withAttribute('paramClienteTelefono', $datos['data']['TELEFONO']);
        }
        return $handler->handle($request);
    }
}
