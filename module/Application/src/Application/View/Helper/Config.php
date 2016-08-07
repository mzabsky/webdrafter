<?php
namespace Application\View\Helper;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface; 
/**
 * Helper to get the ZF2 config object
 * From the view:
 * 
 * $this->config() returns the whole config array
 * $this->config('a','b') will instead return config['a']['b'] and so on
 * 
 * @return string
 */
class Config extends AbstractHelper implements ServiceLocatorAwareInterface
{
    public function __invoke()
    {
        $config = $this->getServiceLocator()->getServiceLocator()->get('Config');
        foreach (func_get_args() as $arg) {
            if (!isset($config[$arg])) {
                throw new \RuntimeException("Config option ".implode('.', func_get_args())." not found");
            }
            $config = $config[$arg];
        }
        
        return $config;
    }
    
    /*
    * @return \Zend\ServiceManager\ServiceLocatorInterface
    */
    public function getServiceLocator()
    {
         return $this->serviceLocator;  
    }
    public function setServiceLocator(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;  
        return $this;  
    }
}