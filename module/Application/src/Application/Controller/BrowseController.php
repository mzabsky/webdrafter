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
use Zend\View\Model\JsonModel;

class BrowseController extends WebDrafterControllerBase
{
    public function indexAction()
    {
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$userTable = $sm->get('Application\Model\UserTable');
    	$cardTable = $sm->get('Application\Model\CardTable');

    	$viewModel = new ViewModel();
    	if(isset($_GET["query"])){
    		$viewModel->query = $_GET["query"];
    		$viewModel->cards = $cardTable->queryCards($_GET["query"], $messages);
    		$viewModel->messages = $messages;
    	}
    	else {
    		$viewModel->query = "";
    	}
    	
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
    	if($viewModel->set === null){
    		return $this->notFoundAction();
    	}
    	
    	$viewModel->user = $userTable->getUser($viewModel->set->userId);
    	if($viewModel->user === null){
    		return $this->notFoundAction();
    	}
    	
			$cards = \Application\resultSetToArray($cardTable->fetchBySetVersion($viewModel->set->currentSetVersionId));

    	if(isset($_GET["source"])){
    		echo nl2br (htmlspecialchars($viewModel->set->about));
    		die();
    	} else if(isset($_GET["json"])) {
				$viewModel = new JsonModel();

				$jsonCards = [];
				foreach ($cards as $card) {
					$jsonCard = [];
					$jsonCard["artUrl"] = $card->artUrl;
					$jsonCards[$card->name] = $jsonCard;
				}

				$viewModel->cards = $jsonCards;

				return $viewModel;
			}
    	
    	$viewModel->currentSetVersion = $setVersionTable->getSetVersion($viewModel->set->currentSetVersionId);
    	$viewModel->setVersions = $setVersionTable->getSetVersionsBySet($viewModel->set->setId);
    	$viewModel->cards = $cards;
    	
    	return $viewModel;
    }
    
    public function standaloneAction()
    {
    	$setUrlName = $this->getEvent()->getRouteMatch()->getParam('url_name');
    	
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$setVersionTable = $sm->get('Application\Model\SetVersionTable');
    	$cardTable = $sm->get('Application\Model\CardTable');
    	$userTable = $sm->get('Application\Model\UserTable');
    	
    	$viewModel = new ViewModel();
    	$viewModel->set = $setTable->getSetByUrlName($setUrlName);
    	if($viewModel->set === null){
    		return $this->notFoundAction();
    	}
    	
    	$viewModel->user = $userTable->getUser($viewModel->set->userId);
    	if($viewModel->user === null){
    		return $this->notFoundAction();
    	}
    	
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
    	if($viewModel->set === null){
    		return $this->notFoundAction();
    	}
    	
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
    	if($viewModel->user === null){
    		return $this->notFoundAction();
    	}
    	
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
    	$contextVersionIdentifier = isset($_GET['contextVersion']) ? $_GET['contextVersion'] : "";
    	$cardIdentifier = isset($_GET['card']) ? $_GET['card'] : "";
    	$setIdentifier = isset($_GET['set']) ? $_GET['set'] : "";
    	$setVersionIdentifier = isset($_GET['setVersion']) ? $_GET['setVersion'] : "";
    	
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$setVersionTable = $sm->get('Application\Model\SetVersionTable');
    	$userTable = $sm->get('Application\Model\UserTable');
    	$cardTable = $sm->get('Application\Model\CardTable');
    	
			$isJson = isset($_GET["json"]);
			$isBot = isset($_GET["bot"]) || $isJson;
			$viewModel = new ViewModel();
			$viewModel->isBot = $isBot;
			$viewModel->isJson = $isJson;
    	$viewModel->setTerminal(true);
    	
    	if(is_numeric($cardIdentifier))
    	{
    		$card = $cardTable->getCard((int)$cardIdentifier);
    		$setVersion = $setVersionTable->getSetVersion($card->setVersionId);
    		$set = $setTable->getSet($setVersion->setId);
    	}
    	else {
    		$setSpecified = false;
    		if(is_numeric($setIdentifier))
    		{
    			$setSpecified = true;
    			$set = $setTable->getSet((int)$setIdentifier);
    		}
    		else if($setIdentifier == "*"){
    			$set = null;
    		}
    		else if($setIdentifier != null && $setIdentifier  != "") 
    		{
    			$setSpecified = true;
    			$set = $setTable->getSetForBot($setIdentifier);
    		}
    		else if($contextIdentifier != null && $contextIdentifier != "") {
    			$set = $setTable->getSetByUrlName($contextIdentifier);
    		}
    		else if(($contextIdentifier == null || $contextIdentifier == "") && ($setIdentifier == null || $setIdentifier  == "")) {
    			$set = null;
    		}
    		else {
    			die("b");
    			return $this->notFoundAction();
    		}

    		if($set === null){
    			if($isBot && $setSpecified == true)
    			{
    				$viewModel->message = "Set not found.";
    				return $viewModel;
    			}
    			else if(!$isBot) 
    			{
    				return $this->notFoundAction();
    			}
    		}
    		
    		$setVersionSpecified = false;
    		if($set === null) {
    			$setVersion = null;
    		}
    		else if(is_numeric($setVersionIdentifier))
    		{
    			$setVersionSpecified = true;
    			$setVersion = $setVersionTable->getSetVersion((int)$setVersionIdentifier);
    		}
    		else if($setVersionIdentifier != null && $setVersionIdentifier != ""){
    			$setVersionSpecified = true;
    			$setVersion = $setVersionTable->getSetVersionByUrlName($set->setId, $setVersionIdentifier);
    		}
    		else if($contextVersionIdentifier != null && $contextVersionIdentifier != ""){
    			$setVersion = $setVersionTable->getSetVersionByUrlName($set->setId, $contextVersionIdentifier);
    		}
    		else {
    			$setVersionSpecified = true;
    			$setVersion = $setVersionTable->getSetVersion($set->currentSetVersionId);
    		}
    		
    		if($setVersion === null){
    		    if($isBot && $setVersionSpecified)
    			{
    				$viewModel->message = "Set version not found.";
    				return $viewModel;
    			}
    			else if(!$isBot)
    			{
    				return $this->notFoundAction();
    			}
    		}
    		
    		//var_dump(@$setVersion->setVersionId, $cardIdentifier, $isBot ? 4 : 1);
    		$cards = \Application\resultSetToArray($cardTable->getCardsForBot($setVersion != null ? $setVersion->setVersionId : null, $cardIdentifier, $isBot ? 4 : 1));
    		//var_dump($cards);
    		if(count($cards) == 0){
    		    if($isBot)
    			{
    				$viewModel->message = "Card not found.";
    				return $viewModel;
    			}
    			else 
    			{
    				return $this->notFoundAction();
    			}
    		}
    	}
    	
    	if(isset($_GET["image"]))
    	{
    		$card = $cards[0];
    		return $this->redirect()->toUrl($card->artUrl);//->toRoute('browse-card', array('set_url_name' => $set->urlName, 'version_url_name' => $setVersion->urlName, 'card_url_name' => $card->urlName));
    	}
    	else if($isBot)
    	{
    		$response = $this->getResponse();
    		
    		$headers = $response->getHeaders();
    		$headers->addHeaderLine('Content-Type', 'text/plain; charset=utf-8');    		
    		
    		$viewModel->set = $set;
    		$viewModel->cards = $cards;
    		
    		return $viewModel;
    	}
    	else //if(!isset($_GET["ajax"]))
    	{
    		$card = $cards[0];
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
    	if($viewModel->set === null){
    		return $this->notFoundAction();
    	}
    	
    	$viewModel->user = $userTable->getUser($viewModel->set->userId);
    	$viewModel->setVersion = $setVersionTable->getSetVersionByUrlName($viewModel->set->setId, $this->getEvent()->getRouteMatch()->getParam('version_url_name'));
    	if($viewModel->setVersion === null){
    		return $this->notFoundAction();
    	}
    	
    	$viewModel->card = $cardTable->getCardByUrlName($viewModel->setVersion->setVersionId, $this->getEvent()->getRouteMatch()->getParam('card_url_name'));
    	if($viewModel->card === null){
    		return $this->notFoundAction();
    	}
    	
    	if($viewModel->card->firstVersionCardId != NULL)
    	{
    		$viewModel->changeHistory = \Application\resultSetToArray($cardTable->getCardHistory($viewModel->card->firstVersionCardId));
    	}    	
    	else 
    	{
    		$viewModel->changeHistory = \Application\resultSetToArray($cardTable->getCardHistory($viewModel->card->cardId));
    	}
    	
    	if(count($viewModel->changeHistory) <= 1) {
    		$viewModel->changeHistory = null;
    	}
    	
    	return $viewModel;
    }
}
