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

class GeneratorController extends WebDrafterControllerBase
{
    public function sealedPoolAction()
    {

    	$setVersionId = $this->getEvent()->getRouteMatch()->getParam('set_version_id');
    	$numberOfPacks = isset($_GET["n"]) ? (int)$_GET["n"] : 6;
    	
    	$sm = $this->getServiceLocator();
    	$setVersionTable = $sm->get('Application\Model\SetVersionTable');
    	$cardTable = $sm->get('Application\Model\CardTable');
    	
    	$setVersion = $setVersionTable->getSetVersion($setVersionId);
    	
    	$cards = $cardTable->fetchBySetVersion($setVersionId);
    	$generator = new \Application\PackGenerator\BoosterDraftPackGenerator();
    	$packs = $generator->GeneratePacks($cards, $numberOfPacks, $setVersion->basicLandSlot, $setVersion->basicLandSlotNeedle);    	
    	
    	$pool = array();
    	foreach ($packs as $pack)
    	{
    		foreach ($pack as $card)
    		{
    			$name = ($card->basicLandSlot ? '*' : '') . $card->name . ' ' . $card->rarity;
    			if(isset($pool[$name]))
    			{
    				$pool[$name]++;
    			}
    			else
    			{
    				$pool[$name] = 1;
    			}
    		}
    	}
    	
    	//ksort($pool);
    	


    	$viewModel = new ViewModel();
    	$viewModel->pool = $pool;
    	return $viewModel;
    }
    
    public function wikiListAction()
    {
    	$sm = $this->getServiceLocator();
    	$adapter = $sm->get("Zend\Db\Adapter\Adapter");
    	
    	$form = new \Application\Form\WikiListForm();
    	
    	if(isset($_GET["sets"])) {
    		$formData = array();
    		$formData["sets"] = str_replace(",", "\n", $_GET["sets"]);
    		$form->setData($formData);
    	}
    	
    	if ($this->getRequest()->isPost())
    	{
    		$setTable = $sm->get('Application\Model\SetTable');
    		$cardTable = $sm->get('Application\Model\CardTable');
    		$setVersionTable = $sm->get('Application\Model\SetVersionTable');
    		
    		$formData = $this->getRequest()->getPost()->toArray();
    		$form->setData($formData);
    		
    		$setNames = explode("\n", $formData["sets"]);
    		$inputDeck = $formData["cards"];
    		
    		$name = substr(explode("\n", $formData["cards"])[0], 3);

    		// Get the newest version and card list for each of the sets;
    		$cards = array();
    		foreach($setNames as $setName) {
    			$setName = trim($setName);
    			
    			if(!$setName) continue;
    			
    			$set = $setTable->getSetByUrlName($setName);
    			if(!$set) {
    				throw new \Exception("Set $setName not found.");
    			}
    			
    			$setVersion = $setVersionTable->getSetVersion($set->currentSetVersionId);
    			$setVersionCards = $cardTable->fetchBySetVersion($setVersion->setVersionId);
    			
    			foreach($setVersionCards as $card) {
    				$cards[strtolower($card->name)]["set"] = $set->urlName . ":" . $setVersion->urlName;
    				
    				$category = "";
    				if(strpos($card->types, "Land") !== false) {
    					$category = "Lands";
    				} else if(strpos($card->types, "Creature") !== false) {
    					$category = "Creatures";
    				} else {
    					$category = "Noncreatures";
    				}
    				$cards[strtolower($card->name)]["category"] = $category;
    			}
    		}
    		
    		$categorizedMaindeckLines = array();
    		$sideboardLines = array();
    		if($x = preg_match_all("/^(SB: )?([0-9]+) (.*)$/m", $inputDeck, $matches, PREG_SET_ORDER)) {
    			foreach($matches as $match) {
    				$index = strtolower(trim($match[3]));
    				$trimmed = trim($match[3]);
    				$count = (int)$match[2];
    				if(isset($cards[$index])) {
    					$cardSet = $cards[$index]["set"];
    					$line = "$count {[$cardSet:$trimmed]}";
    					$category = $cards[$index]["category"];
    				}
    				else {
    					$line = "$count $trimmed NOT FOUND";	
    					$category = "Noncreatures";
    				}
    				
    				if($match[1] != null && strlen($match[1]) > 0) {
    					$sideboardLines[] = $line;
    				}
    				else {
    					$categorizedMaindeckLines[$category][] = $line;
    				}
    			}
    		}
    		
    		$actualMaindeckLines = array();
    		if(isset($categorizedMaindeckLines["Creatures"])) {
    			$actualMaindeckLines[] = "Creatures";    			
    			foreach($categorizedMaindeckLines["Creatures"] as $line) $actualMaindeckLines[] = $line;
    		}

    		if(isset($categorizedMaindeckLines["Noncreatures"])) {
    			if(count($actualMaindeckLines) > 0) $actualMaindeckLines[] = htmlentities(" &nbsp;");
    			$actualMaindeckLines[] = "Noncreatures";
    			foreach($categorizedMaindeckLines["Noncreatures"] as $line) $actualMaindeckLines[] = $line;
    		}

    		if(isset($categorizedMaindeckLines["Lands"])) {
    			if(count($actualMaindeckLines) > 0) $actualMaindeckLines[] = htmlentities(" &nbsp;");
    			$actualMaindeckLines[] = "Lands";
    			foreach($categorizedMaindeckLines["Lands"] as $line) $actualMaindeckLines[] = $line;
    		}
    		
    		
    		//var_dump($outputDeck);
    		/*
    		$output = "";
    		foreach($cardNames as $cardName) {
    			$actualCardName = strtolower(trim($cardName));
    			if(isset($cards[$actualCardName])) {
    				$output .= "{[$cards[$actualCardName]:$actualCardName]}\n";
    			}
    			else if($cardName == "SIDEBOARD:"){
    				$output .= $cardName . "\n";
    			}
    		}
    		*/
    		
    		
    		//var_dump($output);
    		
    	}
    	
    	$viewModel = new ViewModel();
    	$viewModel->form = $form;
    	$viewModel->deckName = @$name;
    	$viewModel->maindeckLines = @$actualMaindeckLines;
    	$viewModel->sideboardLines = @$sideboardLines;
    	
    	return $viewModel;
    }
}
