<?php
namespace Application\View\Helper;

use Traversable;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\RouteStackInterface;
use Zend\View\Exception;

class FullArtUrl extends \Zend\View\Helper\AbstractHelper
{
    public function __invoke($text, $contextSetUrlName = null, $contextSetVersionUrlName = null)
    {
    	if($text[0] == "/") return $this->getView()->url("home", array(), array('force_canonical' => true)) . substr($text, 1, strlen($text) - 1);
    	return $text;
    }
}
