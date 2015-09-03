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
class Auth extends \Zend\View\Helper\AbstractHelper
{
    protected $googleAuthentication;

    public function __invoke()
    {
        if ($this->googleAuthentication == null) {
            $sm = $this->getView()->getHelperPluginManager()->getServiceLocator();
            $this->googleAuthentication  = $sm->get('Application\GoogleAuthentication');
        }

        return $this->googleAuthentication;
    }
}
