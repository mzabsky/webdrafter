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
    	$setUrlName = $this->getEvent()->getRouteMatch()->getParam('url_name');
    	
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$setVersionTable = $sm->get('Application\Model\SetVersionTable');
    	$cardTable = $sm->get('Application\Model\CardTable');
    	$userTable = $sm->get('Application\Model\UserTable');
    	
    	$viewModel = new ViewModel();
    	$viewModel->set = $setTable->getSetByUrlName($setUrlName);
    	$viewModel->user = $userTable->getUser($viewModel->set->userId);
    	$viewModel->currentSetVersion = $setVersionTable->getSetVersion($viewModel->set->currentSetVersionId);
    	$viewModel->setVersions = $setVersionTable->getSetVersionsBySet($viewModel->set->setId);
    	$viewModel->cards = \Application\resultSetToArray($cardTable->fetchBySetVersion($viewModel->set->currentSetVersionId));
    	
    	if(isset($_GET["source"])){
    		echo nl2br (htmlspecialchars($viewModel->set->about));
    		die();
    	}
    	
    	return $viewModel;
    }
    
    public function setVersionAction()
    {
    	$setUrlName = $this->getEvent()->getRouteMatch()->getParam('url_name');
    	$setVersionUrlName = $this->getEvent()->getRouteMatch()->getParam('version_url_name');
    	
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$setVersionTable = $sm->get('Application\Model\SetVersionTable');
    	$cardTable = $sm->get('Application\Model\CardTable');
    	$userTable = $sm->get('Application\Model\UserTable');
    	
    	$viewModel = new ViewModel();
    	$viewModel->set = $setTable->getSetByUrlName($setUrlName);
    	$viewModel->user = $userTable->getUser($viewModel->set->userId);
    	$viewModel->setVersion = $setVersionTable->getSetVersionByUrlName($viewModel->set->setId, $setVersionUrlName);
    	$viewModel->cards = \Application\resultSetToArray($cardTable->fetchBySetVersion($viewModel->setVersion->setVersionId));
    	
    	if(isset($_GET["source"])){
    		echo nl2br (htmlspecialchars($viewModel->setVersion->about));
    		die();
    	}
    	
    	return $viewModel;
    }
    
    public function userAction()
    {
    	$urlName = $this->getEvent()->getRouteMatch()->getParam('url_name');
    	 
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$userTable = $sm->get('Application\Model\UserTable');
    
    	$viewModel = new ViewModel();
    	$viewModel->user = $userTable->getUserByUrlName($urlName);
    	$viewModel->sets = $setTable->getSetsByUser($viewModel->user->userId, false);
    
    	if(isset($_GET["source"])){
    		echo nl2br (htmlspecialchars($viewModel->user->about));
    		die();
    	}
    	
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
    	
    	if(isset($_GET["image"]))
    	{
    		return $this->redirect()->toUrl($card->artUrl);//->toRoute('browse-card', array('set_url_name' => $set->urlName, 'version_url_name' => $setVersion->urlName, 'card_url_name' => $card->urlName));
    	}
    	
    	else //if(!isset($_GET["ajax"]))
    	{
    		return $this->redirect()->toRoute('browse-card', array('set_url_name' => $set->urlName, 'version_url_name' => $setVersion->urlName, 'card_url_name' => $card->urlName));
    	}
    	
    	/*$viewModel = new ViewModel();
    	$viewModel->setVersion = $setVersion;
    	$viewModel->set = $set;
    	$viewModel->card = $card;
    	//$viewModel->sets = $setTable->getSetsByUser($userId);
    	$viewModel->setTerminal(true);
    	return $viewModel; */
    }
    
    public function cardAction()
    {

    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$setVersionTable = $sm->get('Application\Model\SetVersionTable');
    	$userTable = $sm->get('Application\Model\UserTable');
    	$cardTable = $sm->get('Application\Model\CardTable');
    	$userTable = $sm->get('Application\Model\UserTable');

    	$viewModel = new ViewModel();

    	$viewModel->set = $setTable->getSetByUrlName($this->getEvent()->getRouteMatch()->getParam('set_url_name'));
    	$viewModel->user = $userTable->getUser($viewModel->set->userId);
    	$viewModel->setVersion = $setVersionTable->getSetVersionByUrlName($viewModel->set->setId, $this->getEvent()->getRouteMatch()->getParam('version_url_name'));
    	$viewModel->card = $cardTable->getCardByUrlName($viewModel->setVersion->setVersionId, $this->getEvent()->getRouteMatch()->getParam('card_url_name'));
    	if($viewModel->card->firstVersionCardId != NULL)
    	{
    		$viewModel->changeHistory = \Application\resultSetToArray($cardTable->getCardHistory($viewModel->card->firstVersionCardId));
    	}    	
    	else 
    	{
    		$viewModel->changeHistory = NULL;
    	}
    	
    	return $viewModel;
    }
}
