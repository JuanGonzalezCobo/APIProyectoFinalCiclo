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

class TablaMiddleware
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

        if (array_key_exists('tabla', $args)) {

            // Obtengo el id del tabla de la ruta
            $tabla = intval($args['tabla']);

            // comprobamos que exista el local en la base de datos

            // Definimos el SQL
            $sql = <<<SQL
                    SELECT t.nombre, t.fecha 
                    FROM tabla t
                    WHERE id = :id      
                    SQL;
            $datos = Queries::leer($sql, [
                'id' => [$tabla, PDO::PARAM_INT],
            ]);

            if ($datos['status'] != 'ok' || !$datos['data']) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, "Tabla '$tabla' no encontrado");
            }

            $request = $request->withAttribute('paramTablaNombre', $datos['data']['nombre']);
            $request = $request->withAttribute('paramTablaFecha', $datos['data']['fecha']);
        }
        return $handler->handle($request);
    }
}
