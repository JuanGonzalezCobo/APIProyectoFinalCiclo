<?php

namespace App\Modules\Login;

error_reporting(0);

use App\Helpers\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginController extends Controller
{
    public function login(Request $request, Response $response, array $args): Response
    {

    }
}