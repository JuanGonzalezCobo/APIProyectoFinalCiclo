<?php

namespace App\Modules\Imagenes;

error_reporting(0);

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use PDO;


//ruta de la carpeta :__DIR__ . "/../../../../images/"
//$archivo = fopen(__DIR__ . "/../../../images/".$id_pedido.".txt", "w");

class ImagenesController extends Controller
{
    //subir(crear)
    public function subir(Request $request, Response $response, array $args)
    {
        $mensaje = Utils::requiredParams(['IMAGEN', 'NOMBRE', 'EXTENSION'], $request);
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        $id_pedido = $args['id'];
        $params = $request->getParsedBody();
        $imagen = base64_decode($params['IMAGEN']);
        $numero = $params['NOMBRE'];
        $extension = $params['EXTENSION'];

        //comprueba que la carpeta no exista y si no existe, crearla
        if (!is_dir(__DIR__ . "/../../../../images/" . $id_pedido)) {
            mkdir(__DIR__ . "/../../../../images/" . $id_pedido);
        }

        //comprueba que el archivo no exista y si existe, devolver un mensaje de error
        if (file_exists(__DIR__ . "/../../../../images/" . $id_pedido . "/" . $numero . "." . $extension)) {
            return Utils::responseJson($response, [
                'status' => 'error',
                'data' => 'Archivo duplicado'
            ]);
        }

        // si no existe, crear el archivo
        $archivo = fopen(__DIR__ . "/../../../../images/" . $id_pedido . "/" . $numero . "." . $extension, "w");
        fwrite($archivo, $imagen);
        fclose($archivo);

        return Utils::responseJson($response, [
            'status' => 'ok',
            'data' => 'Imagen subida con éxito'
        ]);
    }

    //leer
    public function leer(Request $request, Response $response, array $args)
    {
        $id_pedido = $args['id'];
        $numero = $args['numero'];

        //primero vemos si hay imagenes en ese pedido
        if (!is_dir(__DIR__ . "/../../../../images/" . $id_pedido)) {
            return Utils::responseJson($response, "No hay imagenes en este pedido");
        }

        //listar los archivos
        $archivos = scandir(__DIR__ . "/../../../../images/" . $id_pedido);

        //seleccionar el archivo en la posicion numero
        $archivo = $archivos[$numero + 1];

        //teniendo el archivo, leemos el contenido
        $archivo = file_get_contents(__DIR__ . "/../../../../images/" . $id_pedido . "/" . $archivo);

        //convertimos el archivo a base64
        $archivo = base64_encode($archivo);

        //devolvemos el archivo
        return Utils::responseJson($response, ['imagen' => $archivo]);
    }

    //listar
    public function listar(Request $request, Response $response, array $args)
    {
        $id_pedido = $args['id'];
        $params = $request->getParsedBody();

        //primero vemos si hay imagenes en ese pedido
        if (!is_dir(__DIR__ . "/../../../../images/" . $id_pedido)) {
            return Utils::responseJson($response, [
                'status' => 'ok',
                'data' => []
            ]);
        }

        //listar los archivos
        $archivos = scandir(__DIR__ . "/../../../../images/" . $id_pedido);
        
        // Filtrar los archivos . y ..
        $archivos = array_values(array_diff($archivos, ['.', '..']));
        
        $resultado = [];
        foreach ($archivos as $index => $archivo) {
            $contenido = file_get_contents(__DIR__ . "/../../../../images/" . $id_pedido . "/" . $archivo);
            $resultado[] = [
                'NOMBRE' => pathinfo($archivo, PATHINFO_FILENAME),
                'EXTENSION' => pathinfo($archivo, PATHINFO_EXTENSION),
                'IMAGEN' => base64_encode($contenido),
            ];
        }

        //devolvemos los archivos
        return Utils::responseJson($response, [
            'status' => 'ok',
            'data' => $resultado
        ]);
    }

    //borrar
    public function borrar(Request $request, Response $response, array $args)
    {
        $id = $args['id'];
        $numero = $args['numero'];

        $directorio = __DIR__ . "/../../../../images/$id";

        // Verificar si el directorio existe
        if (!is_dir($directorio)) {
            return Utils::responseJson($response, [
                'status' => 'error',
                'data' => "No existe la carpeta del pedido $id"
            ]);
        }

        $archivos = scandir($directorio);

        // Verifica si el índice es válido
        if (!isset($archivos[$numero + 1])) {
            return Utils::responseJson($response, [
                'status' => 'error',
                'data' => "No se encontró la imagen número $numero en el pedido $id"
            ]);
        }

        $archivoAEliminar = $archivos[$numero + 1];
        $rutaArchivo = "$directorio/$archivoAEliminar";

        // Borrar el archivo
        try {
            unlink($rutaArchivo);
        } catch (\Throwable $th) {
            return Utils::responseJson($response, [
               'status' => 'error',
                'data' => "Error al borrar la imagen $archivoAEliminar"
            ]);
        }

        // Obtener archivos restantes
        $archivosRestantes = array_values(array_diff(scandir($directorio), ['.', '..']));

        if (empty($archivosRestantes)) {
            rmdir($directorio);
            return Utils::responseJson($response, [
                'status' => 'ok',
                'data' => 'Archivo y carpeta borrados correctamente'
            ]);
        }

        // Reordenar los archivos sin huecos
        $contador = 1;
        foreach ($archivosRestantes as $archivo) {
            $extension = pathinfo($archivo, PATHINFO_EXTENSION);
            $nuevoNombre = "$contador.$extension";
            if ($archivo !== $nuevoNombre) {
                rename("$directorio/$archivo", "$directorio/$nuevoNombre");
            }
            $contador++;
        }

        return Utils::responseJson($response, [
            'status' => 'ok',
            'data' => 'Archivo borrado y archivos renombrados correctamente'
        ]);
    }
}
