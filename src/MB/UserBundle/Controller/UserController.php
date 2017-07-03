<?php

namespace MB\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
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
    
    public function editAction($id){
        
        $con = $this->getDoctrine()->getManager();
        $user = $con->getRepository('MBUserBundle:User')->find($id);
        
        if(!$user){
            $messageException = $this->get('translator')->trans('User not found');
            throw $this->createNotFoundException($messageException);
        }
        
        $form = $this->createEditForm($user);
        
        return $this->render('MBUserBundle:User:edit.html.twig', array('user' => $user, 'form' => $form->createView()));
        
    }
    
    public function updateAction($id, Request $request){
        
        $con = $this->getDoctrine()->getManager();
        $user = $con->getRepository('MBUserBundle:User')->find($id);
        
        if(!$user){
            $messageException = $this->get('translator')->trans('User not found');
            throw $this->createNotFoundException($messageException);
        }
        
        $form = $this->createEditForm($user);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            
            $password = $form->get('password')->getData();
            if(!empty($password)){
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);
                $user->setPassword($encoded);
            }else{
                $recoverPass = $this->recoverPass($id);
                $user->setPassword($recoverPass[0]['password']);
            }
            
            if($form->get('role')->getData() == 'ROLE_ADMIN'){
                $user->setIsActive(1);
            }
            
            $con->flush();
            $successMessage = $this->get('translator')->trans('The user has been modified');
            $this->addFlash('mensaje', $successMessage);
            
            return $this->redirectToRoute('mb_user_index'); 
        }
        return $this->render('MBUserBundle:User:edit.html.twig', array('id' => $user->getId(), 'form' => $form->createView()));
        
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
            
            $passwordConstraint = new Assert\NotBlank();
            $errorList = $this->get('validator')->validate($password, $passwordConstraint);
            if(count($errorList) ==0){
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);
                $user->setPassword($encoded);
            
                $con = $this->getDoctrine()->getManager();
                $con->persist($user);
                $con->flush();
            
                $successMessage = $this->get('translator')->trans('The user has been created');
                $this->addFlash('mensaje', $successMessage);
            
                return $this->redirectToRoute('mb_user_index');
                
                }else{
                    $errorMessage = new FormError($errorList[0]->getMessage());
                    $form->get('password')->addError($errorMessage);
                }
            
        }
        return $this->render('MBUserBundle:User:add.html.twig', array('form' => $form->createView()));
    }
    
    
    
    private function createCreateForm(User $entity){
        $form = $this->createForm(new UserType(), $entity, array('action' => $this->generateUrl('mb_user_create'), 'method' => 'POST'));
        return $form;
    }
    
    private function createEditForm(User $entity){
        $form = $this->createForm(new UserType(), $entity, array('action' => $this->generateUrl('mb_user_update', array('id' => $entity->getId())), 'method' => 'PUT'));
        return $form;
    }
    
    private function recoverPass($id){
        $con = $this->getDoctrine()->getManager();
        $query = $con->createQuery('SELECT u.password FROM MBUserBundle:User u WHERE u.id = :id')->setParameter('id', $id);
        $currentPass = $query->getResult();
        
        return $currentPass;
    }
    
}
