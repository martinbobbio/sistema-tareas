<?php

namespace MB\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function indexAction()
    {
        $con = $this->getDoctrine()->getManager();
        $users = $con->getRepository('MBUserBundle:User')->findAll();
        
        /*
        $res = 'Lista de usuarios: <br />';
        foreach($users as $user){
            $res .= 'Usuario: '.$user->getUsername().'<br />';
        }
        return new Response($res);
        */
        return $this->render('MBUserBundle:User:index.html.twig', array('users' => $users));
    }
    
    public function viewAction($id){
        $repository = $this->getDoctrine()->getRepository('MBUserBundle:User');
        //$user = $repository->find($id);
        $user = $repository->findOneById($id);
        return new Response ('Usuario: '.$user->getUsername().'<br>Email: '.$user->getEmail());
    }
    
    
}
