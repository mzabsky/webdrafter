<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
        	'draft' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/draft[/:invite_key][/:action]',
                    'constraints' => array(
                        'invite_key' => '[a-zA-Z0-9]+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
       				'defaults' => array(
      					'controller' => 'Application\Controller\Draft',
     					'action'     => 'index',
        			),
        		),
        	),  
        	'lobby' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/lobby[/:lobby_key][/:action]',
                    'constraints' => array(
                        'lobby_key' => '[a-zA-Z0-9]+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
       				'defaults' => array(
      					'controller' => 'Application\Controller\Lobby',
     					'action'     => 'index',
        			),
        		),
        	), 
        	'member-area' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/member-area[/:action]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
       				'defaults' => array(
      					'controller' => 'Application\Controller\MemberArea',
     					'action'     => 'index',
        			),
        		),
        	),   
        	'member-area-with-draft-id' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/member-area/:action/:draft_id',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]+',
                        'draft_id' => '[0-9]+'
                    ),
       				'defaults' => array(
      					'controller' => 'Application\Controller\MemberArea',
       					'action' => 'draft-admin'
        			),
        		),
        	),     
        	'member-area-manage-set' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/member-area/:action/:set_id',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]+',
                        'set_id' => '[0-9]+'
                    ),
       				'defaults' => array(
      					'controller' => 'Application\Controller\MemberArea',
       					'action' => 'manage-set'
        			),
        		),
        	),      
        	'browse' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/explore',
                    'constraints' => array(
                    ),
       				'defaults' => array(
      					'controller' => 'Application\Controller\Browse',
     					'action'     => 'index',
        			),
        		),
        	),   

       		'autocard' => array(
   				'type' => 'Segment',
  				'options' => array(
  					'route' => '/autocard',
  					'constraints' => array(
    					'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
    				),
    				'defaults' => array(
    					'controller' => 'Application\Controller\Browse',
    					'action'     => 'autocard',
    				),
    			),
      		),
        	'browse-set' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/set/:url_name',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    	'set_name' => '[a-zA-Z][a-z0-9-]*'
                    ),
       				'defaults' => array(
      					'controller' => 'Application\Controller\Browse',
     					'action'     => 'set',
        			),
        		),
        	),       
        	'browse-version' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/set/:url_name/:version_url_name',
        			'constraints' => array(
        				'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
        				'set_version_name' => '[a-zA-Z][a-z0-9-]*',
        				'card_name' => '.*'
        			),
        			'defaults' => array(
        				'controller' => 'Application\Controller\Browse',
        				'action'     => 'setVersion',
        			),
        		),
        	),        		
        	'browse-card' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/set/:set_url_name/:version_url_name/:card_url_name',
        			'constraints' => array(
        				'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
        				'set_url_name' => '[a-zA-Z][a-z0-9-]*',
        				'version_url_name' => '[a-zA-Z][a-z0-9-]*',
        				'card_url_name' => '[a-zA-Z][a-z0-9-]*'
        			),
        			'defaults' => array(
        				'controller' => 'Application\Controller\Browse',
        				'action'     => 'card',
        			),
        		),
        	),
        	'browse-user' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/user/:url_name',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    	'url_name' => '[a-zA-Z][a-z0-9-]*'
                    ),
       				'defaults' => array(
      					'controller' => 'Application\Controller\Browse',
     					'action'     => 'user',
        			),
        		),
        	),  
        	'generate-pool' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/generator/sealed-pool/:set_id',
                    'constraints' => array(
                    	'set_id' => '[0-9]+'
                    ),
       				'defaults' => array(
      					'controller' => 'Application\Controller\Generator',
     					'action'     => 'sealed-pool',
        			),
        		),
        	),
        	'tutorial' => array(
        		'type' => 'Segment',
        		'options' => array(
        			'route' => '/tutorial',
       				'defaults' => array(
      					'controller' => 'Application\Controller\Tutorial',
     					'action'     => 'index',
        			),
        		),
        	),    
            'application' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/application',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Application\Controller\Index' => 'Application\Controller\IndexController',
            'Application\Controller\Draft' => 'Application\Controller\DraftController',
            'Application\Controller\MemberArea' => 'Application\Controller\MemberAreaController',
            'Application\Controller\Browse' => 'Application\Controller\BrowseController',
            'Application\Controller\Generator' => 'Application\Controller\GeneratorController',
            'Application\Controller\Lobby' => 'Application\Controller\LobbyController',
            'Application\Controller\Tutorial' => 'Application\Controller\TutorialController'
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
		'strategies' => array(
    		'ViewJsonStrategy',
    	),
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
            ),
        ),
    ),
);
