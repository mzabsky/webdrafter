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
    	$setId = $this->getEvent()->getRouteMatch()->getParam('set_id');
    	$numberOfPacks = isset($_GET["n"]) ? (int)$_GET["n"] : 6;
    	
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$cardTable = $sm->get('Application\Model\CardTable');
    	
    	$cards = $cardTable->fetchBySet($setId);
    	$generator = new \Application\PackGenerator\BoosterDraftPackGenerator();
    	$packs = $generator->GeneratePacks($cards, $numberOfPacks);    	
    	
    	$pool = array();
    	foreach ($packs as $pack)
    	{
    		foreach ($pack as $card)
    		{
    			if(isset($pool[$card->name]))
    			{
    				$pool[$card->name]++;
    			}
    			else
    			{
    				$pool[$card->name] = 1;
    			}
    		}
    	}
    	
    	ksort($pool);
    	
    	$viewModel = new ViewModel();
    	$viewModel->pool = $pool;
    	return $viewModel;
    }
}
