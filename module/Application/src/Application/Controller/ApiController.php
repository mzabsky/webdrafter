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
use Zend\View\Model\JsonModel;

class ApiController extends WebDrafterControllerBase
{
	protected $user;
	
	public function onDispatch(\Zend\Mvc\MvcEvent $e)
	{
		$apiKey = @$_POST["apiKey"];
		if($apiKey == null){
			$this->getResponse()->setStatusCode(401);
			$this->getResponse()->setReasonPhrase("API key not provided.");
			$this->getResponse()->setContent(var_export($_POST, true));
        	return $this->getResponse();
		}
		
		$sm = $this->getServiceLocator();
		$userTable = $sm->get('Application\Model\UserTable');
		$this->user = $userTable->tryGetUserByApiKey($apiKey);
		if($this->user == null){
			$this->getResponse()->setStatusCode(401);
			$this->getResponse()->setReasonPhrase("User not found.");
			return $this->getResponse();
		}
			
		return parent::onDispatch($e);
	}
	
    public function getUserAction() 
    {
    	$jsonModel = new JsonModel();
    	$jsonModel->id = $this->user->userId;
    	$jsonModel->name = $this->user->name;
    	$jsonModel->email = $this->user->email;
    	$jsonModel->urlName = $this->user->urlName;
    	$jsonModel->url = $this->url()->fromRoute('browse-user', array('url_name' => $this->user->userId, array('force_canonical' => true)));
    	$jsonModel->about = $this->user->about;
    	$jsonModel->createdOn = $this->user->createdOn;
    	return $jsonModel;
    }
    
    public function getUserSetsAction()
    {
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	
    	$sets = $setTable->getSetsByUser($this->user->userId, true);
    	//var_dump($sets);
    	
    	$jsonSets = array();
    	foreach($sets as $set) {
    		$jsonSet = new \stdClass();
    		$jsonSet->id = $set["setId"];
    		$jsonSet->name = $set["setName"];
    		$jsonSet->urlName = $set["setUrlName"];
    		$jsonSet->url = $this->url()->fromRoute('browse-set', array('url_name' => $set["setUrlName"]), array('force_canonical' => true));
    		$jsonSets[] = $jsonSet;
    	}
    	
    	$jsonModel = new JsonModel();
    	$jsonModel->sets = $jsonSets;
    	return $jsonModel;
    }
    
    public function resetUploadSession()
    {
    	$config = $this->getServiceLocator()->get('Config');
    	$dataDir = $config["data_dir"];
    	$targetPath = $dataDir . $userId . $ds . "temp" . $ds . "api" . $ds;
 		// TODO: Finish
    	
    	$jsonModel = new JsonModel();
    	return $jsonModel;
    }
    
    public function uploadCardImageAction()
    {
    	$sm = $this->getServiceLocator();
    	
    	/*$this->getResponse()->setStatusCode(401);
    	$this->getResponse()->setReasonPhrase("file ");
    	$this->getResponse()->setContent(var_export($_FILES, true));
    	return $this->getResponse();*/
    	
    	$userId = $this->user->userId;
    	
    	$config = $this->getServiceLocator()->get('Config');
    	$dataDir = $config["data_dir"];
    	
    	$response = $this->getResponse();
    	$headers = $response->getHeaders();
    	$headers->addHeaderLine('Content-Type', 'text/plain; charset=utf-8');
    	
    	$ds = DIRECTORY_SEPARATOR;
    	
    	if (!empty($_FILES)) {
    		$tempFile = $_FILES['file']['tmp_name'];
    			
    		$targetPath = $dataDir . $userId . $ds . "temp" . $ds . "api" . $ds;
    		if(!is_dir($targetPath)){
    			mkdir($targetPath, 0777, true);
    		}
    			
    		$targetFile =  $targetPath. $_FILES['file']['name'];
    		move_uploaded_file($tempFile,$targetFile); //6
    	}
    	 
    	$jsonModel = new JsonModel();
    	return $jsonModel;
    }
    
    public function uploadCardFileAction()
    {
    	$sm = $this->getServiceLocator();
    	
    	/*$this->getResponse()->setStatusCode(401);
    	$this->getResponse()->setReasonPhrase("file ");
    	$this->getResponse()->setContent(var_export($_FILES, true));
    	return $this->getResponse();*/
    	
    	$userId = $this->user->userId;
    	
    	$config = $this->getServiceLocator()->get('Config');
    	$dataDir = $config["data_dir"];
    	
    	$response = $this->getResponse();
    	$headers = $response->getHeaders();
    	$headers->addHeaderLine('Content-Type', 'text/plain; charset=utf-8');
    	
    	$ds = DIRECTORY_SEPARATOR;
    	
    	if (!empty($_FILES)) {
    		$tempFile = $_FILES['file']['tmp_name'];
    			
    		$targetPath = $dataDir . $userId . $ds . "temp" . $ds . "api" . $ds;
    		if(!is_dir($targetPath)){
    			mkdir($targetPath, 0777, true);
    		}
    			
    		$targetFile =  $targetPath. "set";
    		move_uploaded_file($tempFile,$targetFile); //6
    	}
    	 
    	$jsonModel = new JsonModel();
    	return $jsonModel;
    }
    
    public function finalizeSetUploadAction()
    {
    	$sm = $this->getServiceLocator();

    	$userId = $this->user->userId;
    	 
    	$config = $this->getServiceLocator()->get('Config');
    	$dataDir = $config["data_dir"];
    	 
    	$response = $this->getResponse();
    	$headers = $response->getHeaders();
    	$headers->addHeaderLine('Content-Type', 'text/plain; charset=utf-8');
    	
    	$clientReturnUrl = @$_POST["returnUrl"];
    	if($clientReturnUrl === null) 
    	{
    		$this->getResponse()->setStatusCode(401);
    		$this->getResponse()->setReasonPhrase("Return URL not provided.");
    		return $this->getResponse();
    	}
    	
    	$redirectUrl = $this->url()->fromRoute('member-area', array('action' => 'finish-set-import'), array('query' => array('return' => $clientReturnUrl, 'setId' => (int)@$_POST['setId']), 'force_canonical' => true));
    	
    	$loginUrl = $this->url()->fromRoute('member-area', array('action' => 'login'), array('query' => array('return' => $redirectUrl), 'force_canonical' => true));
    	
    	$jsonModel = new JsonModel();
    	$jsonModel->redirectRequired = true;
    	$jsonModel->redirectUrl = $loginUrl;
    	return $jsonModel;
    }
}
