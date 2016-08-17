<?php
/**
 * Zend Framework (http://framework.zend.com/)
*
* @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
* @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
* @license   http://framework.zend.com/license/new-bsd New BSD License
*/

namespace Application\Controller;

use Zend\Mvc;
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

abstract class WebDrafterControllerBase extends AbstractActionController
{
	private $auth;
	
	public function onDispatch(\Zend\Mvc\MvcEvent $e) 
	{
		if(($redirect = $this->initUser($e)) != NULL) return $redirect;
		//Call your service here
	
		return parent::onDispatch($e);
	}
	
	protected function auth() 
	{
		return $this->auth;		
	}
	
	protected function isAuthRequired(\Zend\Mvc\MvcEvent $e)
	{
		return false;
	}
	
	protected function isUnregisteredAllowed(\Zend\Mvc\MvcEvent $e)
	{
		return false;
	}
	
	private function initUser(\Zend\Mvc\MvcEvent $e)
	{
		$sm = $this->getServiceLocator();
		$this->auth = $sm->get('Application\GoogleAuthentication');
		
		if($this->isAuthRequired($e))
		{
			//die($this->isUnregisteredAllowed($e));
			
			if($this->auth()->GetStatus() == GoogleAuthentication::STATUS_ANONYMOUS)
			{
				//echo "anon";
				return $this->redirect()->toRoute('member-area', array('action' => 'login'));
			}
			else if($this->auth()->GetStatus() == GoogleAuthentication::STATUS_NOT_REGISTERED && !$this->isUnregisteredAllowed($e))
			{
				//echo "reg";
				return $this->redirect()->toRoute('member-area', array('action' => 'register'));
			}	
		}
		
		return NULL;
	}
	
	/*protected function createClient()
	{
		$redirectUri = $this->url()->fromRoute('member-area', array('action' => 'login'), array('force_canonical' => true));
		
		$scopes = implode(' ', array(
				//\Google_Service_Drive::DRIVE_METADATA_READONLY,
				\Google_Service_Drive::DRIVE_READONLY,
				\Google_Service_Oauth2::USERINFO_EMAIL,
				\Google_Service_Oauth2::USERINFO_PROFILE)
		);
		
		$client = new \Google_Client();
		$client->setApplicationName('PlaneSculptors.net');
		$client->setScopes($scopes);
		$client->setAuthConfigFile('config/client_secret.json');
		$client->setAccessType('offline');
		$client->setRedirectUri($redirectUri);
		
		//var_dump($client->isAccessTokenExpired());
		
		$token = @$_COOKIE['ACCESSTOKEN'];  // fetch from cookie
		if($token){
			// use the same token
			$client->setAccessToken($token);
			//echo "token from cookie";
		}
		else {
			$token = $client->getAccessToken();
			//var_dump($token);
			//echo "token from get";
		}

		$_COOKIE['ACCESSTOKEN'] = $token;
		
		if($token && $client->isAccessTokenExpired()){  // if token expired
			$refreshToken = json_decode($token)->refresh_token;
		
			// refresh the token
			$client->refreshToken($refreshToken);
		}
		
		
		return $client;
	}*/
}
?>