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
use Zend\View\Model\JsonModel;

class DraftController extends AbstractActionController
{	
	private $sm;
	private $draftTable;
	private $draftPlayerTable;
	private $pickTable;
	private $cardTable;
	private $draft;
	private $draftPlayer;
	

	private function init()
	{
		$this->inviteKey = $this->getEvent()->getRouteMatch()->getParam('invite_key');
		$this->sm = $this->getServiceLocator();
		$this->draftTable = $this->sm->get('Application\Model\DraftTable');
		$this->draftPlayerTable = $this->sm->get('Application\Model\DraftPlayerTable');
		$this->draftSetTable = $this->sm->get('Application\Model\DraftSetTable');
		$this->pickTable = $this->sm->get('Application\Model\PickTable');
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
		$allCards = $this->cardTable->fetchByDraft($this->draft->draftId);
		$cardArray = array();
		
		foreach($allCards as $card)
		{
			$cardArray[$card->cardId] = $card;
		}
		
		$viewModel->cards = $cardArray;
		return $viewModel;
	}	
	
	public function getDraftInfoAction()
	{
		$this->init();
		$jsonModel = new JsonModel();
		$jsonModel->inviteKey = $this->inviteKey;
		$jsonModel->draftStatus = $this->draft->status;
		switch($this->draft->status)
		{
			case Draft::STATUS_OPEN:
				$jsonModel->hasJoined = $this->draftPlayer->hasJoined;
				$jsonModel->draftPlayers = \Application\resultSetToArray($this->draftPlayerTable->fetchByDraft($this->draft->draftId));
				break;
			case Draft::STATUS_RUNNING:
				$jsonModel->booster = \Application\resultSetToArray($this->pickTable->fetchBoosterForPlayer($this->draftPlayer->draftPlayerId));
				$jsonModel->picks = \Application\resultSetToArray($this->pickTable->fetchPicksForPlayer($this->draftPlayer->draftPlayerId));
				$jsonModel->hasPicked = (int)$this->pickTable->hasPickedFromCurrent($this->draftPlayer->draftPlayerId);
				$jsonModel->pickIndicators = $this->draftTable->fetchPickIndicators($this->draft->draftId);
				$jsonModel->packNumber = $this->draft->packNumber;
				$jsonModel->pickNumber = $this->draft->pickNumber;
				// do picks
				break;
			case Draft::STATUS_FINISHED:
				$jsonModel->picks = \Application\resultSetToArray($this->pickTable->fetchPicksForPlayer($this->draftPlayer->draftPlayerId));
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
		if(!isset($_GET["name"]) || strlen($_GET["name"]) < 1)
		{
			throw new \Exception("Name not set");
		}

		$this->draftPlayer->hasJoined = 1;
		$this->draftPlayer->name = $_GET["name"];
		$this->draftPlayer->userId = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null;
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
			$picksRequired = count($draftPlayers); 
			//var_dump($picksMade, $picksRequired); die();
			if($picksMade + 1 == $picksRequired)
			{
				$draftSets = \Application\resultSetToArray($this->draftSetTable->fetchByDraft($this->draft->draftId));
				
				if($this->draft->pickNumber < 14)
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
						foreach($boosterByPlayer[$fromPlayerId] as $pick2)
						{
							$pick2->currentPlayerId = $toPlayerId;
							$this->pickTable->savePick($pick2);	
						}
						//$this->pickTable->shiftPicks($fromPlayerId, $toPlayerId, $this->draft->packNumber);
					}
				}
				else if($this->draft->packNumber < count($draftSets))
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
			if(isset($maindeckArray[$card->name]))
			{
				$maindeckArray[$card->name]++;
			}
			else 
			{
				$maindeckArray[$card->name] = 1;
			}
		}
		
		$sideboardArray = array();
		foreach($sideboard as $card)
		{
			if(isset($sideboardArray[$card->name]))
			{
				$sideboardArray[$card->name]++;
			}
			else 
			{
				$sideboardArray[$card->name] = 1;
			}
		}
		
		$response = $this->getResponse();
		
		$headers = $response->getHeaders();
		$headers->addHeaderLine('Content-Type', 'text/plain; charset=utf-8');
		
		
		$viewModel->maindeck = $maindeckArray;
		$viewModel->sideboard = $sideboardArray;
		$viewModel->deckName = $this->draft->name . " - " . $this->draftPlayer->name . "'s deck";
		
		switch($type){
			case 'text':
				$viewModel->setTemplate('Application/draft/export-text.phtml');
				break;
			case 'cockatrice':
				$viewModel->setTemplate('Application/draft/export-cockatrice.phtml');
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
}
;