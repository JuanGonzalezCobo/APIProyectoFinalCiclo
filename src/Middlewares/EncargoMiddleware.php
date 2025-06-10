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

class EncargoMiddleware
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
            SELECT  enc.ID as ID_ENCARGO, est.ID as LOCAL_RECOGIDA, cli.NOMBRE as NOMBRE_CLIENTE, cli.TELEFONO as TELEFONO_CLIENTE , enc.FECHA_HORA_ESTIMADA_RECOGIDA as FECHA_RECOGIDA, 
                enc.NUM_VELAS as NUM_VELAS, enc.VALORACION_FINAL as VALORACION_FINAL, enc.DESCRIPCION as DESCRIPCION, enc.NOTAS as NOTAS, enc.TEXTO_PERSO as TEXTO_PERSO
            FROM ENCARGO enc
            JOIN CLIENTE cli ON cli.ID = enc.CLIENTE_ID
            JOIN ESTABLECIMIENTO est ON est.ID = enc.LOCAL_RECOGIDA
            WHERE enc.ID = :id;
        SQL;

            $repuesta_sql = Queries::leer($sql, [
                'id' => [$id, PDO::PARAM_INT],
            ]);

            if ($repuesta_sql['status'] != 'ok' || !$repuesta_sql['data']) {
                $response = $this->responseFactory->createResponse();
                return Utils::responseJsonError($response, "Local con '$id' no encontrado");
            }

            $request = $request->withAttribute('paramEncargoLocalRecogida', $repuesta_sql['data']['LOCAL_RECOGIDA']);
            $request = $request->withAttribute('paramEncargoNombreCliente', $repuesta_sql['data']['NOMBRE_CLIENTE']);
            $request = $request->withAttribute('paramEncargoTelfCliente', $repuesta_sql['data']['TELEFONO_CLIENTE']);
            $request = $request->withAttribute('paramEncargoFechaRecogida', $repuesta_sql['data']['FECHA_RECOGIDA']);
            $request = $request->withAttribute('paramEncargoNumVelas', $repuesta_sql['data']['NUM_VELAS']);
            $request = $request->withAttribute('paramEncargoValoracionFinal', $repuesta_sql['data']['VALORACION_FINAL']);
            $request = $request->withAttribute('paramEncargoDescripcion', $repuesta_sql['data']['DESCRIPCION']);
            $request = $request->withAttribute('paramEncargoNotas', $repuesta_sql['data']['NOTAS']);
            $request = $request->withAttribute('paramEncargoTextoPerso', $repuesta_sql['data']['TEXTO_PERSO']);
        }

        return $handler->handle($request);
    }
}
