<?php

namespace Application;

class GoogleAuthentication
{
	private static $instance = false;
	
	private $sm;
	private $googleClient;
	private $user;
	private $authStatus;
	
	const STATUS_LOGGED_IN = 3;
	const STATUS_NOT_REGISTERED = 2;
	const STATUS_ANONYMOUS = 1;
	
	private function initUser()
	{
		$this->googleClient = $this->createClient();
		
		if(!isset($_SESSION['user_id']))
		{
			$this->authStatus = GoogleAuthentication::STATUS_ANONYMOUS;
			return;
			//throw new \Exception("Must be logged in to access this page");
		}
		
		$this->googleClient->setAccessToken($_SESSION["access_token"]);
	
		if ($this->googleClient->isAccessTokenExpired()) {
			$refreshToken = $this->googleClient->getRefreshToken();
			if($refreshToken == null){
				session_destroy();
				$this->authStatus = GoogleAuthentication::STATUS_ANONYMOUS;
				return;
			}
				
			$this->googleClient->refreshToken($refreshToken);
			//file_put_contents($credentialsPath, $client->getAccessToken());
		}
	
		$userTable = $this->sm->get('Application\Model\UserTable');
		$this->user = $userTable->tryGetUserByEmail($_SESSION["email"]);
	
		if($this->user->name == null){
			$this->authStatus = GoogleAuthentication::STATUS_NOT_REGISTERED;
			return;
		}
		
		$this->authStatus = GoogleAuthentication::STATUS_LOGGED_IN;
	}
	
	private function createClient()
	{
		$vhm = $this->sm->get('viewhelpermanager');
		$url = $vhm->get('url');
		
		$redirectUri = $url('member-area', array('action' => 'login'), array('force_canonical' => true));
	
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
	
	public function __construct($sm){
		$this->sm = $sm;
		$this->initUser();
	}
	
	/*public function getInstance($sm){
		if(self::$instance === null){
			self::$instance = new GoogleAuthentication($sm);
		}
      	return self::$instance;
	}*/
	
	public function getGoogleClient()
	{
		return $this->googleClient;
	}
	
	public function getUser()
	{
		return $this->user;
	}
	
	public function getStatus()
	{
		return $this->authStatus;
	}
}