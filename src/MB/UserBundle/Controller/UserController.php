<?php

namespace MB\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use MB\UserBundle\Entity\User;
use MB\UserBundle\Form\UserType;

class UserController extends Controller
{
    public function indexAction(){
        
        $con = $this->getDoctrine()->getManager();
        $users = $con->getRepository('MBUserBundle:User')->findAll();
        
        return $this->render('MBUserBundle:User:index.html.twig', array('users' => $users));
    }
    
    public function viewAction($id){
        
    }
    
    public function addAction(){
        
        $user = new User();
        $form = $this->createCreateForm($user);
        
        return $this->render('MBUserBundle:User:add.html.twig', array('form' => $form->createView()));
    }
    
    public function createAction(Request $request){
        $user = new User();
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);
        
        if($form->isValid()){
            $password = $form->get('password')->getData();
            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $password);
            $user->setPassword($encoded);
            
            $con = $this->getDoctrine()->getManager();
            $con->persist($user);
            $con->flush();
            
            $successMessage = $this->get('translator')->trans('The user has been created');
            $this->addFlash('mensaje', $successMessage);
            
            return $this->redirectToRoute('mb_user_index');
        }
        return $this->render('MBUserBundle:User:add.html.twig', array('form' => $form->createView()));
    }
    
    private function createCreateForm(User $entity){
        $form = $this->createForm(new UserType(), $entity, array('action' => $this->generateUrl('mb_user_create'), 'method' => 'POST'));
        return $form;
    }
    
}
