<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\ResultSet\HydratingResultSet;

class Module
{
	private $googleAuthentication;
	
    public function onBootstrap(MvcEvent $e)
    {
        $instanceId = mt_rand();

        $eventLogger = new \Zend\Log\Logger;
        $eventLoggerWriter = new \Zend\Log\Writer\Stream('./logs/events-'.date('Y-m-d').'.log');
        $eventLogger->addWriter($eventLoggerWriter);

        $errorLogger = new \Zend\Log\Logger;
        $errorLoggerWriter = new \Zend\Log\Writer\Stream('./logs/errors-'.date('Y-m-d').'.log');
        $errorLogger->addWriter($errorLoggerWriter);
        
        \Zend\Log\Logger::registerErrorHandler($errorLogger);
        \Zend\Log\Logger::registerExceptionHandler($errorLogger);

        /*
    	$sm = $e->getApplication()->getServiceManager();
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $eventManager->attach(
            '*',
            function ($e) use($eventLogger, $instanceId)
            {
                $event = $e->getName();
                $target = get_class($e->getTarget());
                $params = $e->getParams();
                $output = sprintf(
                        '%s %s %s %s %s %s %s',
                        str_pad($instanceId, 10),
                        str_pad($_SERVER['REQUEST_URI'], 50),
                        str_pad($_SESSION["user_id"],5),
                        $_SERVER['REMOTE_ADDR'],
                        str_pad($event, 15),
                        str_pad($target, 50),
                        json_encode($params));
                
                $eventLogger->log(\Zend\Log\Logger::INFO, $output);

                return true;
            });*/
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                    'League' => __DIR__ . '/../../vendor/League',
                    'Guzzle' => __DIR__ . '/../../vendor/Guzzle',
                    'Symfony' => __DIR__ . '/../../vendor/Symfony',
                    'Michelf' => __DIR__ . '/../../vendor/Michelf'
                ),
            ),
        );
    }

	public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Application\GoogleAuthentication' =>  function($sm) {
                	if($this->googleAuthentication == null)
                	{
                		$this->googleAuthentication = new \Application\GoogleAuthentication($sm);
                	}
            		return $this->googleAuthentication;
                },
                'Application\Model\Set' =>  function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            		$user = new \Application\Model\Set();
            		$user->setDbAdapter($dbAdapter);
            		return $user;
                },
                'Application\Model\SetVersion' =>  function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            		$user = new \Application\Model\SetVersion();
            		$user->setDbAdapter($dbAdapter);
            		return $user;
                },
                'Application\Model\SetTable' =>  function($sm) {
                    $tableGateway = $sm->get('SetTableGateway');
                    $table = new \Application\Model\SetTable($tableGateway);
                    return $table;
                },
                'SetTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype($sm->get('Application\Model\Set'));
                    return new TableGateway('set', $dbAdapter, null, $resultSetPrototype);
                },
                'Application\Model\SetVersionTable' =>  function($sm) {
                    $tableGateway = $sm->get('SetVersionTableGateway');
                    $table = new \Application\Model\SetVersionTable($tableGateway);
                    return $table;
                },
                'SetVersionTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype($sm->get('Application\Model\SetVersion'));
                    return new TableGateway('set_version', $dbAdapter, null, $resultSetPrototype);
                },
                'Application\Model\CardTable' =>  function($sm) {
                    $tableGateway = $sm->get('CardTableGateway');
                    $table = new \Application\Model\CardTable($tableGateway);
                    return $table;
                },
                'CardTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new \Application\Model\Card());
                    return new TableGateway('card', $dbAdapter, null, $resultSetPrototype);
                },
                'Application\Model\DraftTable' =>  function($sm) {
                    $tableGateway = $sm->get('DraftTableGateway');
                    $table = new \Application\Model\DraftTable($tableGateway);
                    return $table;
                },
                'DraftTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new \Application\Model\Draft());
                    return new TableGateway('draft', $dbAdapter, null, $resultSetPrototype);
                },
                'Application\Model\DraftSetVersionTable' =>  function($sm) {
                    $tableGateway = $sm->get('DraftSetVersionTableGateway');
                    $table = new \Application\Model\DraftSetVersionTable($tableGateway);
                    return $table;
                },
                'DraftSetVersionTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new \Application\Model\DraftSetVersion());
                    return new TableGateway('draft_set_version', $dbAdapter, null, $resultSetPrototype);
                },
                'Application\Model\DraftPlayerTable' =>  function($sm) {
                    $tableGateway = $sm->get('DraftPlayerTableGateway');
                    $table = new \Application\Model\DraftPlayerTable($tableGateway);
                    return $table;
                },
                'DraftPlayerTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new \Application\Model\DraftPlayer());
                    return new TableGateway('draft_player', $dbAdapter, null, $resultSetPrototype);
                },
                'Application\Model\PickTable' =>  function($sm) {
                    $tableGateway = $sm->get('PickTableGateway');
                    $table = new \Application\Model\PickTable($tableGateway);
                    return $table;
                },
                'PickTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new \Application\Model\Pick());
                    return new TableGateway('pick', $dbAdapter, null, $resultSetPrototype);
                },
                'Application\Model\User' =>  function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            		$user = new \Application\Model\User();
            		$user->setDbAdapter($dbAdapter);
            		return $user;
                },
                'Application\Model\UserTable' =>  function($sm) {
                    $tableGateway = $sm->get('UserTableGateway');
                    $table = new \Application\Model\UserTable($tableGateway);
                    return $table;
                },
                'UserTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype($sm->get('Application\Model\User'));
                    return new TableGateway('user', $dbAdapter, null, $resultSetPrototype);
                },
                'Application\Model\DraftPlayerBasicTable' =>  function($sm) {
                    $tableGateway = $sm->get('DraftPlayerBasicTableGateway');
                    $table = new \Application\Model\DraftPlayerBasicTable($tableGateway);
                    return $table;
                },
                'DraftPlayerBasicTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new \Application\Model\DraftPlayerBasic());
                    return new TableGateway('draft_player_basic', $dbAdapter, null, $resultSetPrototype);
                },
                'Application\Model\CollationTable' =>  function($sm) {
                    $tableGateway = $sm->get('CollationTableGateway');
                    $table = new \Application\Model\CollationTable($tableGateway);
                    return $table;
                },
                'CollationTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new \Application\Model\Collation());
                    return new TableGateway('collation', $dbAdapter, null, $resultSetPrototype);
                },
            )
        );
     }
     
     public function getViewHelperConfig()
     {
     	return array(
     			'factories' => array(
     					'auth' => function($sm) {
     						$helper = new \Application\View\Helper\Auth() ;
     						return $helper;
     					},
     					'config' => function($sm) {
     						$helper = new \Application\View\Helper\Config() ;
     						return $helper;
     					},
     					'fullFormInput' => function($sm) {
     						$helper = new \Application\View\Helper\FullFormInput() ;
     						return $helper;
     					},
     					'markdown' => function($sm) {
     						$helper = new \Application\View\Helper\Markdown() ;
     						return $helper;
     					},
     					'symbols' => function($sm) {
     						$helper = new \Application\View\Helper\Symbols() ;
     						return $helper;
     					},
     					'wideMode' => function($sm) {
     						$helper = new \Application\View\Helper\WideMode() ;
     						return $helper;
     					},
     					'fullArtUrl' => function($sm) {
     						$helper = new \Application\View\Helper\FullArtUrl() ;
     						return $helper;
     					}
     			)
     	);
     }
}

function resultSetToArray($resultSet)
{
	$a = array();
	foreach($resultSet as $item)
	{
		$a[] = $item;		
	}
	return $a;
}

function toUrlName($str){
	$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
	$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
	$clean = strtolower(trim($clean, '-'));
	$clean = preg_replace("/[\/_|+ -]+/", '-', $clean);

	return $clean;
}