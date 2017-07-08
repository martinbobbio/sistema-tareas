<?php

namespace MB\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use MB\UserBundle\Entity\Task;
use MB\UserBundle\Form\TaskType;

class TaskController extends Controller
{
    
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
    
}
