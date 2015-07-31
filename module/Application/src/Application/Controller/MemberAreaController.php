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

class MemberAreaController extends AbstractActionController
{
	private $googleClient;
	
	private function initUser()
	{
		if(!isset($_SESSION['user_id']))
		{
			$this->redirect()->toRoute('member-area', array('action' => 'login'));
			//throw new \Exception("Must be logged in to access this page");
		}		
		
		$this->googleClient = $this->createClient();
		$this->googleClient->setAccessToken($_SESSION["access_token"]);
		
		if ($this->googleClient->isAccessTokenExpired()) {
			$refreshToken = $this->googleClient->getRefreshToken();
			if($refreshToken == null){
				session_destroy();
				$this->redirect()->toRoute('member-area', array('action' => 'login'));
			}
			
			$this->googleClient->refreshToken($refreshToken);
			//file_put_contents($credentialsPath, $client->getAccessToken());
		}
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
		$this->initUser();
		
		$sm = $this->getServiceLocator();
		$draftTable = $sm->get('Application\Model\DraftTable');
		$setTable = $sm->get('Application\Model\SetTable');
		
		$viewModel = new ViewModel();
		
		$viewModel->setCreated = isset($_GET["set-created"]);
		$viewModel->setRetired = isset($_GET["set-retired"]);
		$viewModel->draftsHosted = $draftTable->fetchByHost($_SESSION["user_id"]);
		$viewModel->draftsPlayed = $draftTable->getPastDraftsByUser($_SESSION["user_id"]);
		$viewModel->setsOwned = $setTable->fetchByUser($_SESSION["user_id"]);
		
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
		$this->initUser();
		
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
					$set->code = $formData["code"];
					$set->url = $formData["url"];
					$set->downloadUrl = $formData["download_url"];
					$set->userId = $_SESSION["user_id"];
					$set->isRetired = 0;
					
					$setTable = $sm->get('Application\Model\SetTable');
					$setTable->saveSet($set);
					
					$artUrl = $formData["art_url"];
					
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
					}

					$adapter->getDriver()->getConnection()->commit();
				}
				catch(Exception $e)
				{
					$adapter->getDriver()->getConnection()->rollback();
					throw $e;
				}
								
				return $this->redirect()->toRoute('member-area', array(), array('query' => 'set-created'));
			} 
			else 
			{
				//var_dump($form->getMessages());
			}
		}
		
		$viewModel = new ViewModel();
		$viewModel->driveAppId = $this->getServiceLocator()->get('Config')['auth']['driveAppId'];
		$viewModel->accessToken = $_SESSION['access_token'];
		$viewModel->form = $form;
		
		return $viewModel;
	}
	
	public function selectGameModeAction()
	{
		$this->initUser();
		return new ViewModel();
	}
	
	public function hostDraftAction()
	{
		$this->initUser();
		
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
					$draft->name = join("/", $setCodes) . " " . $modeName . " on " . date("r").
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
		$this->initUser();
		
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
		$this->initUser();
		
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
		$this->initUser();
		
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
		$this->initUser();
		
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
	
	public function retireSetAction()
	{
		$this->initUser();
	
		if(!isset($_GET["set_id"]) || strlen($_GET["set_id"]) < 1)
		{
			throw new \Exception("Set not set");
		}
		
		$sm = $this->getServiceLocator();
		$setTable = $sm->get('Application\Model\SetTable');
	
		$set = $setTable->getSet($_GET["set_id"]);
		
		if($_SESSION["user_id"] != $set->userId)
		{
			throw new \Exception("You don't own this set.");			
		}
		
		$set->isRetired = 1;
		$setTable->saveSet($set);

		return $this->redirect()->toRoute('member-area', array(), array('query' => 'set-retired'));
	}
	
	public function googleDriveAction()
	{
		
		die();
	}
}
?>