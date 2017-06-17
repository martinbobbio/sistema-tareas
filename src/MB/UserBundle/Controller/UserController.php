<?php

namespace MB\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function indexAction()
    {
        return new Response("Bienvenido al modulo de Usuarios!");
    }
    
    public function articlesAction($page)
    {
        return new Response("Este es el articulo " .$page);
    }
}
