<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class BrowseController extends AbstractActionController
{
    public function indexAction()
    {
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	
    	$viewModel = new ViewModel(); 
    	$viewModel->sets = $setTable->fetchAll();
    	
        return $viewModel;
    }
    
    public function setAction()
    {
    	$setId = $this->getEvent()->getRouteMatch()->getParam('set_id');
    	
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$cardTable = $sm->get('Application\Model\CardTable');
    	 
    	$viewModel = new ViewModel();
    	$viewModel->set = $setTable->getSet($setId);
    	$viewModel->cards = $cardTable->fetchBySet($setId);
    	 
    	return $viewModel;
    }
    
    public function userAction()
    {
    	$userId = $this->getEvent()->getRouteMatch()->getParam('user_id');
    	 
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$userTable = $sm->get('Application\Model\UserTable');
    
    	$viewModel = new ViewModel();
    	$viewModel->user = $userTable->getUser($userId);
    	$viewModel->sets = $setTable->fetchByUser($userId);
    
    	return $viewModel;
    }
}
