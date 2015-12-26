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
use League\OAuth2\Client\Provider;
use Application\Model\Set;
use Application\Model\Draft;
use Application\Model\Pick;
use Application\Model\DraftSet;
use Zend\View\Model\JsonModel;
use Application\Model\DraftPlayer;
use Application\Model\User;
use Application\PackGenerator\BoosterDraftPackGenerator;
use Application\PackGenerator\CubePackGenerator;
use Application\Form\CreateSetForm;
use Application\GoogleAuthentication;
use Application\Form\UploadCardsForm;

class MemberAreaController extends AbstractActionController
{
	private function initUser($allowUnregistered = false)
	{
		$sm = $this->getServiceLocator();
		$auth = $sm->get('Application\GoogleAuthentication');
		
		if($auth->GetStatus() == GoogleAuthentication::STATUS_ANONYMOUS)
		{
			return $this->redirect()->toRoute('member-area', array('action' => 'login'));
		}
		else if($auth->GetStatus() == GoogleAuthentication::STATUS_NOT_REGISTERED && !$allowUnregistered)
		{
			return $this->redirect()->toRoute('member-area', array('action' => 'register'));
		}
		
		return NULL;
	}
	
	private function createClient()
	{
		$redirectUri = $this->url()->fromRoute('member-area', array('action' => 'login'), array('force_canonical' => true));
		
		$scopes = implode(' ', array(
				//\Google_Service_Drive::DRIVE_METADATA_READONLY,
				\Google_Service_Drive::DRIVE_READONLY,
				\Google_Service_Oauth2::USERINFO_EMAIL,
				\Google_Service_Oauth2::USERINFO_PROFILE)
		);
		
		$client = new \Google_Client();
		$client->setApplicationName('WebDrafter');
		$client->setScopes($scopes);
		$client->setAuthConfigFile('config/client_secret.json');
		$client->setAccessType('offline');
		$client->setRedirectUri($redirectUri);
		
		return $client;
	}
	
	public function indexAction()
	{	
		if(($redirect = $this->initUser()) != NULL) return $redirect;
		
		$sm = $this->getServiceLocator();
		$auth = $sm->get('Application\GoogleAuthentication');
		$draftTable = $sm->get('Application\Model\DraftTable');
		$setTable = $sm->get('Application\Model\SetTable');
		
		$adapter = $sm->get("Zend\Db\Adapter\Adapter");
		
		$form = new \Application\Form\RegistrationForm(false);
		$form->setAttribute('action', $this->url()->fromRoute('member-area', array('action' => 'index'), array('fragment' => 'account_settings_tab')));
		
		if ($this->getRequest()->isPost())
		{
			$formData = $this->getRequest()->getPost()->toArray();
			
			$userTable = $sm->get('Application\Model\UserTable');
			$user = $auth->getUser();
			$inputFilter = $user->getInputFilter();
			$inputFilter->remove('name');
			$inputFilter->remove('url_name');
			$form->setInputFilter($inputFilter);			
		
			$form->setData($formData);
			
			if ($form->isValid($formData))
			{
				$user->emailPrivacy = $formData["email_privacy"];
				$user->about = $formData["about"];
					
				$userTable->saveUser($user);
		
				return $this->redirect()->toRoute('member-area', array(), array('query' => 'account-updated'));
			}
			else
			{
			}
		}
		else {
			$user = $auth->getUser();
			$form->setData($user->getArray());
		}
		
		$viewModel = new ViewModel();
		
		$viewModel->setCreated = isset($_GET["set-created"]);
		$viewModel->setRetired = isset($_GET["set-retired"]);
		$viewModel->accountUpdated = isset($_GET["account-updated"]);
		$viewModel->draftsHosted = $draftTable->getPastDraftsByHost($_SESSION["user_id"]);
		$viewModel->draftsPlayed = $draftTable->getPastDraftsByUser($_SESSION["user_id"]);
		$viewModel->setsOwned = $setTable->getSetsByUser($_SESSION["user_id"], true);
		$viewModel->form = $form;
		
		return $viewModel;
	}
	
	public function registerAction()
	{
		if(($redirect = $this->initUser(true)) != NULL) return $redirect;

		if($_SESSION["not_registered"] != true){
			return $this->redirect()->toRoute('member-area');
		}
		
		$sm = $this->getServiceLocator();
		$auth = $sm->get('Application\GoogleAuthentication');
		$adapter = $sm->get("Zend\Db\Adapter\Adapter");
		
		$form = new \Application\Form\RegistrationForm(true);
		$form->setAttribute('action', $this->url()->fromRoute('member-area', array('action' => 'register')));
		
		if ($this->getRequest()->isPost())
		{
			$formData = $this->getRequest()->getPost()->toArray();

			$userTable = $sm->get('Application\Model\UserTable');
			$user = $userTable->tryGetUserByEmail($_SESSION["email"]);
			$form->setInputFilter($user->getInputFilter());
				
			$form->setData($formData);
				
			if ($form->isValid($formData))
			{
				$user->name = $formData["name"];
				$user->urlName = $formData["url_name"];
				$user->emailPrivacy = $formData["email_privacy"];
				$user->about = $formData["about"];
					
				$userTable->saveUser($user);
				
				return $this->redirect()->toRoute('member-area');
			}
			else
			{
				//var_dump($form->getMessages());
			}
		}
		else {
		}
		
		$viewModel = new ViewModel();
		$viewModel->driveAppId = $this->getServiceLocator()->get('Config')['auth']['driveAppId'];
		$viewModel->accessToken = $_SESSION['access_token'];
		$viewModel->form = $form;
		$viewModel->registrationMode = true;
		
		return $viewModel;
	}
	
	public function loginAction()
	{
		$client = $this->createClient();
		
		/*$provider = new \League\OAuth2\Client\Provider\Google([
				'clientId'      => $this->getServiceLocator()->get('Config')['auth']['clientId'],
				'clientSecret'  => $this->getServiceLocator()->get('Config')['auth']['clientSecret'],
				'redirectUri'   => $redirectUri,
				'scopes'        => ['email']
		]);*/
		
		if (isset($_GET['code'])) {
			$client->authenticate($_GET['code']);
			
			$_SESSION['access_token'] = $client->getAccessToken();
			
			$plus = new \Google_Service_Plus($client);
			$me = $plus->people->get("me");
			$_SESSION["email"] = $me['emails'][0]['value'];
			
			$sm = $this->getServiceLocator();
			$userTable = $sm->get('Application\Model\UserTable');
			$user = $userTable->tryGetUserByEmail($_SESSION["email"]);
			if($user == null)
			{
				$user = new User();
				$user->email = $_SESSION["email"];
				$userTable->saveUser($user);
				$_SESSION["user_id"] = $user->userId;
			}
			else
			{
				$_SESSION["user_id"] = $user->userId;
			}
			
			if($user->name === null)
			{
				$_SESSION["not_registered"] = true;
				$this->redirect()->toRoute('member-area', array('action' => 'register'));	
			}
			else {
				$_SESSION["not_registered"] = false;
			}
			
			$this->redirect()->toRoute('member-area');	
		}
		else if(isset($_SESSION['access_token']))
		{
			$client->setAccessToken($_SESSION['access_token']);
			$this->redirect()->toRoute('member-area');	
		}
		else {
			header("Location: " . $client->createAuthUrl());
			exit;
		}
		
		if ($client->isAccessTokenExpired()) {
			$client->refreshToken($client->getRefreshToken());
		}
		
		return new ViewModel();
	}
	
	public function logoutAction()
	{
	 	//unset($_SESSION["email"]);
	 	session_destroy();

		return $this->redirect()->toRoute('home');
	}
	
	public function createSetAction()
	{
		if(($redirect = $this->initUser()) != NULL) return $redirect;
		
		$sm = $this->getServiceLocator();
		$adapter = $sm->get("Zend\Db\Adapter\Adapter");
		
		$form = new \Application\Form\CreateSetForm();		
	
		if ($this->getRequest()->isPost()) 
		{
			$formData = array_merge_recursive(
            	$this->getRequest()->getPost()->toArray(),
            	$this->getRequest()->getFiles()->toArray()
        	);
			
			/*if ($formData['file']['tmp_name'] == "" || $formData['file']['tmp_name'] === null) {
				$formData['file'] = null;
			}*/
			
			$set = $sm->get('Application\Model\Set');
			$form->setInputFilter($set->getInputFilter());
			
			$form->setData($formData);
			
			if ($form->isValid($formData)) 
			{
				$adapter->getDriver()->getConnection()->beginTransaction();
				
				try
				{
					$set->name = $formData["name"];
					$set->urlName = $formData["url_name"];
					$set->code = $formData["code"];
					$set->about = $formData["about"];
					//$set->url = $formData["url"];
					//$set->downloadUrl = $formData["download_url"];
					$set->userId = $_SESSION["user_id"];
					$set->isPrivate = 1;
					$set->isFeatured = 0;
					$set->currentSetVersion = null;
					$set->status = Set::STATUS_UNPLAYABLE;
					
					$setTable = $sm->get('Application\Model\SetTable');
					$setTable->saveSet($set);
					
					/*$artUrl = $formData["art_url"];
					
					$fileContents = file_get_contents($this->getRequest()->getFiles('file')["tmp_name"]);
					
					$parser = new \Application\SetParser\IsochronDrafterSetParser();
					$cards = $parser->Parse($fileContents);
					
					$cardTable = $sm->get('Application\Model\CardTable');
					foreach($cards as $card)
					{
						$card->setId = $set->setId;
						$cardName = preg_replace("/[^\p{L}0-9- ]/iu", "", $card->name);
						switch($formData["art_url_format"])
						{
							case CreateSetForm::NAME_DOT_PNG:
								$card->artUrl = $artUrl . "/" . $cardName . ".png";
								break;
							case CreateSetForm::NAME_DOT_FULL_DOT_PNG:
								$card->artUrl = $artUrl . "/" . $cardName . ".full.png";
								break;
							case CreateSetForm::NAME_DOT_JPG:
								$card->artUrl = $artUrl . "/" . $cardName . ".jpg";
								break;
							case CreateSetForm::NAME_DOT_FULL_DOT_JPG:
								$card->artUrl = $artUrl . "/" . $cardName . ".full.jpg";
								break;
							default:
								throw new \Exception("Invalid art URL format.");
						}
						
						$cardTable->saveCard($card);
					}*/

					$adapter->getDriver()->getConnection()->commit();
				}
				catch(Exception $e)
				{
					$adapter->getDriver()->getConnection()->rollback();
					throw $e;
				}
								
				return $this->redirect()->toRoute('member-area-manage-set', array('set_id' => $set->setId), array('query' => 'set-created'));
			} 
			else 
			{
				//var_dump($form->getMessages());
			}
		}
		
		$viewModel = new ViewModel();
		$viewModel->form = $form;
		
		return $viewModel;
	}
	
	public function selectGameModeAction()
	{
		if(($redirect = $this->initUser()) != NULL) return $redirect;
		return new ViewModel();
	}
	
	public function hostDraftAction()
	{
		if(($redirect = $this->initUser()) != NULL) return $redirect;
		
		if(!isset($_REQUEST["mode"]) || (int)$_REQUEST["mode"] < 1)
		{
			throw new \Exception("Game mode not set");
		}
		
		$mode = (int)$_REQUEST["mode"];
		
		$sm = $this->getServiceLocator();
		$setTable = $sm->get('Application\Model\SetTable');		
		$form = new \Application\Form\HostDraftForm($setTable, $mode);
	
		if ($this->getRequest()->isPost())
		{
			$formData = $this->getRequest()->getPost();
			$form->setData($formData);
			if ($form->isValid($formData))
			{
				$sm = $this->getServiceLocator();
				$adapter = $sm->get("Zend\Db\Adapter\Adapter");
				$adapter->getDriver()->getConnection()->beginTransaction();
	
				try
				{
					$setTable = $sm->get('Application\Model\SetTable');
				
					$setIds = array();
					$numberOfPacks = (int)$formData['number_of_packs'];
					switch($mode)
					{
						case \Application\Model\Draft::MODE_BOOSTER_DRAFT:
						case \Application\Model\Draft::MODE_SEALED_DECK:
						case \Application\Model\Draft::MODE_CUBE_DRAFT:
							for($i = 1; $i <= $numberOfPacks; $i++)
							{
								$setIds[] = $formData['pack' . $i];
							}
							break;
						case \Application\Model\Draft::MODE_CHAOS_DRAFT:
							$setIds = $formData['pack1'];
							break;
						default:
							throw new \Exception("Invalid game mode " . $mode);
								
					}
					
					switch($mode)
					{
						case \Application\Model\Draft::MODE_BOOSTER_DRAFT:
							$modeName = 'booster draft';
							break;
						case \Application\Model\Draft::MODE_SEALED_DECK:
							$modeName = 'sealed deck';
							break;
						case \Application\Model\Draft::MODE_CUBE_DRAFT:
							$modeName = 'cube draft';
							break;
						case \Application\Model\Draft::MODE_CHAOS_DRAFT:
							$modeName = 'chaos draft';
							break;
						default:
							throw new \Exception("Invalid game mode " . $mode);
					
					}
					
					$sets = array();
					$setCodes = array();					
					foreach($setIds as $setId)
					{
						$set = $setTable->getSet($setId);
						$sets[] = $set;
						$setCodes[] = $set->code;	
					}
					
					$draft = new Draft();
					$draft->name = join("/", $setCodes) . " " . $modeName;
					$draft->status = Draft::STATUS_OPEN;
					$draft->hostId = $_SESSION["user_id"];
					$draft->createdOn = date("Y-m-d H:i:s");
					$draft->pickNumber = 1;
					$draft->packNumber = 1;
					$draft->lobbyKey = md5(time() . "lobby key" . $draft->hostId);
					$draft->gameMode = $mode;
						
					$draftTable = $sm->get('Application\Model\DraftTable');
					$draftTable->saveDraft($draft);
						
					$draftSetTable = $sm->get('Application\Model\DraftSetTable');
					foreach($setIds as $index => $setId)
					{
						$draftSet = new DraftSet();
						$draftSet->draftId = $draft->draftId;
						$draftSet->setId = $setId;
						$draftSet->packNumber = $index + 1;
						$draftSetTable->saveDraftSet($draftSet);
					}
	
					$adapter->getDriver()->getConnection()->commit();
				}
				catch(Exception $e)
				{
					$adapter->getDriver()->getConnection()->rollback();
					throw $e;
				}
				
				return $this->redirect()->toRoute('member-area-with-draft-id', array('action' => 'draft-admin', 'draft_id' => $draft->draftId), array('query' => 'draft-opened'));
			}
		}
	
		$viewModel = new ViewModel();
		$viewModel->form = $form;
	
		return $viewModel;
	}
	
	public function draftAdminAction()
	{	
		if(($redirect = $this->initUser()) != NULL) return $redirect;
		
		$draftId = $this->getEvent()->getRouteMatch()->getParam('draft_id');
		
		$sm = $this->getServiceLocator();
		$draftTable = $sm->get('Application\Model\DraftTable');
		
		$viewModel = new ViewModel();
		$viewModel->draftOpened = isset($_GET["draft-opened"]);
		$viewModel->draft = $draftTable->getDraft($draftId);
		//$viewModel->form = $form;
	
		return $viewModel;
	}
	
	public function getDraftPlayersAction()
	{
		if(($redirect = $this->initUser()) != NULL) return $redirect;
		
		$draftId = $this->getEvent()->getRouteMatch()->getParam('draft_id');
		
		$sm = $this->getServiceLocator();
		$draftTable = $sm->get('Application\Model\DraftTable');
		$draftPlayerTable = $sm->get('Application\Model\DraftPlayerTable');
		
		$jsonModel = new JsonModel();
		$jsonModel->draft = $draftTable->getDraft($draftId);
		$draftPlayers = array();
		foreach($draftPlayerTable->fetchByDraft($draftId) as $row)
		{
			$draftPlayers[] = $row;
		}
		$jsonModel->draftPlayers = $draftPlayers;
		return $jsonModel;
	}
	
	public function addDraftPlayerAction()
	{
		if(($redirect = $this->initUser()) != NULL) return $redirect;
		
		$draftId = $this->getEvent()->getRouteMatch()->getParam('draft_id');
	
		$sm = $this->getServiceLocator();
		$draftTable = $sm->get('Application\Model\DraftTable');
		$draftPlayerTable = $sm->get('Application\Model\DraftPlayerTable');
	
		$draft = $draftTable->getDraft($draftId);
		if($draft->status != Draft::STATUS_OPEN)
		{
			throw new Exception("Invalid status");
		}
		
		$inviteKey = md5("draftplayer_" . time());
		
		$draftPlayer = new DraftPlayer();
		$draftPlayer->draftId = $draftId;
		$draftPlayer->hasJoined = 0;
		$draftPlayer->inviteKey = $inviteKey;
		$draftPlayerTable->saveDraftPlayer($draftPlayer);
		
		$jsonModel = new JsonModel();
		$jsonModel->draftPlayer = $draftPlayer;
		return $jsonModel;
	}
	
	public function startDraftAction()
	{
		if(($redirect = $this->initUser()) != NULL) return $redirect;
		
		try
		{
			$draftId = $this->getEvent()->getRouteMatch()->getParam('draft_id');
		
			$sm = $this->getServiceLocator();
			$adapter = $sm->get("Zend\Db\Adapter\Adapter");
			$adapter->getDriver()->getConnection()->beginTransaction();
			
			$draftTable = $sm->get('Application\Model\DraftTable');
			$draftPlayerTable = $sm->get('Application\Model\DraftPlayerTable');
			$draftSetTable = $sm->get('Application\Model\DraftSetTable');
			$cardTable = $sm->get('Application\Model\CardTable');
			$pickTable = $sm->get('Application\Model\PickTable');
			
			// Start the draft
			$draft = $draftTable->getDraft($draftId);
			if($draft->status != Draft::STATUS_OPEN)
			{
				throw new Exception("Invalid status");
			}
			
			$draft->status = $draft->gameMode == Draft::MODE_SEALED_DECK ? Draft::STATUS_FINISHED : Draft::STATUS_RUNNING;
			$draftTable->saveDraft($draft); 

			$draftPlayers = $draftPlayerTable->fetchJoinedByDraft($draftId);
			$draftPlayerArray = array();
			foreach($draftPlayers as $draftPlayer)
			{
				$draftPlayerArray[] = $draftPlayer;
			}
			$numberOfPlayers = count($draftPlayerArray);
			
			// Create packs
			if($draft->gameMode == Draft::MODE_BOOSTER_DRAFT || $draft->gameMode == Draft::MODE_SEALED_DECK)
			{
				$packGenerator = new BoosterDraftPackGenerator();
				$draftSets = $draftSetTable->fetchByDraft($draftId);
				$picks = array();
				foreach($draftSets as $setIndex => $draftSet)
				{		
					$cards = $cardTable->fetchBySet($draftSet->setId);
					$cardArray = array();
					foreach($cards as $card)
					{
						$cardArray[] = $card;
					}
					
					$packs = $packGenerator->GeneratePacks($cardArray, $numberOfPlayers);
					foreach($draftPlayerArray as $playerIndex => $player)
					{
						foreach ($packs[$playerIndex] as $card)
						{
							$pick = new Pick();
							$pick->cardId = $card->cardId;
							$pick->startingPlayerId = $player->draftPlayerId;
							$pick->currentPlayerId = $player->draftPlayerId;
							$pick->isPicked = $draft->gameMode == Draft::MODE_SEALED_DECK ? 1 : 0;
							$pick->packNumber = $setIndex + 1;
							$pick->pickNumber = null;
							$pick->zone = Pick::ZONE_MAINDECK;
							$pick->zoneColumn = 0;
							$picks[] = $pick;
						}
					}
				}
			}
			else if($draft->gameMode == Draft::MODE_CUBE_DRAFT)
			{
				$packGenerator = new CubePackGenerator();				
				$draftSet = $draftSetTable->fetchByDraft($draftId)->current();
				$cards = $cardTable->fetchBySet($draftSet->setId);
				
				$cardArray = array();				
				foreach($cards as $card)
				{
					$cardArray[] = $card;
				}
				
				$packs = $packGenerator->GeneratePacks($cardArray, $numberOfPlayers * 3);
				
				$picks = array();
				foreach($draftPlayerArray as $playerIndex => $player)
				{
					for($i = 0; $i < 3; $i++)
					{
						foreach ($packs[$playerIndex * 3 + $i] as $card)
						{
							$pick = new Pick();
							$pick->cardId = $card->cardId;
							$pick->startingPlayerId = $player->draftPlayerId;
							$pick->currentPlayerId = $player->draftPlayerId;
							$pick->isPicked = $draft->gameMode == Draft::MODE_SEALED_DECK ? 1 : 0;
							$pick->packNumber = $i + 1;
							$pick->pickNumber = null;
							$pick->zone = Pick::ZONE_MAINDECK;
							$pick->zoneColumn = 0;
							$picks[] = $pick;
						}
					}
				}
			}
			else if($draft->gameMode == Draft::MODE_CHAOS_DRAFT)
			{
				$packGenerator = new BoosterDraftPackGenerator();
				$draftSets = $draftSetTable->fetchByDraft($draftId);
				$draftSetArray = array();				
				
				$convertedDraftSets = \Application\resultSetToArray($draftSets);
				while(count($draftSetArray) < 3 * $numberOfPlayers)
				{
					foreach($convertedDraftSets as $draftSet)
					{
						$draftSetArray[] = $draftSet;		
					}
					
					if(count($draftSetArray) == 0) throw new \Exception("No sets selected for this draft");
				}
				
				shuffle($draftSetArray);
				
				$picks = array();
				foreach($draftPlayerArray as $playerIndex => $player)
				{
					for($i = 0; $i < 3; $i++)
					{

						$cards = $cardTable->fetchBySet($draftSetArray[$playerIndex * 3 + $i]->setId);
						$pack = $packGenerator->generatePacks($cards, 1)[0];
						
						foreach ($pack as $card)
						{
							$pick = new Pick();
							$pick->cardId = $card->cardId;
							$pick->startingPlayerId = $player->draftPlayerId;
							$pick->currentPlayerId = $player->draftPlayerId;
							$pick->isPicked = $draft->gameMode == Draft::MODE_SEALED_DECK ? 1 : 0;
							$pick->packNumber = $i + 1;
							$pick->pickNumber = null;
							$pick->zone = Pick::ZONE_MAINDECK;
							$pick->zoneColumn = 0;
							$picks[] = $pick;
						}
					}
				}
			}
			
			foreach($picks as $pick)
			{
				$pickTable->savePick($pick);
			}
			
			// Assign order to players
			shuffle($draftPlayerArray);
			foreach($draftPlayerArray as $playerIndex => $draftPlayer)
			{
				$draftPlayer->playerNumber = $playerIndex + 1;
				$draftPlayerTable->saveDraftPlayer($draftPlayer);
			}
			
			$adapter->getDriver()->getConnection()->commit();
			
			$jsonModel = new JsonModel();
			return $jsonModel;
			
		}
		catch(Exception $e)
		{
			$adapter->getDriver()->getConnection()->rollback();
			throw $e;
		}
	}
	
	public function setSetPrivateModeAction()
	{
		if(($redirect = $this->initUser()) != NULL) return $redirect;
	
		$setId = $this->getEvent()->getRouteMatch()->getParam('set_id');
	
		$sm = $this->getServiceLocator();
		$auth = $sm->get('Application\GoogleAuthentication');
		$setTable = $sm->get('Application\Model\SetTable');
		
		$set = $setTable->getSet($setId);
		if($set === null) {
			return $this->notFoundAction();
		}
		
		if($set->userId != $auth->getUser()->userId){
			throw new Exception("You don't own this set.");
		}
		
		$set->isPrivate = isset($_GET["private"]);
		$setTable->saveSet($set);
		
		return $this->redirect()->toRoute('member-area-manage-set', array('set_id' => $setId), array('query' => 'changes-saved'));
	}
	
	public function manageSetAction()
	{
		if(($redirect = $this->initUser()) != NULL) return $redirect;
		
		$sm = $this->getServiceLocator();
		$auth = $sm->get('Application\GoogleAuthentication');
		$setTable = $sm->get('Application\Model\SetTable');
		
		$set = $setTable->getSet($this->getEvent()->getRouteMatch()->getParam('set_id'));
		if($set === null) {
			return $this->notFoundAction();
		}
		
		if($set->userId != $auth->getUser()->userId){
			throw new Exception("You don't own this set.");
		}
		
		$form = new \Application\Form\CreateSetForm();
		$form->setAttribute('action', $this->url()->fromRoute('member-area-manage-set', array('set_id' => $set->setId)));		
		
		$uploadForm = new \Application\Form\UploadCardsForm();
		$uploadForm->setAttribute('action', $this->url()->fromRoute('member-area-manage-set', array('set_id' => $set->setId), array('fragment' => 'upload_tab')));
		
		if ($this->getRequest()->isPost())
		{
			$formData = array_merge_recursive(
            	$this->getRequest()->getPost()->toArray(),
            	$this->getRequest()->getFiles()->toArray()
        	);
			
			if(isset($formData["submit"]))
			{
				// Set properties form
				$inputFilter = $set->getInputFilter();
				//$inputFilter->remove('name');
				$form->setInputFilter($inputFilter);
				
				$form->setData($formData);
				
				if ($form->isValid($formData))
				{
					$set->name = $formData["name"];
					$set->urlName = $formData["url_name"];
					$set->code = $formData["code"];
					$set->about = $formData["about"];
					$setTable->saveSet($set);
				
					return $this->redirect()->toRoute('member-area-manage-set', array('set_id' => $set->setId), array('query' => 'changes-saved'));
				}
				else
				{
					//var_dump($form->getMessages());
				}
			}
			else if(isset($formData["submit_upload"]))
			{
				$uploadForm->setInputFilter($form->getInputFilter());
				
				$uploadForm->setData($formData);
				
				if ($uploadForm->isValid($formData)) 
				{
					$artUrl = $formData["art_url"];
					
					$fileContents = file_get_contents($this->getRequest()->getFiles('file')["tmp_name"]);
					
					// Get cards from the current version so that we can compare the uploaded file against it
					$cardTable = $sm->get('Application\Model\CardTable');
					$currentVersionCards = $cardTable->fetchBySetVersion($set->currentSetVersionId);
					$currentVersionCardArray = array();
					foreach($currentVersionCards as $card)
					{
						$currentVersionCardArray[$card->name] = $card;
					}
					
					try 
					{
						$parser = new \Application\SetParser\IsochronDrafterSetParser();
						$cards = $parser->Parse($fileContents);
						
						foreach($cards as $card)
						{
							$cardName = preg_replace("/[^\p{L}0-9- ]/iu", "", $card->name);
							switch($formData["art_url_format"])
							{
								case UploadCardsForm::NAME_DOT_PNG:
									$card->artUrl = $artUrl . "/" . $cardName . ".png";
									break;
								case UploadCardsForm::NAME_DOT_FULL_DOT_PNG:
									$card->artUrl = $artUrl . "/" . $cardName . ".full.png";
									break;
								case UploadCardsForm::NAME_DOT_JPG:
									$card->artUrl = $artUrl . "/" . $cardName . ".jpg";
									break;
								case UploadCardsForm::NAME_DOT_FULL_DOT_JPG:
									$card->artUrl = $artUrl . "/" . $cardName . ".full.jpg";
									break;
								default:
									throw new \Exception("Invalid art URL format.");
							}
							
							//var_dump($currentVersionCardArray[$card->name]->isNewVersionChanged($card));
							$card->isChanged = isset($currentVersionCardArray[$card->name]) ? $currentVersionCardArray[$card->name]->isNewVersionChanged($card) : true;
							$card->firstVersionCardId = isset($currentVersionCardArray[$card->name]) ? $currentVersionCardArray[$card->name]->firstVersionCardId : NULL;
							$card->changedOn = $card->isChanged ? date("Y-m-d H:i:s") : $currentVersionCardArray[$card->name]->changedOn;
						}
						
						//die();
	
						$guid = \uniqid();
						
						$_SESSION["card_file_cards"] = serialize($cards);
						$_SESSION["card_file_guid"] = $guid;
				
						return $this->redirect()->toRoute('member-area-manage-set', array('action' => 'create-set-version', 'set_id' => $set->setId), array('query' => array('upload' => $guid)));
					}
					catch (\Exception $e)
					{
						$uploadForm->get("file")->setMessages(array($e->getMessage()));
					}
				} 
				else 
				{
					//var_dump($form->getMessages());
				}
			}	
		}
		
		if(!isset($formData["submit"]))
		{
			$form->setData($set->getArray());
		}

		$setVersionTable = $sm->get('Application\Model\SetVersionTable');
		$setVersions = $setVersionTable->fetchBySet($set->setId);
		
		$viewModel = new ViewModel();
		$viewModel->setCreated = isset($_GET['set-created']);
		$viewModel->changesSaved = isset($_GET['changes-saved']);
		$viewModel->setVersionCreated = isset($_GET['set-version-created']);
		$viewModel->setVersions = $setVersionTable->getSetVersionsBySet($set->setId);
		
		$viewModel->set = $set;		
		$viewModel->form = $form;
		$viewModel->uploadForm = $uploadForm;
		$viewModel->driveAppId = $this->getServiceLocator()->get('Config')['auth']['driveAppId'];
		$viewModel->accessToken = $_SESSION['access_token'];
		return $viewModel;
	}
	
	public function setSetStatusAction()
	{
		if(($redirect = $this->initUser()) != NULL) return $redirect;
		
		$setId = $this->getEvent()->getRouteMatch()->getParam('set_id');
	
		$sm = $this->getServiceLocator();
		$auth = $sm->get('Application\GoogleAuthentication');
		$setTable = $sm->get('Application\Model\SetTable');
		
		if(!isset($_GET["status"]) || $_GET["status"] < Set::STATUS_UNPLAYABLE || $_GET["status"] > Set::STATUS_DISCONTINUED)
		{
			throw new \Exception("Set not set");
		}
		
		$set = $setTable->getSet($setId);
		if($set === null) {
			return $this->notFoundAction();
		}
		
		if($set->userId != $auth->getUser()->userId){
			throw new Exception("You don't own this set.");
		}
		
		$set->status = $_GET["status"];
		$setTable->saveSet($set);
		
		$this->redirect()->toRoute('member-area-manage-set', array('set_id' => $setId), array('fragment' => 'status_tab', 'query' => 'changes-saved'));
	}
	
	public function createSetVersionAction()
	{
		if(($redirect = $this->initUser()) != NULL) return $redirect;
	
		$setId = $this->getEvent()->getRouteMatch()->getParam('set_id');
	
		$sm = $this->getServiceLocator();
		$auth = $sm->get('Application\GoogleAuthentication');
		$setTable = $sm->get('Application\Model\SetTable');

		$set = $setTable->getSet($setId);
		if($set === null) {
			return $this->notFoundAction();
		}
		
		if($set->userId != $auth->getUser()->userId){
			throw new Exception("You don't own this set.");
		}
		
		if(!isset($_GET["upload"]) || $_GET["upload"] != $_SESSION["card_file_guid"])
		{
			$this->redirect()->toRoute('member-area-manage-set', array('set_id' => $setId), array('fragment' => 'upload_tab', 'query' => 'upload-expired'));
		}
	
		$cards = unserialize($_SESSION["card_file_cards"]);
		
		$sm = $this->getServiceLocator();
		$setVersionTable = $sm->get('Application\Model\SetVersionTable');
		$adapter = $sm->get("Zend\Db\Adapter\Adapter");
		
		$form = new \Application\Form\CreateSetVersionForm();
		
		$cardTable = $sm->get('Application\Model\CardTable');
		

		// Get cards from the previous version so that we can compare the uploaded file against it
		$previousSetVersion = null;
		$previousVersionCards = null;
		if($set->currentSetVersionId != null){
			$previousSetVersion = $setVersionTable->getSetVersion($set->currentSetVersionId);
			$previousVersionCards = $cardTable->fetchBySetVersion($set->currentSetVersionId);
		}
		
		if ($this->getRequest()->isPost())
		{
			$formData = array_merge_recursive(
					$this->getRequest()->getPost()->toArray(),
					$this->getRequest()->getFiles()->toArray()
			);
				
			$setVersion = $sm->get('Application\Model\SetVersion');
			$setVersion->setId = $set->setId;
			$form->setInputFilter($setVersion->getInputFilter());
				
			$form->setData($formData);
				
			if ($form->isValid($formData))
			{
				$adapter->getDriver()->getConnection()->beginTransaction();
		
				try
				{
					$setVersion->name = $formData["name"];
					$setVersion->urlName = $formData["url_name"];
					$setVersion->downloadUrl = $formData["download_url"];
					$setVersion->about = $formData["about"];
					//$setVersion->createdOn = $formData["about"];
						
					$setVersionTable->saveSetVersion($setVersion);

					$set->currentSetVersionId = $setVersion->setVersionId;
					$setTable->saveSet($set);
					
					foreach($cards as $card)
					{
						$card->setVersionId = $setVersion->setVersionId;

						$cardTable->saveCard($card);
					}
		
					unset($_SESSION["card_file_guid"]);
					unset($_SESSION["card_file_cards"]);
					
					$adapter->getDriver()->getConnection()->commit();
				}
				catch(Exception $e)
				{
					$adapter->getDriver()->getConnection()->rollback();
					throw $e;
				}
		
				return $this->redirect()->toRoute('member-area-manage-set', array('set_id' => $set->setId), array('query' => 'set-version-created'));
			}
			else
			{
			}
		}
		else 
		{
			$previousSetVersionCount = count($setVersionTable->fetchBySet($set->setId));
			$form->setData(array(
					'name' => "Version " . ($previousSetVersionCount + 1),
					'url_name' => "version-" . ($previousSetVersionCount + 1)
			));
			$form->setData(array('name' => "Version " . (count($setVersionTable->fetchBySet($set->setId)) + 1)));
			
			if($previousSetVersionCount > 0)
			{
				$addedCards = array();
				$changedCards = array();
				$removedCards = array();
				$cardArray = array();
				foreach($cards as $card)
				{
					if($card->isChanged)
					{
						if($card->firstVersionCardId == NULL)
						{
							$addedCards[] = $card;
						}
						else 
						{
							$changedCards[] = $card;
						}
					}
					
					$cardArray[$card->name] = $card;
				}
				
				foreach($previousVersionCards as $previousVersionCard)
				{
					if(!isset($cardArray[$previousVersionCard->name]))
					{
						$removedCards[] = $previousVersionCard;
					}
				}
				
				if(count($addedCards) > 0 || count($removedCards) > 0 || count($changedCards) > 0)
				{
					$changeLog = "Change log:\n\n";
					foreach($addedCards as $card)
					{
						$changeLog .= "* Added [[" . $card->name . "]].\n";
					}
					
					foreach($removedCards as $card)
					{
						$changeLog .= "* Removed [[" . $set->urlName . ":" . $previousSetVersion->urlName . ":" . $card->name . "]].\n";
					}
					
					foreach($changedCards as $card)
					{
						$changeLog .= "* Changed [[" . $card->name . "]].\n";
					}
					
				}
				else
				{
					$changeLog = "This update didn't change any cards.";	
				}
				
				$changeLog = trim($changeLog);
				
				$form->setData(array('about' => $changeLog));
			}
			else {
				$form->setData(array('about' => "This is the first version of " . $set->name . " published on WebDrafter."));
			}
		}
		
		$viewModel = new ViewModel();
		
		$viewModel->set = $set;
		$viewModel->cards = $cards;
		$viewModel->form = $form;	
		$viewModel->uploadGuid = $_GET["upload"];
		return $viewModel;
	}
	
	public function manageSetVersionAction()
	{
		if(($redirect = $this->initUser()) != NULL) return $redirect;
	
		$sm = $this->getServiceLocator();
		$auth = $sm->get('Application\GoogleAuthentication');
		$setTable = $sm->get('Application\Model\SetTable');
		$setVersionTable = $sm->get('Application\Model\SetVersionTable');
	
		$set = $setTable->getSet($this->getEvent()->getRouteMatch()->getParam('set_id'));
		if($set === null) {
			return $this->notFoundAction();
		}
		
		$setVersion = $setVersionTable->getSetVersion($this->getEvent()->getRouteMatch()->getParam('set_version_id'));
		if($setVersion === null) {
			return $this->notFoundAction();
		}
	
		if($set->setId != $setVersion->setId)
		{
			return $this->notFoundAction();
		}
		
		if($set->userId != $auth->getUser()->userId){
			throw new Exception("You don't own this set.");
		}
	
		$form = new \Application\Form\CreateSetVersionForm();
		$form->setAttribute('action', $this->url()->fromRoute('member-area-manage-set-version', array('set_id' => $set->setId, 'set_version_id' => $setVersion->setVersionId)));

		if ($this->getRequest()->isPost())
		{
			$formData = $this->getRequest()->getPost()->toArray();
				
			if(isset($formData["submit"]))
			{
				// Set properties form
				$inputFilter = $setVersion->getInputFilter();
				//$inputFilter->remove('name');
				$form->setInputFilter($inputFilter);
	
				$form->setData($formData);
	
				if ($form->isValid($formData))
				{
					$setVersion->name = $formData["name"];
					$setVersion->urlName = $formData["url_name"];
					$setVersion->about = $formData["about"];
	
					$setVersionTable->saveSetVersion($setVersion);
	
					return $this->redirect()->toRoute('member-area-manage-set-version', array('set_id' => $setVersion->setId, 'set_version_id' => $setVersion->setVersionId), array('query' => 'changes-saved'));
				}
				else
				{
					//var_dump($form->getMessages());
				}
			}
		}
		else
		{
			$form->setData($setVersion->getArray());
		}
	
		$viewModel = new ViewModel();
		$viewModel->changesSaved = isset($_GET['changes-saved']);
	
		$viewModel->set = $set;
		$viewModel->setVersion = $setVersion;
		$viewModel->form = $form;
		return $viewModel;
	}
}
?>