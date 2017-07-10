<?php

namespace MB\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use MB\UserBundle\Entity\Task;
use MB\UserBundle\Form\TaskType;

class TaskController extends Controller
{
    
    //Custom ------------------------------------------------------------------------------------------------
    
    public function customAction(Request $request){
        $idUser = $this->get('security.token_storage')->getToken()->getUser()->getId();
        $con = $this->getDoctrine()->getManager();
        $dql = "SELECT t FROM MBUserBundle:Task t JOIN t.user u WHERE u.id = :idUser ORDER BY t.id DESC";
        
        $tasks = $con->createQuery($dql)->setParameter('idUser', $idUser)->getResult();
        
        $updateForm = $this->createCustomForm(':TASK_ID', 'PUT', 'mb_task_process');
        
        return $this->render('MBUserBundle:Task:custom.html.twig', array('tasks' => $tasks, 'update_form' => $updateForm->createView()));
        
    }
    
    public function processAction($id, Request $request){
        $con = $this->getDoctrine()->getManager();
        $task = $con->getRepository('MBUserBundle:Task')->find($id);
        
        if(!$task){
            throw $this->createNotFoundException('The task does not exist');
        }
        
        $form = $this->createCustomForm($task->getId(), 'PUT', 'mb_task_process');
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            if($task->getStatus() == 0){
                $task->setStatus(1);
                $con->flush();
                
                if($request->isXMLHttpRequest()){
                    return new Response(json_encode(array('processed' => 1)),200, array('Content-Type' => 'application/json'));
                }
            }
        }else{
            if($request->isXMLHttpRequest()){
                    return new Response(json_encode(array('processed' => 0)),200, array('Content-Type' => 'application/json'));
                }
        }
        
        
    }
    
    
    //Index ------------------------------------------------------------------------------------------------
    
    public function indexAction(Request $request){
        $con = $this->getDoctrine()->getManager();
        $tasks = $con->getRepository('MBUserBundle:Task')->findAll();
        
        return $this->render('MBUserBundle:Task:index.html.twig', array('tasks' => $tasks));
    }
    
    
    
    //Add ------------------------------------------------------------------------------------------------
    
    public function addAction(){
        $task = new Task();
        $form = $this->createCreateForm($task);
        
        return $this->render('MBUserBundle:Task:add.html.twig', array('form' => $form->createView()));
        
    }
    
    public function createAction(Request $request){
        $task = new Task();
        $form = $this->createCreateForm($task);
        $form->handleRequest($request);
        
        if($form->isValid()){
            $task->setStatus(0);
            $con = $this->getDoctrine()->getManager();
            $con->persist($task);
            $con->flush();
            
            $successMessage = $this->get('translator')->trans('The task has been created');
            $this->addFlash('mensaje', $successMessage);
            
            return $this->redirectToRoute('mb_task_index');
        }
        
        return $this->render('MBUserBundle:Task:add.html.twig', array('form' => $form->createView()));
    }
    
    private function createCreateForm(Task $entity){
        $form = $this->createForm(new TaskType(),$entity, array ('action' => $this->generateUrl('mb_task_create'),'method' => 'POST'));
        return $form;
    }
    
    //View ------------------------------------------------------------------------------------------------
    
    public function viewAction($id){
        $task = $this->getDoctrine()->getRepository('MBUserBundle:Task')->find($id);
        
        if(!$task){
            throw $this->createNotFoundException('The task does not exist');
        }
        
        $deleteForm = $this->createCustomForm($task->getId(),'DELETE','mb_task_delete');
        
        $user = $task->getUser();
        
        return $this->render('MBUserBundle:Task:view.html.twig', array('task'=>$task, 'user'=>$user, 'delete_form' => $deleteForm->createView()));
    }
    
    private function createCustomForm($id,$method,$route){
        return $this->createFormBuilder()->setAction($this->generateUrl($route,array('id' => $id)))->setMethod($method)->getForm();
    }
    
    //Edit ------------------------------------------------------------------------------------------------
    
    public function editAction($id){
        $con = $this->getDoctrine()->getManager();
        $task = $con->getRepository('MBUserBundle:Task')->find($id);
        
        if(!$task){
            throw $this->createNotFoundException('Task not found');
        }
        
        $form = $this->createEditForm($task);
        
        return $this->render('MBUserBundle:Task:edit.html.twig',array('task' => $task, 'form' => $form->createView()));
    }
    
    public function updateAction($id, Request $request){
        $con = $this->getDoctrine()->getManager();
        $task = $con->getRepository('MBUserBundle:Task')->find($id);
        
        if(!$task){
            throw $this->createNotFoundException('Task not found');
        }
        
        $form = $this->createEditForm($task);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            $task->setStatus(0);
            $con->flush();
            
            $successMessage = $this->get('translator')->trans('The task has been updated');
            $this->addFlash('mensaje', $successMessage);
            
            return $this->redirectToRoute('mb_task_index'); 
        }
        
        return $this->render('MBUserBundle:Task:edit.html.twig',array('task' => $task, 'form' => $form->createView()));
        
    }
    
    private function createEditForm(Task $entity){
        $form = $this->createForm(new TaskType(),$entity,array('action' => $this->generateUrl('mb_task_update', array('id' => $entity->getId())), 'method' => 'PUT'));
        return $form;
    }
    
    
    //Delete ------------------------------------------------------------------------------------------------
    
    public function deleteAction(Request $request,$id){
        $con = $this->getDoctrine()->getManager();
        $task = $con->getRepository('MBUserBundle:Task')->find($id);
        
        if(!$task){
            throw $this->createNotFoundException('Task not found');
        }
        
        $form = $this->createCustomForm($task->getId(),'DELETE','mb_user_delete');
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            $con->remove($task);
            $con->flush();
            
            $successMessage = $this->get('translator')->trans('The task has been deleted');
            $this->addFlash('mensaje', $successMessage);
            
            return $this->redirectToRoute('mb_task_index');
        }
    }
    
}
