<?php

namespace App\Helpers;

use PDO;
use PDOException;
use App\Helpers\Database;


class Queries
{

    //**************************************************************************
    // ATRIBUTOS                                                               *
    //**************************************************************************
    public static $db_config = [
        'db_host' => DB_HOST,
        'db_name' => DB_NAME,
        'db_user' => DB_USER,
        'db_pass' => DB_PASS
    ];


    //**************************************************************************
    // CONSTRUCTOR                                                             *
    //**************************************************************************
    public function __construct()
    {
    }

    //**************************************************************************
    // METODOS DE EJECUCIÓN DE CONSULTAS                                       *
    //**************************************************************************

    //TODO: Ver como pasar el DI/container a los métodos

    public static function leer(string $sql, array $datos): array
    {
        try {
            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => '', );

            // Preparamos el SQL                                                                                                                                                                                                                                                                                  
            $query = Database::instance()->prepare($sql);
            // Enlazamos los parámetros            
            // obtenemos y devolvemos parametros que no vienen por get y post            
            foreach ($datos as $clave => $valor) {
                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }
            // Ejecutamos la sentencia SQL
            $query->execute();
            // Obtenemos los datos y los guardamos en el array $respuesta
            $respuesta['data'] = $query->fetch(PDO::FETCH_ASSOC);
            // Devolvemos los datos
            return $respuesta;
        } catch (PDOException $e) {
            return ['status' => 'error', 'data' => $e->getMessage()];
        }
    }


    //**************************************************************************
    // METODO LISTADO                                                          *
    //**************************************************************************
    public static function listar(string $sql, array $datos): array
    {
        try {

            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => '');

            // Preparamos el SQL
            $query = Database::instance()->prepare($sql);
            // Enlazamos los parámetros                        
            foreach ($datos as $clave => $valor) {
                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }
            // Ejecutamos la sentencia SQL
            $query->execute();
            // Obtenemos los datos y los guardamos en el array $respuesta
            $respuesta['data'] = $query->fetchAll(PDO::FETCH_ASSOC);
            // Devolvemos los datos
            return $respuesta;
        } catch (PDOException $e) {
            return ['status' => 'error', 'data' => $e->getMessage()];
        }
    }


    //**************************************************************************
    // METODO INSERTAR                                                         *
    //**************************************************************************
    public static function crear(string $sql, array $datos): array
    {
        try {
            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => 'Operación realizada correctamente');
            // Creamos un instancia de la base de datos
            $db = Database::instance();
            // Preparamos el SQL
            $query = $db->prepare($sql);
            // Enlazamos los parámetros
            foreach ($datos as $clave => $valor) {
                
                /*
                Explicacion del codigo:
                $clave = nombre del parametro en el SQL
                $valor[0] = valor del parametro
                $valor[1] = tipo de dato del parametro
                */

                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }
            // Ejecutamos la sentencia SQL
            $query->execute();
            // Devolvemos los datos
            return $respuesta;
        } catch (PDOException $e) {
            return ['status' => 'error', 'data' => $e->getMessage()];
        }
    }

    //**************************************************************************
    // METODO ACTUALIZAR                                                       *
    //**************************************************************************
    public static function actualizar(string $sql, array $datos): array
    {
        try {
            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => 'Operación realizada correctamente');
            // Preparamos el SQL
            $query = Database::instance()->prepare($sql);
            // Enlazamos los parámetros                        
            foreach ($datos as $clave => $valor) {
                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }
            // Ejecutamos la sentencia SQL
            $query->execute();
            // Devolvemos los datos
            return $respuesta;
        } catch (PDOException $e) {
            return ['status' => 'error', 'data' => $e->getMessage()];
        }
    }

    //**************************************************************************
    // METODO ELIMINAR                                                         *
    //**************************************************************************
    public static function borrar(string $sql, array $datos): array
    {
        try {
            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => 'Operación realizada correctamente');
            // Preparamos el SQL
            $query = Database::instance()->prepare($sql);
            // Enlazamos los parámetros                        
            foreach ($datos as $clave => $valor) {
                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }
            // Ejecutamos la sentencia SQL
            $query->execute();
            // Devolvemos los datos
            return $respuesta;
        } catch (PDOException $e) {
            return ['status' => 'error', 'data' => $e->getMessage()];
        }
    }

}
