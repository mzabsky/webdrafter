<?php
namespace Application\View\Helper;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface; 

class FullFormInput extends AbstractHelper implements ServiceLocatorAwareInterface
{
    public function __invoke($input)
    {
    	$str = '';
    	$str .= '<div class="form-element">';
    	$str .= $this->view->formLabel($input);
        $str .= '<span class="description">' . $input->getOption('description') . '</span>';
    	//$str .= '<div class="input-and-errors">';
    	if($input instanceof \Zend\Form\Element\TextArea)
    	{
    		$str .= $this->view->formTextArea($input);
    	}
    	else if($input instanceof \Zend\Form\Element)
    	{
    		$str .= $this->view->formInput($input);
    	}
    	
    	$str .= $this->view->formElementErrors($input);    	
        $str .= '</div>';
        return $str;
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