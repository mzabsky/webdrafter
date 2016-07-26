<?php
namespace Application\View\Helper;

use Traversable;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\RouteStackInterface;
use Zend\View\Exception;

/**
 * Helper for making easy links and getting urls that depend on the routes and router.
 */
class WideMode extends \Zend\View\Helper\AbstractHelper
{
	private $mode = false;
	
    public function __invoke($mode = null)
    {
    	if($mode === null){
    		return $this->mode;
    	}
    	else {
    		$this->mode = $mode;	
    	}
    }
}
