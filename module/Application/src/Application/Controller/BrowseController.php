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
    	$userTable = $sm->get('Application\Model\UserTable');
    	
    	$viewModel = new ViewModel(); 
    	$viewModel->sets = $setTable->getSets();
    	$viewModel->users = $userTable->getUsers();
    	
        return $viewModel;
    }
    
    public function setAction()
    {
    	$setId = $this->getEvent()->getRouteMatch()->getParam('set_id');
    	
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$setVersionTable = $sm->get('Application\Model\SetVersionTable');
    	$cardTable = $sm->get('Application\Model\CardTable');
    	 
    	$viewModel = new ViewModel();
    	$viewModel->set = $setTable->getSetByUrlName($setId);
    	$viewModel->currentSetVersion = $setVersionTable->getSetVersion($viewModel->set->currentSetVersionId);
    	$viewModel->setVersions = $setVersionTable->getSetVersionsBySet($viewModel->set->setId);
    	$viewModel->cards = $cardTable->fetchBySetVersion($viewModel->set->currentSetVersionId);
    	 
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
    	$viewModel->sets = $setTable->getSetsByUser($userId);
    
    	return $viewModel;
    }
    
    public function autocardAction()
    {

    	$contextIdentifier = isset($_GET['context']) ? $_GET['context'] : "";
    	$cardIdentifier = isset($_GET['card']) ? $_GET['card'] : "";
    	$setIdentifier = isset($_GET['set']) ? $_GET['set'] : "";
    	$setVersionIdentifier = isset($_GET['setVersion']) ? $_GET['setVersion'] : "";
    	
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$setVersionTable = $sm->get('Application\Model\SetVersionTable');
    	$userTable = $sm->get('Application\Model\UserTable');
    	$cardTable = $sm->get('Application\Model\CardTable');
    	
    	if(is_numeric($cardIdentifier))
    	{
    		$card = $cardTable->getCard((int)$cardIdentifier);
    		$setVersion = $setVersionTable->getSetVersion($card->setVersionId);
    		$set = $setTable->getSet($setVersion->setId);
    	}
    	else {
    		if(is_numeric($setIdentifier))
    		{
    			$set = $setTable->getSet((int)$setIdentifier);
    		}
    		else if($setIdentifier != null && $setIdentifier  != "") {
    			$set = $setTable->getSetByUrlName($setIdentifier);
    		}
    		else {
    			$set = $setTable->getSetByUrlName($contextIdentifier);
    		}
    		 
    		if(is_numeric($setVersionIdentifier))
    		{
    			$setVersion = $setVersionTable->getSetVersion((int)$setVersionIdentifier);
    		}
    		else if($setVersionIdentifier != null && $setVersionIdentifier != ""){
    			$setVersion = $setVersionTable->getSetVersionByUrlName($set->setId, $setVersionIdentifier);
    		}
    		else {
    			$setVersion = $setVersionTable->getSetVersion($set->currentSetVersionId);
    		}
    		
    		$card = $cardTable->getCardByName($setVersion->setVersionId, $cardIdentifier);
    	}
    	
    	if(!isset($_GET["ajax"]))
    	{
    		return $this->redirect()->toRoute('browse-card', array('url_name' => $set->urlName, 'version_url_name' => $setVersion->urlName, 'card_name' => $card->name));
    	}
    	
    	$viewModel = new ViewModel();
    	$viewModel->setVersion = $setVersion;
    	$viewModel->set = $set;
    	$viewModel->card = $card;
    	//$viewModel->sets = $setTable->getSetsByUser($userId);
    	$viewModel->setTerminal(true);
    	return $viewModel;
    }
}
