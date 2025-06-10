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

class LocalMiddleware
{
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $args = $route->getArguments();

        if (array_key_exists('id', $args)) {

            $id = intval($args['id']);

            $sql = <<<SQL
                SELECT est.ID, est.NOMBRE, est.PASSWORD, est.TIPO, est.BORRADO, est.TOKEN, est.COLOR
                FROM ESTABLECIMIENTO est
                WHERE est.ID = :id;
            SQL;

            $repuesta_sql = Queries::leer($sql, [
                'id' => [$id, PDO::PARAM_INT],
            ]);

            if ($repuesta_sql['status'] != 'ok' || !$repuesta_sql['data']) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, "Local con '$id' no encontrado");
            }
            
            $request = $request->withAttribute('paramLocalNOMBRE', $repuesta_sql['data']['NOMBRE']);
            $request = $request->withAttribute('paramLocalPASSWORD', $repuesta_sql['data']['PASSWORD']);
            $request = $request->withAttribute('paramLocalTIPO', $repuesta_sql['data']['TIPO']);
            $request = $request->withAttribute('paramLocalBORRADO', $repuesta_sql['data']['BORRADO']);
            $request = $request->withAttribute('paramLocalTOKEN', $repuesta_sql['data']['TOKEN']);
            $request = $request->withAttribute('paramLocalCOLOR', $repuesta_sql['data']['COLOR']);
        }

        return $handler->handle($request);
    }
}
