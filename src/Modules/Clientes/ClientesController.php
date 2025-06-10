<?php

namespace App\Modules\Clientes;

error_reporting(0);

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Helpers\Queries;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ClientesController extends Controller
{
  public function listar(Request $request, Response $response, array $args): Response
  {
    // Definimos el SQL
    $sql = <<<SQL
      SELECT c.ID,  c.TELEFONO, c.NOMBRE FROM CLIENTE c 
      SQL;


    $resp = Queries::listar(sql: $sql, datos: []);



    // Si la consulta falla, mostramos el error
    if ($resp['status'] != 'ok') {
      return Utils::responseJsonError($response, $resp['data']);
    }

    return Utils::responseJsonOk($response, $resp['data']);
  }

  public function crear(Request $request, Response $response, array $args): Response
  {
    // Compruebo que vengan los parámetros obligatorios
    $mensaje = Utils::requiredParams(['TELEFONO', 'NOMBRE'], $request);
    if ($mensaje != '') {
      return Utils::responseJsonError($response, $mensaje);
    }

    $params = $request->getParsedBody();

    // Definimos el SQL
    $sql = <<<SQL
    INSERT INTO CLIENTE(telefono, nombre) VALUES(:telefono, :nombre)
    SQL;

    $resp = Queries::crear(sql: $sql, datos: [
      'telefono' => [$params['TELEFONO'], PDO::PARAM_STR],
      'nombre' => [$params['NOMBRE'], PDO::PARAM_STR],
    ]);

    // Si la consulta falla, mostramos el error
    if ($resp['status'] != 'ok') {
      return Utils::responseJsonError($response, $resp['data']);
    }

    // Consulta para obtener el cliente recién insertado
    $sqlSelect = <<<SQL
    SELECT ID, TELEFONO, NOMBRE
    FROM CLIENTE 
    WHERE ID = LAST_INSERT_ID()
    SQL;

    $clienteResp = Queries::leer(sql: $sqlSelect, datos: []);

    // Si la consulta falla, devolvemos al menos la confirmación básica
    if ($clienteResp['status'] != 'ok' || !$clienteResp['data']) {
      $clienteData = [
        'mensaje' => 'Recurso creado correctamente',
        'ID' => $resp['data'], // Asumiendo que resp['data'] contiene el ID generado
        'TELEFONO' => $params['TELEFONO'],
        'NOMBRE' => $params['NOMBRE']
      ];

      // Ponemos los datos en un array
      return Utils::responseJsonOk($response, [$clienteData]);
    }

    // Devolvemos un array con la información del cliente
    return Utils::responseJsonOk($response, [$clienteResp['data']]);
  }
  public function editar(Request $request, Response $response, array $args): Response
  {
    // obtener del middleware los datos del cliente
    $nombre = $request->getAttribute('paramClienteNombre');
    $telefono = $request->getAttribute('paramClienteTelefono');

    // captura parametros del body
    $params = $request->getParsedBody();
    if (array_key_exists('NOMBRE', $params)) {
      $nombre = $params['NOMBRE'];
    }
    if (array_key_exists('TELEFONO', $params)) {
      $telefono = $params['TELEFONO'];
    }

    // Definir la consulta SQL
    $sql = <<<SQL
        UPDATE CLIENTE set NOMBRE= :nombre, TELEFONO = :telefono  
        WHERE id = :id
      SQL;

    $idCliente = intval($args['cliente']);

    $resp = Queries::actualizar(sql: $sql, datos: [
      'id' => [$idCliente, PDO::PARAM_INT],
      'nombre' => [$nombre, PDO::PARAM_STR],
      'telefono' => [$telefono, PDO::PARAM_STR],
    ]);


    // Si la consulta falla, mostramos el error
    if ($resp['status'] != 'ok') {
      return Utils::responseJsonError($response, $resp['data']);
    }
    

   
      $clienteData = [
        'ID' => $idCliente,
        'NOMBRE' => $nombre,
        'TELEFONO' => $telefono
      ];

      // Ponemos los datos en un array
      return Utils::responseJsonOk($response, $clienteData);
   
  }

  public function leer(Request $request, Response $response, array $args): Response
  {

    // obtener del middleware los datos del cliente
    $idCliente = intval($args['cliente']);
    $nombre = $request->getAttribute('paramClienteNombre');
    $telefono = $request->getAttribute('paramClienteTelefono');

    $datos = [];
    $datos['ID'] = $idCliente;
    $datos['NOMBRE'] = $nombre;
    $datos['TELEFONO'] = $telefono;

    // $datos = ['id' => $idCliente, 'nombre' => $nombre, 'telefono' => $telefono];

    return Utils::responseJsonOk($response, $datos);
  }
}
