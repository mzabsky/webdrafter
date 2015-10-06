<?php
namespace Application\View\Helper;

use Traversable;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\RouteStackInterface;
use Zend\View\Exception;

class Markdown extends \Zend\View\Helper\AbstractHelper
{
    public function __invoke($text)
    {
    	return '<div class="markdown-content">' . \Michelf\Markdown::defaultTransform($text) . '</div>';
    }
}
