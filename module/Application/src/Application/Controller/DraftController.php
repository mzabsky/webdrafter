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
use Zend\Mvc\MvcEvent;
use League\OAuth2\Client\Provider;
use Application\Model\Draft;
use Application\Model\Pick;
use Application\Model\DraftPlayerTable;
use Zend\View\Model\JsonModel;
use Application\Model\SetVersion;

class DraftController extends WebDrafterControllerBase
{	
	private $sm;
	private $draftTable;
	private $draftPlayerTable;
	private $pickTable;
	private $cardTable;
	private $draft;
	private $draftPlayer;
	private $userPlayer;
	

	private function init()
	{
		$this->inviteKey = $this->getEvent()->getRouteMatch()->getParam('invite_key');
		$this->sm = $this->getServiceLocator();
		$this->draftTable = $this->sm->get('Application\Model\DraftTable');
		$this->draftPlayerTable = $this->sm->get('Application\Model\DraftPlayerTable');
		$this->draftSetVersionTable = $this->sm->get('Application\Model\DraftSetVersionTable');
		$this->pickTable = $this->sm->get('Application\Model\PickTable');
		$this->setVersionTable = $this->sm->get('Application\Model\SetVersionTable');
		$this->setTable = $this->sm->get('Application\Model\SetTable');
		$this->userTable = $this->sm->get('Application\Model\UserTable');
		$this->cardTable = $this->sm->get('Application\Model\CardTable');
		$this->draftPlayer = $this->draftPlayerTable->getDraftPlayerByInviteKey($this->inviteKey);		
		$this->draft = $this->draftTable->getDraft($this->draftPlayer->draftId);
	}
	
	public function indexAction()
	{
		$this->init();
		
		$viewModel = new ViewModel();
		$viewModel->draft = $this->draft;	
		$viewModel->draftPlayer = $this->draftPlayer;	
		$viewModel->allPlayers = $this->draftPlayerTable->fetchByDraft($this->draft->draftId);
		$viewModel->host = $this->userTable->getUser($this->draft->hostId);
		
		$allCards = $this->cardTable->fetchByDraft($this->draft->draftId);
		$cardArray = array();
		
		foreach($allCards as $card)
		{
			$cardArray[$card->cardId] = $card;
		}
		
		$packs = array();
		$draftSetVersions = $this->draftSetVersionTable->fetchByDraft($this->draft->draftId);
		foreach($draftSetVersions as $draftSetVersion)
		{
			$pack = new \stdClass();
			$pack->packNumber = $draftSetVersion->packNumber;
			$pack->setVersion = $this->setVersionTable->getSetVersion($draftSetVersion->setVersionId);
			$pack->set = $this->setTable->getSet($pack->setVersion->setId);
			$packs[] = $pack;
		}
		
		$viewModel->packs = $packs;
		$viewModel->cards = $cardArray;
		
		$basicTable = $this->sm->get('Application\Model\DraftPlayerBasicTable');
		$viewModel->basics = $basicTable->fetchByDraftPlayer($this->draftPlayer->draftPlayerId);
		return $viewModel;
	}	
	
	public function getDraftInfoAction()
	{
		$this->init();
		$jsonModel = new JsonModel();
		$jsonModel->inviteKey = $this->inviteKey;
		$jsonModel->draftStatus = $this->draft->status;
		$jsonModel->tournamentUrl = $this->draft->tournamentUrl;
		switch($this->draft->status)
		{
			case Draft::STATUS_OPEN:
				$jsonModel->hasJoined = $this->draftPlayer->hasJoined;
				$jsonModel->draftPlayers = \Application\resultSetToArray($this->draftPlayerTable->fetchByDraft($this->draft->draftId));
				break;
			case Draft::STATUS_RUNNING:
				$jsonModel->booster = \Application\resultSetToArray($this->pickTable->fetchBoosterForPlayer($this->draftPlayer->draftPlayerId));
				$jsonModel->picks = \Application\resultSetToArray($this->pickTable->fetchPicksForPlayer($this->draftPlayer->draftPlayerId, false));
				$jsonModel->hasPicked = (int)$this->pickTable->hasPickedFromCurrent($this->draftPlayer->draftPlayerId);
				$jsonModel->pickIndicators = $this->draftTable->fetchPickIndicators($this->draft->draftId);
				$jsonModel->packNumber = $this->draft->packNumber;
				$jsonModel->pickNumber = $this->draft->pickNumber;
				// do picks
				break;
			case Draft::STATUS_FINISHED:
				$jsonModel->picks = \Application\resultSetToArray($this->pickTable->fetchPicksForPlayer($this->draftPlayer->draftPlayerId, true));
				// list picks
				break;
			default:
				throw new \Exception("Invalid draft status");
		}
		
		return $jsonModel;
	}
	
	public function joinAction()
	{
		$this->init();
		
		if($this->draft->status != Draft::STATUS_OPEN)
		{
			throw new \Exception("Invalid draft status");
		}
		if($this->draftPlayer->hasJoined)
		{
			throw new \Exception("Already joined");
		}
		
		$auth = $this->sm->get("Application\GoogleAuthentication");
		if($auth->isLoggedIn())
		{
			$name = $auth->getUser()->name;
		}
		else if(isset($_GET["player_name"]) && strlen($_GET["player_name"]) > 0)
		{
			$name = $_GET["player_name"];
		}
		else 
		{
			throw new \Exception("Name not set");
		}

		if(!$this->draftPlayerTable->checkPlayerNameOpenInDraft($name, $this->draft->draftId))
		{
			return $this->redirect()->toRoute('draft', array('invite_key' => $this->lobbyKey), array('query' => 'name-taken'));
		}
		
		$this->draftPlayer->hasJoined = 1;
		$this->draftPlayer->name = $name;
		$this->draftPlayer->userId = $auth->getStatus() == \Application\GoogleAuthentication::STATUS_LOGGED_IN ? $auth->getUser()->userId : null;
		$this->draftPlayerTable->saveDraftPlayer($this->draftPlayer);
		
		$jsonModel = new JsonModel();
		return $jsonModel;
	}
	

	public function pickAction()
	{
		$this->init();
	
		if($this->draft->status != Draft::STATUS_RUNNING)
		{
			throw new \Exception("Invalid draft status");
		}
		
		if(!isset($_GET["pickId"]) || strlen($_GET["pickId"]) < 1)
		{
			throw new \Exception("Invalid pick Id");
		}
		
		try
		{
			$adapter = $this->sm->get("Zend\Db\Adapter\Adapter");
			$adapter->getDriver()->getConnection()->beginTransaction();
		
			// Validate the pick
			$pick = $this->pickTable->getPick((int)$_GET["pickId"]);
			if($pick->packNumber != $this->draft->packNumber || $pick->currentPlayerId != $this->draftPlayer->draftPlayerId || $pick->isPicked)
			{
				throw new \Exception("Invalid pick");
			}
			
			// If all players have picked, advance the entire draft to next pick
			$picksMade = $this->pickTable->getNumberOfCurrentPicksMade($this->draft->draftId);
			
			// Save the pick
			$pick->isPicked = true;
			$pick->pickNumber = $this->draft->pickNumber;
			$this->pickTable->savePick($pick);

			$draftPlayers = \Application\resultSetToArray($this->draftPlayerTable->fetchJoinedByDraft($this->draft->draftId));
			$humanCount = 0;
			$isAiByPlayerId = array();
			foreach($draftPlayers as $draftPlayer)
			{
				if(!$draftPlayer->isAi)
				{
					$humanCount++;
				}
				
				$isAiByPlayerId[$draftPlayer->draftPlayerId] = $draftPlayer->isAi; 
			}
			
			$picksRequired = $humanCount; 
			//var_dump($picksMade, $picksRequired); die();
			if($picksMade + 1 == $picksRequired)
			{
				$draftSets = \Application\resultSetToArray($this->draftSetVersionTable->fetchByDraft($this->draft->draftId));
				$currentDraftSetVersion = $draftSets[$this->draft->packNumber - 1];
				$currentSetVersion = $this->setVersionTable->getSetVersion($currentDraftSetVersion->setVersionId);
				
				switch($this->draft->gameMode)
				{
					case Draft::MODE_BOOSTER_DRAFT:
					case Draft::MODE_SEALED_DECK:
						$actualNumberOfPacks = count($draftSets);
						break;
					case Draft::MODE_CHAOS_DRAFT:
					case Draft::MODE_CUBE_DRAFT:
						$actualNumberOfPacks = count($draftSets);
						break;
					default:
						throw new \Exception("Invalid game mode " . $this->gameMode);
				}
				
				$packSize = 14;
				if($currentSetVersion->basicLandSlot != SetVersion::BASIC_LAND_SLOT_BASIC_LAND || $this->draft->gameMode == Draft::MODE_CUBE_DRAFT)
				{
					$packSize = 15;
				}
				
				if($this->draft->pickNumber < $packSize)
				{
					//DIE("shift");
					// Advance to next card in pack				
					$this->draft->pickNumber++;
					$this->draftTable->saveDraft($this->draft);
					
					$shiftDirection = $this->draft->packNumber % 2 == 1 ? 1 : -1;
					
					$playerShiftMap = array();
					foreach($draftPlayers as $playerIndex => $player)
					{
						$shiftIndex = (count($draftPlayers) + $playerIndex - $shiftDirection) % count($draftPlayers);
						//var_dump($shiftDirection, $playerIndex, $shiftIndex);
						
						$playerShiftMap[$player->draftPlayerId] = $draftPlayers[$shiftIndex]->draftPlayerId;
					}
					
					$boosterByPlayer = array();
					foreach($draftPlayers as $player)
					{
						$boosterByPlayer[$player->draftPlayerId] = $this->pickTable->fetchBoosterForPlayer($player->draftPlayerId);
					}
					
					foreach($playerShiftMap as $fromPlayerId => $toPlayerId)
					{
						$isAi = $isAiByPlayerId[$fromPlayerId];
						if($isAi)
						{
							$aiPickIndex = rand(0, count($boosterByPlayer[$fromPlayerId]) - 1);
						}
						
						foreach($boosterByPlayer[$fromPlayerId] as $i => $pick2)
						{
							if($isAi && $i == $aiPickIndex)
							{
								$pick2->isPicked = true;
							}
							else 
							{
								$pick2->currentPlayerId = $toPlayerId;
							}
							$this->pickTable->savePick($pick2);	
						}
						//$this->pickTable->shiftPicks($fromPlayerId, $toPlayerId, $this->draft->packNumber);
					}
				}
				else if($this->draft->packNumber < $actualNumberOfPacks)
				{
					//DIE("pack");
					
					// Pass the cards to the next player
					$this->draft->pickNumber = 1;
					$this->draft->packNumber++;
					$this->draftTable->saveDraft($this->draft);
				}
				else 
				{
					//DIE("finish");
					
					// Finish draft
					$this->draft->status = Draft::STATUS_FINISHED;
					$this->draftTable->saveDraft($this->draft);					
				}
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
	
	public function exportAction()
	{
		$fixApostrophes = function($cardName) { return str_replace("\xE2\x80\x99", "'", $cardName); };//’
		
		$this->init();
		
		if(!isset($_GET["type"]) || strlen($_GET["type"]) < 1)
		{
			throw new \Exception("Type not set");
		}

		$type = $_GET["type"];
		
		$viewModel = new ViewModel();
		$viewModel->setTerminal(true);
		$maindeck = $this->cardTable->fetchPickedCards($this->draftPlayer->draftPlayerId, Pick::ZONE_MAINDECK);
		$sideboard = $this->cardTable->fetchPickedCards($this->draftPlayer->draftPlayerId, Pick::ZONE_SIDEBOARD);
		
		$maindeckArray = array();
		foreach($maindeck as $card)
		{
			$cardName = $fixApostrophes($card->name);
			if(isset($maindeckArray[$cardName]))
			{
				$maindeckArray[$cardName]++;
			}
			else 
			{
				$maindeckArray[$cardName] = 1;
			}
		}
		
		$sideboardArray = array();
		foreach($sideboard as $card)
		{
			$cardName = $fixApostrophes($card->name);
			if(isset($sideboardArray[$cardName]))
			{
				$sideboardArray[$cardName]++;
			}
			else 
			{
				$sideboardArray[$cardName] = 1;
			}
		}
		
		$basicTable = $this->sm->get('Application\Model\DraftPlayerBasicTable');
		$basics = $basicTable->fetchByDraftPlayer($this->draftPlayer->draftPlayerId);
		
		$basicTypes = array('W' => 'Plains', 'U' => 'Island', 'B' => 'Swamp', 'R' => 'Mountain', 'G' => 'Forest');
		foreach($basics as $basic)
		{
			$maindeckArray[$basicTypes[$basic->color]] = $basic->count;
		}
		
		$response = $this->getResponse();
		
		$headers = $response->getHeaders();
		$headers->addHeaderLine('Content-Type', 'text/plain; charset=utf-8');
		
		
		$viewModel->maindeck = $maindeckArray;
		$viewModel->sideboard = $sideboardArray;
		$viewModel->deckName = $this->draft->name . " - " . $this->draftPlayer->name . "'s deck";
		
		switch($type){
			case 'text':
				$viewModel->setTemplate('application/draft/export-text.phtml');
				break;
			case 'cockatrice':
				$viewModel->setTemplate('application/draft/export-cockatrice.phtml');
				break;
			default:
				throw new \Exception("Invalid export type");
		}
		
		return $viewModel;
	}
	
	public function pickListAction()
	{
		$this->init();
	
		$viewModel = new ViewModel();
		$viewModel->setTerminal(true);
		$viewModel->picks = $this->cardTable->fetchPickedCards($this->draftPlayer->draftPlayerId);

		$response = $this->getResponse();
	
		$headers = $response->getHeaders();
		$headers->addHeaderLine('Content-Type', 'text/plain; charset=utf-8');

		return $viewModel;
	}
	
	public function savePickZoneAction()
	{
		$this->init();
	
		if($this->draft->status != Draft::STATUS_FINISHED)
		{
			throw new \Exception("Invalid draft status");
		}
		
		if(!isset($_GET["pick_id"]) || strlen($_GET["pick_id"]) < 1)
		{
			throw new \Exception("Invalid pick Id");
		}
		
		if(!isset($_GET["zone"]))
		{
			throw new \Exception("Invalid zone");
		}

		if(!isset($_GET["zone_column"]))
		{
			throw new \Exception("Invalid zone column");
		}
		
		$pick = $this->pickTable->getPick((int)$_GET["pick_id"]);
		if($pick->currentPlayerId != $this->draftPlayer->draftPlayerId)
		{
			throw new \Exception("Not your pick");
		}
		
		$pick->zone = (int)$_GET["zone"];
		$pick->zoneColumn = (int)$_GET["zone_column"];
		$this->pickTable->savePick($pick);

		$jsonModel = new JsonModel();
		return $jsonModel;
	}
	
	public function syncPickZonesAction()
	{
		$this->init();
	
		if($this->draft->status != Draft::STATUS_FINISHED)
		{
			throw new \Exception("Invalid draft status");
		}
		
		foreach ($_POST['zone_settings'] as $zoneSetting)
		{
			if(!isset($zoneSetting["pick_id"]))
			{
				throw new \Exception("Invalid pick ID");
			}
			
			if(!isset($zoneSetting["zone"]))
			{
				throw new \Exception("Invalid zone");
			}
			
			if(!isset($zoneSetting["zone_column"]))
			{
				throw new \Exception("Invalid zone column");
			}
			
			$pick = $this->pickTable->getPick((int)$zoneSetting["pick_id"]);
			if($pick->currentPlayerId != $this->draftPlayer->draftPlayerId)
			{
				throw new \Exception("Not your pick");
			}

			$pick->zone = (int)$zoneSetting["zone"];
			$pick->zoneColumn = (int)$zoneSetting["zone_column"];
			$this->pickTable->savePick($pick);
		}
		
		$jsonModel = new JsonModel();
		return $jsonModel;
	}
	
	public function sortPicksAction()
	{
		$this->init();


		if($this->draft->status != Draft::STATUS_FINISHED)
		{
			throw new \Exception("Invalid draft status");
		}


		if(!isset($_GET["sort_by"]))
		{
			throw new \Exception("Invalid sort by");
		}
		
		$cards = $this->cardTable->fetchPickedCards($this->draftPlayer->draftPlayerId);
		$cardsById = array();
		foreach($cards as $card)
		{
			$cardsById[$card->cardId] = $card;
		}
		
		$picks = $this->pickTable->fetchPicksForPlayer($this->draftPlayer->draftPlayerId, true);		
		
		foreach($picks as $pick)
		{
			if($pick->zone != Pick::ZONE_MAINDECK) continue;
			

			//var_dump($pick->zoneColumn);
			
			$card = $cardsById[$pick->cardId];
			switch ($_GET["sort_by"])
			{
				case "color":
					if(strlen($card->colors) == 0)
					{
						$pick->zoneColumn = 0;
					}
					else if(strlen($card->colors) > 1)
					{
						$pick->zoneColumn = 1;
					}
					else if($card->colors == "W")
					{
						$pick->zoneColumn = 2;
					}
					else if($card->colors == "U")
					{
						$pick->zoneColumn = 3;
					}
					else if($card->colors == "B")
					{
						$pick->zoneColumn = 4;
					}
					else if($card->colors == "R")
					{
						$pick->zoneColumn = 5;
					}
					else if($card->colors == "G")
					{
						$pick->zoneColumn = 6;
					}
					else 
					{
						$pick->zoneColumn = 0;
					}
					break;
				case "cmc":
					if($card->cmc < 7)
					{
						$pick->zoneColumn = (int)$card->cmc;
					}
					else {
						$pick->zoneColumn = 7;
					}
					break;
				case "rarity":
					if($card->rarity == "M")
					{
						$pick->zoneColumn = 0;
					}
					else if($card->rarity == "R")
					{
						$pick->zoneColumn = 1;
					}
					else if($card->rarity == "U")
					{
						$pick->zoneColumn = 2;
					}
					else if($card->rarity == "C")
					{
						$pick->zoneColumn = 3;
					}					
					else
					{
						$pick->zoneColumn = 4;
					}
					break;
				default:
					throw new \Exception("Invalid sort by");
			}
			
			//var_dump($pick->zoneColumn);
			$this->pickTable->savePick($pick);
			//break;
		}

		$jsonModel = new JsonModel();
		return $jsonModel;
	}
	
	public function updateBasicAction()
	{
		$this->init();

		$basicTable = $this->sm->get('Application\Model\DraftPlayerBasicTable');
		
		$colors = array("W", "U", "B", "R", "G");
		foreach($colors as $color){
			$n = (int)$_GET[strtolower($color)];
			
			$basic = $basicTable->getByDraftPlayerAndColor($this->draftPlayer->draftPlayerId, $color);
			
			if($basic == null)
			{
				$basic = new \Application\Model\DraftPlayerBasic();
				$basic->draftPlayerId = $this->draftPlayer->draftPlayerId;
				$basic->color = $color;
			}
			
			$basic->count = $n;
			$basicTable->saveDraftPlayerBasic($basic);
		}

		$jsonModel = new JsonModel();
		return $jsonModel;
	}
}
;