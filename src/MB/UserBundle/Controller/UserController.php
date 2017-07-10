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
    //Login ------------------------------------------------------------------------------------------------
    
    public function homeAction(){
        return $this->render('MBUserBundle:User:home.html.twig');
    }
    
    //Index ------------------------------------------------------------------------------------------------
    
    public function indexAction(){
        
        $con = $this->getDoctrine()->getManager();
        $users = $con->getRepository('MBUserBundle:User')->findAll();
        
        $deleteFormAjax = $this->createCustomForm(':USER_ID','DELETE','mb_user_delete');
        
        return $this->render('MBUserBundle:User:index.html.twig', array('users' => $users, 'delete_form_ajax' => $deleteFormAjax->createView()));
    }
    
    private function createCustomForm($id,$method,$route){
        
        return $this->createFormBuilder()->setAction($this->generateUrl($route, array ('id' => $id)))->setMethod($method)->getForm();
    }
    
    
    //View ------------------------------------------------------------------------------------------------
    
    public function viewAction($id){
        
        $repository = $this->getDoctrine()->getRepository('MBUserBundle:User');
        $user = $repository->find($id);
        
        if(!$user){
            $messageException = $this->get('translator')->trans('User not found');
            throw $this->createNotFoundException($messageException);
        }
        
        $deleteForm = $this->createCustomForm($user->getId(),'DELETE','mb_user_delete');
        
        return $this->render('MBUserBundle:User:view.html.twig', array('user' => $user, 'delete_form' => $deleteForm->createView()));
        
    }
    
    //Edit ------------------------------------------------------------------------------------------------
    
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
    
    //Add ------------------------------------------------------------------------------------------------
    
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
    
    
    //Delete ------------------------------------------------------------------------------------------------
    
    public function deleteAction(Request $request, $id){
        
        $con = $this->getDoctrine()->getManager();
        $user = $con->getRepository('MBUserBundle:User')->find($id);
        
        if(!$user){
            $messageException = $this->get('translator')->trans('User not found');
            throw $this->createNotFoundException($messageException);
        }
        
        $form = $this->createCustomForm($user->getId(),'DELETE','mb_user_delete');
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            
            if($request->isXMLHttpRequest()){
                
                $res = $this->deleteUser($user->getRole(), $con, $user);
                return new Response(json_encode(array('removed' => $res['removed'],'message' => $res['message'])),200, array('Content-Type' => 'application/json'));
            }
            
            $res = $this->deleteUser($user->getRole(),$con,$user);
            
            $this->addFlash($res['alert'], $res['message']);
            return $this->redirectToRoute('mb_user_index');
        }
    }
    
    private function deleteUser($role, $con, $user){
        if($role == 'ROLE_USER'){
            $con->remove($user);
            $con->flush();
            
            $message = $this->get('translator')->trans('The user has been deleted');
            $removed = 1;
            $alert = 'mensaje';
        }elseif($role == 'ROLE_ADMIN'){
            $message = $this->get('translator')->trans('The user could not be deleted');
            $removed = 0;
            $alert = 'error';
        }
        
        return array('removed' => $removed, 'message' => $message, 'alert' => $alert);
    }
    
    
}
