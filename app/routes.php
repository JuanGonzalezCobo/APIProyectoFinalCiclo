<?php

use App\Helpers\Utils;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

use App\Modules\Locales\LocalesController;
use App\Modules\Encargos\EncargosController;
use App\Modules\Clientes\ClientesController;
use App\Modules\Login_Logout\AuthController;
use App\Modules\Imagenes\ImagenesController;

use App\Middlewares;

error_reporting(0);

return function (App $app) {


  /********* MODULO DE LOGIN **********/

  //  POST   -> /login      -> Login (se pasan datos en el body)

  $app->post(pattern: '/login', callable: AuthController::class . ':login');

  //  POST   -> /logout     -> Logout

  $app->post(pattern: '/logout', callable: AuthController::class . ':logout')->add(new Middlewares\TokenMiddleware($app->getResponseFactory()));



  /********* MODULO DE LOCALES **********/

  //  GET    -> /locales                -> Lista todos los locales
  //  GET    -> /locales/1/encargos     -> Lista los encargos de un local
  //  GET    -> /locales/1              -> Lee un local
  //  POST   -> /locales                -> Crea un local (se pasan datos en el body)
  //  PUT    -> /locales/1              -> Editamos un local (se pasan datos en el body)
  //  DELETE -> /locales/1              -> Borramos un local

  $app->group(pattern: '/locales', callable: function (RouteCollectorProxy $grupo) use ($app) {
    $grupo->group(pattern: '/{local:' . Utils::$validacion_entero . '}/encargos', callable: function (RouteCollectorProxy $encargo) use ($grupo) {
      $encargo->get('', callable: LocalesController::class . ':listarEncargos');
    });
    // listar
    $grupo->get(pattern: '', callable: LocalesController::class . ':listar');
    // leer
    $grupo->get(pattern: '/{id:' . Utils::$validacion_entero . '}', callable: LocalesController::class . ':leer');
    //insertar
    $grupo->post(pattern: '', callable: LocalesController::class . ':insertar');
    //editar
    $grupo->put('/{id:' . Utils::$validacion_entero . '}', callable: LocalesController::class . ':editar');
    //borrar
    $grupo->delete('/{id:' . Utils::$validacion_entero . '}', callable: LocalesController::class . ':borrar');
  })->add(new Middlewares\TokenOuterMiddleware($app->getResponseFactory()))
    // TOKENS DE ENTRADA  
    ->add(new Middlewares\LocalMiddleware($app->getResponseFactory()))
    ->add(new Middlewares\TokenMiddleware($app->getResponseFactory()));


  /********* MODULO DE ENCARGOS **********/

  //  GET    -> /encargos                -> Lista todos los encargos
  //  GET    -> /encargosFuturos         -> Lista todos los encargos futuros
  //  GET    -> /encargos/1              -> Lee un encargo
  //  PUT    -> /encargos/1              -> Editamos un encargo (se pasan datos en el body)
  //  POST   -> /encargos                -> Crear un encargo
  //  DELETE -> /encargos/1              -> Borramos/Cancelamos/Anulamos un encargo
  //  PUT    -> /encargos/1/finalizado   -> Finalizamos un encargo
  //  PUT    -> /encargos/1/impreso      -> Imprimimos un encargo
  //  PUT    -> /encargos/1/entregado    -> Entregamos un encargo

  $app->group(pattern: '/encargos', callable: function (RouteCollectorProxy $grupo) use ($app) {
    // listar
    $grupo->get(pattern: '', callable: EncargosController::class . ':listar');
    // listarFuturos
    $grupo->get(pattern: 'Futuros', callable: EncargosController::class . ':listarEncargosFuturos');
    // leer
    $grupo->get(pattern: '/{id:' . Utils::$validacion_entero . '}', callable: EncargosController::class . ':leer');
    //editar
    $grupo->put('/{id:' . Utils::$validacion_entero . '}', callable: EncargosController::class . ':editar');
    //crear
    $grupo->post(pattern: '', callable: EncargosController::class . ':crear');
    //borrar
    $grupo->delete('/{id:' . Utils::$validacion_entero . '}', callable: EncargosController::class . ':borrar');
    //finalizar
    $grupo->put('/{id:' . Utils::$validacion_entero . '}/finalizado', callable: EncargosController::class . ':finalizar');
    //imprimir
    $grupo->put('/{id:' . Utils::$validacion_entero . '}/impreso', callable: EncargosController::class . ':imprimir');
    //entregar
    $grupo->put('/{id:' . Utils::$validacion_entero . '}/entregado', callable: EncargosController::class . ':entregar');
  })->add(new Middlewares\TokenOuterMiddleware($app->getResponseFactory()))
    // TOKENS DE ENTRADA
    ->add(new Middlewares\EncargoMiddleware($app->getResponseFactory()))
    ->add(new Middlewares\TokenMiddleware($app->getResponseFactory()));


  /********* MODULO DE CLIENTES **********/

  //  GET    -> /clientes                -> Lista todos los clientes
  //  GET    -> /clientes/1              -> Lee un cliente
  //  PUT    -> /clientes/1              -> Editamos un cliente (se pasan datos en el body)
  //  POST   -> /clientes/1              -> Crear un cliente
  //  GET    -> /clientes/1/encargos     -> SLista los encargos de un cliente

  $app->group('/clientes', function (RouteCollectorProxy $clientes) use ($app) {
    $clientes->get("", ClientesController::class . ':listar');
    $clientes->get("/{cliente}", ClientesController::class . ':leer');
    $clientes->post("", ClientesController::class . ':crear');
    $clientes->put("/{cliente}", ClientesController::class . ':editar');
    $clientes->group(pattern: '/{cliente}/encargos', callable: function (RouteCollectorProxy $encargos) use ($clientes) {
      $encargos->get("", EncargosController::class . ':listarEncargos');
    });
  })->add(new Middlewares\TokenOuterMiddleware($app->getResponseFactory()))
    // TOKENS DE ENTRADA
    ->add(new Middlewares\ClientesMiddleware($app->getResponseFactory()))
    ->add(new Middlewares\TokenMiddleware($app->getResponseFactory()));


    /********* MODULO DE IMAGENES **********/

    //  POST   -> /imagenes/1              -> Subir una imagen
    //  GET    -> /imagenes/1/1            -> Leer una imagen
    //  DELETE -> /imagenes/1/1            -> Borrar una imagen
    //  PUT    -> /imagenes/1              -> Actualizar las imagenes de un encargo
    //  GET    -> /imagenes/1              -> Listar las imagenes de un encargo
  
  $app->group(pattern: '/imagenes', callable: function (RouteCollectorProxy $imagenes) use ($app) {
    // subir
    $imagenes->post("/{id}", ImagenesController::class. ':subir');
    $imagenes->get("/{id}/{numero}", ImagenesController::class. ':leer');
    $imagenes->delete("/{id}/{numero}", ImagenesController::class. ':borrar');
    $imagenes->put("/{id}", ImagenesController::class. ':actualizar');
    $imagenes->get("/{id}", ImagenesController::class. ':listar');
  });
};
