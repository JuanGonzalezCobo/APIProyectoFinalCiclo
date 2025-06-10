<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

require_once __DIR__ . '/config.php';

return function (App $app) {
    // Parse json, form data and xml
    $app->addBodyParsingMiddleware();

    // Add the Slim built-in routing middleware
    $app->addRoutingMiddleware();

    // Generamos un log
    $logger = new Logger('error');
    $logger->pushHandler(new RotatingFileHandler('error.log'));

    // Definimos el manejador de error por defecto
    $customErrorHandler = function (ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) use ($app, $logger) {

        // Si hay 
        if ($logger) {
            $logger->error($exception->getMessage());
        }

        $token = implode($request->getHeader('Authorization'));
        $payload = [
            'status' => 'error',
            'data' => '',
            'error' => $exception->getMessage(),
        ];
        if (str_starts_with($token, 'Bearer ')) {
            $payload['expiration'] = '';
        }
        $response = $app->getResponseFactory()->createResponse();
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $response;
    };

    // Handle exceptions
    $errorMiddleware = $app->addErrorMiddleware(MIDDLEWARE_DISPLAY_ERROR_DETAILS, MIDDLEWARE_LOG_ERROR, MIDDLEWARE_LOG_ERROR, $logger);
    $errorMiddleware->setDefaultErrorHandler($customErrorHandler);
};
