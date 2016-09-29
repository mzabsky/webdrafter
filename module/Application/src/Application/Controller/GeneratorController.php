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
    			$name = $card->name . ' ' . $card->rarity;
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
}
