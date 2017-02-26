<?php
namespace Application\View\Helper;

use Traversable;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\RouteStackInterface;
use Zend\View\Exception;

require_once __DIR__."/MarkdownParser.php";

class Markdown extends \Zend\View\Helper\AbstractHelper
{
    public function __invoke($text, $contextSetUrlName = null, $contextSetVersionUrlName = null)
    {
    	return '<div class="markdown-content">' . processMarkdown($text, $contextSetUrlName, $contextSetVersionUrlName) . '</div>';
    }
}
