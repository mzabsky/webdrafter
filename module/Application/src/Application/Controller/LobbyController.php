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
use Zend\View\Model\JsonModel;

class LobbyController extends AbstractActionController
{	
	private $sm;
	private $draftTable;
	private $draft;

	private function init()
	{
		$this->lobbyKey = $this->getEvent()->getRouteMatch()->getParam('lobby_key');
		$this->sm = $this->getServiceLocator();
		$this->draftTable = $this->sm->get('Application\Model\DraftTable');
		$this->draftPlayerTable = $this->sm->get('Application\Model\DraftPlayerTable');
		$this->draft = $this->draftTable->getDraftByLobbyKey($this->lobbyKey);		
	}
	
	public function indexAction()
	{
		$this->init();
		
		$viewModel = new ViewModel();
		$viewModel->draft = $this->draft;
		return $viewModel;
	}
	
	public function joinAction()
	{
		$this->init();
		
		if($this->draft->status != Draft::STATUS_OPEN)
		{
			throw new \Exception("Invalid draft status");
		}
		
		if(!isset($_GET["player_name"]) || strlen($_GET["player_name"]) < 1)
		{
			throw new \Exception("Name not set");
		}

		$inviteKey = md5("draftplayer_" . time());
		
		$draftPlayer = new \Application\Model\DraftPlayer();
		$draftPlayer->draftId = $this->draft->draftId;
		$draftPlayer->inviteKey = $inviteKey;
		$draftPlayer->hasJoined = 1;
		$draftPlayer->name = $_GET["player_name"];
		$draftPlayer->userId = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null;
		$this->draftPlayerTable->saveDraftPlayer($draftPlayer);
		
		return $this->redirect()->toRoute('draft', array('invite_key' => $inviteKey));
	}
}
;