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
use Application\Model\DraftSetVersion;
use Zend\View\Model\JsonModel;
use Application\Model\DraftPlayer;
use Application\Model\User;
use Application\PackGenerator\BoosterDraftPackGenerator;
use Application\PackGenerator\CubePackGenerator;
use Application\Form\CreateSetForm;
use Application\GoogleAuthentication;
use Application\ChallongeAPI;
use Application\Form\UploadCardsForm;

function rmdir_recursive($dir) {
	$result = true;
	foreach(scandir($dir) as $file) {
		if ('.' === $file || '..' === $file) continue;
		if (is_dir("$dir/$file")) $result &= rmdir_recursive("$dir/$file");
		else $result &= unlink("$dir/$file");
	}
	$result &= rmdir($dir);
	return $result;
}

class MemberAreaController extends WebDrafterControllerBase
{
	protected function isAuthRequired(\Zend\Mvc\MvcEvent $e)
	{
		$action = $e->getRouteMatch()->getParam('action');
		if($action == 'logout' || $action == 'login')
		{
			return false;
		}
		return true;
	}
	
	protected function isUnregisteredAllowed(\Zend\Mvc\MvcEvent $e)
	{
		$action = $e->getRouteMatch()->getParam('action');
		if($action == 'register')
		{
			return true;
		}
		return false;
	}
	
	private function processSetUpload($set, $artUrlFormat, $uploadId, $dir, $parser){
		$setFile = $dir . 'set';
		
		if(!file_exists($setFile)){
			throw new \Exception("Set file not uploaded. Add a single text file.");
		}
		
		$fileContents = file_get_contents($setFile);
		
		// Get cards from the current version so that we can compare the uploaded file against it
		$sm = $this->getServiceLocator();
		$cardTable = $sm->get('Application\Model\CardTable');
		$currentVersionCards = $cardTable->fetchBySetVersion($set->currentSetVersionId);
		$currentVersionCardArray = array();
		foreach($currentVersionCards as $card)
		{
			$currentVersionCardArray[$card->name] = $card;
		}
		
		$cards = $parser->Parse($fileContents);
		
		if(count($cards) > 1000){
			throw new \Exception("Maximum allowed number of cards in a set is 1000.");
		}
		
		foreach($cards as $card)
		{
			if(!file_exists($dir . "/" . $card->imageName)){
				throw new \Exception("Image for card \"" . $card->name . "\" is missing, file named \"" . $cardArtName . "\" was expected.");
			}
				
			$card->artUrl = $card->imageName; // This is not a full URL yet
				
			//var_dump($currentVersionCardArray[$card->name]->isNewVersionChanged($card));
			$card->isChanged = isset($currentVersionCardArray[$card->name]) ? $currentVersionCardArray[$card->name]->isNewVersionChanged($card) : true;
			$card->firstVersionCardId = isset($currentVersionCardArray[$card->name]) ? ($currentVersionCardArray[$card->name]->firstVersionCardId != NULL ? $currentVersionCardArray[$card->name]->firstVersionCardId : $currentVersionCardArray[$card->name]->cardId) : NULL;
			$card->changedOn = $card->isChanged ? date("Y-m-d H:i:s") : $currentVersionCardArray[$card->name]->changedOn;
		}
		
		//die();
		
		$_SESSION["card_file_cards"] = serialize($cards);
		$_SESSION["upload_id"] = $uploadId;
		$_SESSION["upload_dir"] = $dir;
		return $this->redirect()->toRoute('member-area-manage-set', array('action' => 'create-set-version', 'set_id' => $set->setId), array('query' => array('upload_id' => $uploadId)));
	}
	
	public function keepAliveAction(){
		return new JsonModel();
	}
	
	public function indexAction()
	{	
		$sm = $this->getServiceLocator();
		$auth = $this->auth();
		$draftTable = $sm->get('Application\Model\DraftTable');
		$setTable = $sm->get('Application\Model\SetTable');
		
		$adapter = $sm->get("Zend\Db\Adapter\Adapter");
		
		$form = new \Application\Form\RegistrationForm(false);
		$form->setAttribute('action', $this->url()->fromRoute('member-area', array('action' => 'index'), array('fragment' => 'account_settings_tab')));
		
		$user = $auth->getUser();
		if ($this->getRequest()->isPost())
		{
			$formData = $this->getRequest()->getPost()->toArray();
			
			$userTable = $sm->get('Application\Model\UserTable');
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
		$viewModel->user = $user;
		
		return $viewModel;
	}
	
	public function registerAction()
	{
		if($_SESSION["not_registered"] != true){
			return $this->redirect()->toRoute('member-area');
		}
		
		$sm = $this->getServiceLocator();
		$auth = $this->auth();
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
		$client = $this->auth()->getGoogleClient();
		
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
				$_SESSION["user_id"] = $user->userId;
			}
			else
			{
				$_SESSION["user_id"] = $user->userId;
			}

			$user->refreshToken = $client->getRefreshToken(); // Presunout do prvni casti ifu vyse
			$_SESSION["refresh_token"] = $user->refreshToken;
			
			$userTable->saveUser($user);
			
			if($user->name === null)
			{
				$_SESSION["not_registered"] = true;
				$this->redirect()->toRoute('member-area', array('action' => 'register'));	
			}
			else {
				$_SESSION["not_registered"] = false;
			}
			
			if(isset($_GET["state"])){
				$this->redirect()->toUrl($_GET["state"]);
			}
			else {
				$this->redirect()->toRoute('member-area');
			}	
		}
		else if(isset($_SESSION['access_token']))
		{
			$client->setAccessToken($_SESSION['access_token']);
			
			if(isset($_GET["return"])){
				$this->redirect()->toUrl($_GET["return"]);
			}
			else {
				$this->redirect()->toRoute('member-area');
			}	
		}
		else {
			session_destroy();
			header("Location: " . $client->createAuthUrl());
			exit;
		}
		
		if ($client->isAccessTokenExpired()) {
			//$_SESSION["return_url"] = $_GET["return"];
			$client->refreshToken($client->getRefreshToken());
		}
		
		return new ViewModel();
	}
	
	public function logoutAction()
	{
	 	session_destroy();

		return $this->redirect()->toRoute('home');
	}
	
	public function createSetAction()
	{
		$sm = $this->getServiceLocator();
		$adapter = $sm->get("Zend\Db\Adapter\Adapter");
		
		$form = new \Application\Form\CreateSetForm();		
	
		if ($this->getRequest()->isPost()) 
		{
			$formData = array_merge_recursive(
            	$this->getRequest()->getPost()->toArray(),
            	$this->getRequest()->getFiles()->toArray()
        	);
				
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
		return new ViewModel();
	}
	
	public function hostDraftAction()
	{
		if(!isset($_REQUEST["mode"]) || (int)$_REQUEST["mode"] < 1)
		{
			throw new \Exception("Game mode not set");
		}
		
		$mode = (int)@$_REQUEST["mode"];
		$rarityMode = (int)@$_REQUEST["rarity_mode"];
		
		$sm = $this->getServiceLocator();
		$setTable = $sm->get('Application\Model\SetTable');	
		$setVersionTable = $sm->get('Application\Model\SetVersionTable');		
		$draftSetVersionTable = $sm->get('Application\Model\DraftSetVersionTable');	
	
		if (isset($_POST["setVersionIds"]))
		{
			$setVersionIds = explode(",", $_POST["setVersionIds"]);
			
			if(count($setVersionIds) < 1)
			{
				throw new \Exception("No sets selected");	
			}
			
			$sm = $this->getServiceLocator();
			$adapter = $sm->get("Zend\Db\Adapter\Adapter");
			$adapter->getDriver()->getConnection()->beginTransaction();

			try
			{
				$setTable = $sm->get('Application\Model\SetTable');
			
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
				$setVersions = array();
				$setCodes = array();					
				foreach($setVersionIds as $setVersionId)
				{
					$setVersion = $setVersionTable->getSetVersion($setVersionId);
					$setVersions[] = $setVersion;
					
					$set = $setTable->getSet($setVersion->setId);					
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
				$draft->rarityMode = $rarityMode;
					
				$draftTable = $sm->get('Application\Model\DraftTable');
				$draftTable->saveDraft($draft);
					
				$draftSetTable = $sm->get('Application\Model\DraftSetVersionTable');
				foreach($setVersionIds as $index => $setVersionId)
				{
					$draftSetVersion = new DraftSetVersion();
					$draftSetVersion->draftId = $draft->draftId;
					$draftSetVersion->setVersionId = $setVersionId;
					$draftSetVersion->packNumber = $index + 1;
					$draftSetVersionTable->saveDraftSetVersion($draftSetVersion);
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
	
		$viewModel = new ViewModel();
		$viewModel->sets = $setTable->getSetsToHost($_SESSION["user_id"]);
		$viewModel->mode = $mode;
	
		return $viewModel;
	}
	
	public function draftAdminAction()
	{	
		$draftId = $this->getEvent()->getRouteMatch()->getParam('draft_id');
		
		$sm = $this->getServiceLocator();
		$draftTable = $sm->get('Application\Model\DraftTable');
		$auth = $this->auth();
		
		$viewModel = new ViewModel();
		$viewModel->draftOpened = isset($_GET["draft-opened"]);
		$viewModel->playerKicked = isset($_GET["player-kicked"]);
		$viewModel->draft = $draftTable->getDraft($draftId);
		
		if($viewModel->draft->hostId != $auth->getUser()->userId)
		{
			throw new Exception("Unauthorized");
		}
		
		//$viewModel->form = $form;
	
		return $viewModel;
	}
	
	public function getDraftPlayersAction()
	{
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
		$isAi = (int)$_GET["is_ai"];	
		$draftId = $this->getEvent()->getRouteMatch()->getParam('draft_id');
	
		$sm = $this->getServiceLocator();
		$draftTable = $sm->get('Application\Model\DraftTable');
		$draftPlayerTable = $sm->get('Application\Model\DraftPlayerTable');
		$auth = $this->auth();
	
		$draft = $draftTable->getDraft($draftId);
		if($draft->status != Draft::STATUS_OPEN)
		{
			throw new Exception("Invalid status");
		}
		
		if($draft->hostId != $auth->getUser()->userId)
		{
			throw new Exception("Unauthorized");
		}
		
		$inviteKey = md5("draftplayer_" . time());
		
		// Count AI players
		$aiPlayers = 0;
		$draftPlayers = $draftPlayerTable->fetchJoinedByDraft($draftId);
		foreach($draftPlayers as $draftPlayer){
			if($draftPlayer->isAi == 1) $aiPlayers++;
		}
		
		$draftPlayer = new DraftPlayer();
		$draftPlayer->draftId = $draftId;
		$draftPlayer->hasJoined = $isAi ? 1 : 0;
		$draftPlayer->inviteKey = $isAi ? null : $inviteKey;
		$draftPlayer->name = $isAi ? "AI Player #" . ($aiPlayers + 1) : null;
		$draftPlayer->isAi = $isAi;
		$draftPlayerTable->saveDraftPlayer($draftPlayer);
		
		$jsonModel = new JsonModel();
		$jsonModel->draftPlayer = $draftPlayer;
		return $jsonModel;
	}
	
	public function startDraftAction()
	{
		try
		{
			$draftId = $this->getEvent()->getRouteMatch()->getParam('draft_id');
		
			$sm = $this->getServiceLocator();
			$adapter = $sm->get("Zend\Db\Adapter\Adapter");
			$adapter->getDriver()->getConnection()->beginTransaction();
			
			$draftTable = $sm->get('Application\Model\DraftTable');
			$draftPlayerTable = $sm->get('Application\Model\DraftPlayerTable');
			$draftSetVersionTable = $sm->get('Application\Model\DraftSetVersionTable');
			$cardTable = $sm->get('Application\Model\CardTable');
			$pickTable = $sm->get('Application\Model\PickTable');
			$setVersionTable = $sm->get('Application\Model\SetVersionTable');
			$auth = $this->auth();
			
			// Start the draft
			$draft = $draftTable->getDraft($draftId);
			if($draft->status != Draft::STATUS_OPEN)
			{
				throw new Exception("Invalid status");
			}
			
			if($draft->hostId != $auth->getUser()->userId) 
			{
				throw new Exception("Unauthorized");				
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
			
			$allowedRarities = array();
			switch($draft->rarityMode)
			{
				case Draft::RARITY_MODE_MRUC:
					$allowedRarities[] = 'M';
				case Draft::RARITY_MODE_RUC:
					$allowedRarities[] = 'R';
				case Draft::RARITY_MODE_UC:
					$allowedRarities[] = 'U';
				case Draft::RARITY_MODE_C:
					$allowedRarities[] = 'C';
					break;
				default:
					throw new \Exception("Invalid rarity mode " . $draft->rarityMode);
			}
			// Create packs
			if($draft->gameMode == Draft::MODE_BOOSTER_DRAFT || $draft->gameMode == Draft::MODE_SEALED_DECK)
			{
				$packGenerator = new BoosterDraftPackGenerator();
				$draftSetVersions = $draftSetVersionTable->fetchByDraft($draftId);
				$picks = array();
				foreach($draftSetVersions as $setIndex => $draftSetVersion)
				{		
					$setVersion = $setVersionTable->getSetVersion($draftSetVersion->setVersionId);
					
					$cards = $cardTable->fetchBySetVersion($draftSetVersion->setVersionId);
					$cardArray = array();
					foreach($cards as $card)
					{
						if(in_array($card->rarity, $allowedRarities)){
							$cardArray[] = $card;
						}
					}
					
					$packs = $packGenerator->GeneratePacks($cardArray, $numberOfPlayers, $setVersion->basicLandSlot, $setVersion->basicLandSlotNeedle);
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
				$draftSetVersions = $draftSetVersionTable->fetchByDraft($draftId);
				$draftSetVersion = $draftSetVersions->current();
				$cards = $cardTable->fetchBySetVersion($draftSetVersion->setVersionId);
				
				$cardArray = array();				
				foreach($cards as $card)
				{
					//if(in_array($card->rarity, $allowedRarities)){
						$cardArray[] = $card;
					//}
				}
				
				$packs = $packGenerator->GeneratePacks($cardArray, $numberOfPlayers * count($draftSetVersions));
				
				$picks = array();
				foreach($draftPlayerArray as $playerIndex => $player)
				{
					for($i = 0; $i < count($draftSetVersions); $i++)
					{
						foreach ($packs[$playerIndex * count($draftSetVersions) + $i] as $card)
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
				$draftSetVersions = $draftSetVersionTable->fetchByDraft($draftId);
				$draftSetVersionArray = array();				
				
				$convertedDraftSetVersions = \Application\resultSetToArray($draftSetVersions);
				while(count($draftSetVersionArray) < count($draftSetVersions) * $numberOfPlayers)
				{
					foreach($convertedDraftSetVersions as $draftSetVersion)
					{
						$draftSetVersionArray[] = $draftSetVersion;		
					}
					
					if(count($draftSetVersionArray) == 0) throw new \Exception("No sets selected for this draft");
				}
				
				shuffle($draftSetVersionArray);
				
				$picks = array();
				foreach($draftPlayerArray as $playerIndex => $player)
				{
					for($i = 0; $i < 3; $i++)
					{
						$setVersionId = $draftSetVersionArray[$playerIndex * 3 + $i]->setVersionId;
						$setVersion = $setVersionTable->getSetVersion($draftSetVersion->setVersionId);
						
						$cards = $cardTable->fetchBySetVersion($setVersionId);
						$pack = $packGenerator->generatePacks($cards, 1, $setVersion->basicLandSlot, $setVersion->basicLandSlotNeedle)[0];
						
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
		$setId = $this->getEvent()->getRouteMatch()->getParam('set_id');
	
		$sm = $this->getServiceLocator();
		$auth = $this->auth();
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
		$uploadErrorMessages = null;
		
		$sm = $this->getServiceLocator();
		$config = $this->getServiceLocator()->get('Config');
		$auth = $this->auth();
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
		$uploadForm->setAttribute('action', $this->url()->fromRoute('member-area-manage-set', array('set_id' => $set->setId), array('fragment' => 'upload')));
		
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
				//$uploadForm->setInputFilter($form->getInputFilter());
				
				$uploadForm->setData($formData);
				//if ($uploadForm->isValid($formData)) 
				{
					try
					{

						$dataDir = $config["data_dir"];
						$userId = $this->auth()->getUser()->userId;
						$uploadId = (int)$formData["upload_id"];
						$ds = DIRECTORY_SEPARATOR;
						$setDir = $dataDir . $userId . $ds . "temp" . $ds . $uploadId . $ds;
						return $this->processSetUpload($set, $uploadId, $setDir, new \Application\SetParser\IsochronDrafterSetParser($formData["art_url_format"]));
					}
					catch (\Exception $e)
					{
						//die($e->getMessage());
						$uploadErrorMessages = array($e->getMessage());
					}
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
		$viewModel->uploadErrorMessages = $uploadErrorMessages;
		return $viewModel;
	}
	
	public function setSetStatusAction()
	{
		$setId = $this->getEvent()->getRouteMatch()->getParam('set_id');
	
		$sm = $this->getServiceLocator();
		$auth = $this->auth();
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
		$setId = $this->getEvent()->getRouteMatch()->getParam('set_id');
	
		$sm = $this->getServiceLocator();
		$auth = $this->auth();
		$userId = $this->auth()->getUser()->userId;
		$config = $this->getServiceLocator()->get('Config');
		$setTable = $sm->get('Application\Model\SetTable');

		$set = $setTable->getSet($setId);
		if($set === null) {
			return $this->notFoundAction();
		}
		
		if($set->userId != $auth->getUser()->userId){
			throw new Exception("You don't own this set.");
		}
		
		if(!isset($_GET["upload_id"]) || $_GET["upload_id"] != $_SESSION["upload_id"] || (int)$_GET["upload_id"] == 0)
		{
			$this->redirect()->toRoute('member-area-manage-set', array('set_id' => $setId), array('fragment' => 'upload', 'query' => 'upload-expired'));
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
			$formData = $this->getRequest()->getPost()->toArray();
		
			$setVersion = $sm->get('Application\Model\SetVersion');
			$setVersion->setId = $set->setId;
			$form->setInputFilter($setVersion->getInputFilter());
				
			$form->setData($formData);
				
			if ($form->isValid($formData))
			{
				$adapter->getDriver()->getConnection()->beginTransaction();

				$uploadId = (int)$_GET["upload_id"];
				$dataDir = $config["data_dir"];
					
				$ds = DIRECTORY_SEPARATOR;
					
				// Validate that the temp dir exists for this upload (it's consistency should be guaranteed by the previous step, which is in turn guaranteed by the session key)
				$tempSetDir = $_SESSION["upload_dir"];
				if(!is_dir($tempSetDir)){
					$this->redirect()->toRoute('member-area-manage-set', array('set_id' => $setId), array('fragment' => 'upload', 'query' => 'upload-expired'));
				}
				
				try
				{
					$setVersion->name = $formData["name"];
					$setVersion->urlName = $formData["url_name"];
					$setVersion->downloadUrl = $formData["download_url"];
					$setVersion->about = $formData["about"];
					$setVersion->basicLandSlot = $formData["basic_land_slot"];
					$setVersion->basicLandSlotNeedle = $formData["basic_land_slot_needle"];
					//$setVersion->createdOn = $formData["about"];
						
					$setVersionTable->saveSetVersion($setVersion);
					
					$set->currentSetVersionId = $setVersion->setVersionId;
					$setTable->saveSet($set);

					$finalSetDir = $dataDir . $userId . $ds . $setVersion->setVersionId . $ds;
					if(!mkdir($finalSetDir, 0777, true)){
						throw new \Exception("Could not create dir \"$finalSetDir\" for the set.");
					}
					
					if(!rename($tempSetDir . 'set', $finalSetDir . 'set')){
						throw new \Exception("Could not move set file to the final location.");
					}	
					
					foreach($cards as $card)
					{
						if(!rename($tempSetDir . $card->artUrl, $finalSetDir . $card->artUrl)){
							throw new \Exception("Could not move file \"" . $card->artUrl . "\" to the final location.");;
						}
						$card->artUrl = "/upload/" . $userId . "/" . $setVersion->setVersionId ."/" . rawurlencode($card->artUrl);
						$card->setVersionId = $setVersion->setVersionId;

						$cardTable->saveCard($card);
					}
		
					if(!rmdir_recursive($dataDir . $userId . $ds . "temp" . $ds)){
						throw new \Exception("Could not delete the temp dir.");
					}
					
					$adapter->getDriver()->getConnection()->commit();
				}
				catch(Exception $e)
				{
					$adapter->getDriver()->getConnection()->rollback();
					throw $e;
				}

				unset($_SESSION["upload_id"]);
				unset($_SESSION["card_file_cards"]);
		
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
				$form->setData(array('about' => "This is the first version of " . $set->name . " published on PlaneSculptors.net."));
			}
		}
		
		$viewModel = new ViewModel();
		
		$viewModel->set = $set;
		$viewModel->cards = $cards;
		$viewModel->form = $form;	
		$viewModel->uploadId = $_GET["upload_id"];
		return $viewModel;
	}
	
	public function manageSetVersionAction()
	{
		$sm = $this->getServiceLocator();
		$auth = $this->auth();
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
					$setVersion->downloadUrl = $formData["download_url"];
					$setVersion->basicLandSlot = $formData["basic_land_slot"];
					$setVersion->basicLandSlotNeedle = $formData["basic_land_slot_needle"];
	
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
	
	public function createTournamentAction()
	{
		$draftId = $this->getEvent()->getRouteMatch()->getParam('draft_id');
		//$tournamentType = $_GET["tournament_type"];

		$sm = $this->getServiceLocator();
		
		$auth = $this->auth();
		$draftTable = $sm->get('Application\Model\DraftTable');
		$draftPlayerTable = $sm->get('Application\Model\DraftPlayerTable');
		
		$draft = $draftTable->getDraft($draftId);
		if($draft->hostId != $auth->getUser()->userId)
		{
			throw new Exception("Unauthorized");
		}
		
		if(isset($_POST["challonge_api_key"]))
		{
			$user = $auth->getUser();
			$user->challongeApiKey = $_POST["challonge_api_key"];
			
			$userTable = $sm->get('Application\Model\UserTable');
			$userTable->saveUser($user);
		}
		else if(isset($_POST["tournament_type"]) && $auth->getUser()->challongeApiKey != NULL){
			$challonge = new \Application\ChallongeAPI($auth->getUser()->challongeApiKey);
			$challonge->verify_ssl = false;
			
			$createTournamentParams = array(
					"tournament[name]" => $draft->name . " tournament",
					"tournament[tournament_type]" => $_POST["tournament_type"],
					"tournament[url]" => $draft->lobbyKey,
					"tournament[description]" => "Tournament for an event hosted on PlaneSculptors.net",
					"tournament[pts_for_match_win]" => 3,
					"tournament[pts_for_match_tie]" => 1,
					"tournament[pts_for_bye]" => 3,
					"tournament[open_signup]" => false,
			);
			
			$tournament = $challonge->createTournament($createTournamentParams);

			$draftPlayers = $draftPlayerTable->fetchJoinedByDraft($draftId);
			foreach($draftPlayers as $playerIndex => $player)
			{
			
				$createParticipantParams = array(
						"participant[name]" => $player->name,
						"participant[seed]" => $playerIndex + 1
				);
				$participant = $challonge->createParticipant($tournament->id, $createParticipantParams);
			}
			
			$draft->tournamentUrl = $tournament->{'full-challonge-url'};
			$draftTable->saveDraft($draft);
		}
		
		if($draft->tournamentUrl != NULL){
			return $this->redirect()->toUrl($draft->tournamentUrl);
		}

		$viewModel = new ViewModel();
		$viewModel->draft = $draft;
		return $viewModel;
	}
	
	public function kickAction()
	{
		$draftId = $this->getEvent()->getRouteMatch()->getParam('draft_id');
		$userId = $_GET['user_id'];
	
		$sm = $this->getServiceLocator();
		$draftTable = $sm->get('Application\Model\DraftTable');
		$draftPlayerTable = $sm->get('Application\Model\DraftPlayerTable');
		$auth = $this->auth();
	
		$draft = $draftTable->getDraft($draftId);
		if($draft->status != Draft::STATUS_OPEN)
		{
			throw new Exception("Invalid status");
		}
		
		if($draft->hostId != $auth->getUser()->userId)
		{
			throw new Exception("Unauthorized");
		}
		
		$draftPlayerTable->deleteDraftPlayerByUserId($draftId, $userId);
		
		return $this->redirect()->toRoute('member-area-with-draft-id', array('action' => 'draft-admin', 'draft_id' => $draft->draftId), array('query' => 'player-kicked'));
	}

	public function uploadHandlerAction()
	{
		//$viewModel = new ViewModel();
		//$viewModel->setTerminal(true);
		
		$uploadId = (int)$_GET["upload_id"];
		
		$userId = $this->auth()->getUser()->userId;
		
		$config = $this->getServiceLocator()->get('Config');
		$dataDir = $config["data_dir"];
		
		$response = $this->getResponse();
		$headers = $response->getHeaders();
		$headers->addHeaderLine('Content-Type', 'text/plain; charset=utf-8');
		
		$ds = DIRECTORY_SEPARATOR;
		
		if (!empty($_FILES)) {
			$tempFile = $_FILES['file']['tmp_name']; 
			
			$targetPath = $dataDir . $userId . $ds . "temp" . $ds . $uploadId . $ds;
			if(!is_dir($targetPath)){
				mkdir($targetPath, 0777, true);
			}
			
			$targetFile =  $targetPath. $_FILES['file']['name'];
			move_uploaded_file($tempFile,$targetFile); //6  
		}

		return $response;
	}
	
	public function finishSetImportAction()
	{
		$auth = $this->auth();
		$sm = $this->getServiceLocator();
		$config = $this->getServiceLocator()->get('Config');
		$setTable = $sm->get('Application\Model\SetTable');
		if(isset($_GET["setId"]) && (int)$_GET["setId"] > 0) {
			$set = $setTable->getSet((int)$_GET["setId"]);
			if($set === null) {
				return $this->notFoundAction();
			}
			
			try {
				$ds = DIRECTORY_SEPARATOR;
				$dataDir = $config["data_dir"];
				$setDir = $dataDir . $auth->getUser()->userId . $ds . "temp" . $ds . "api" . $ds;
					
				$redirect = $this->processSetUpload($set, UploadCardsForm::NAME_DOT_JPG, rand(), $setDir, new \Application\SetParser\JsonSetParser());
				return $redirect;
			} catch(\Exception $e) {
				echo $e;
			}	
		}
		else {
			$viewModel = new ViewModel();
			$viewModel->setsOwned = $setTable->getSetsByUser($_SESSION["user_id"], true);
			return $viewModel;
		}
	}
}
?>