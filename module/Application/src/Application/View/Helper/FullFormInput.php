<?php
namespace Application\View\Helper;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface; 

class FullFormInput extends AbstractHelper implements ServiceLocatorAwareInterface
{
    public function __invoke($input, $prefix = null, $postfix = null, $delinkify = false)
    {
    	$str = '';
    	$str .= '<div class="form-element ' . (count($input->getMessages()) > 0 ? 'has-error' : '') . '">';
    	$str .= $this->view->formLabel($input);
        $str .= '<span class="description">' . $input->getOption('description') . '</span>';
    	$str .= $this->delinkyfy($prefix, $delinkify);
    	//$str .= '<div class="input-and-errors">';
    	if($input instanceof \Zend\Form\Element\TextArea)
    	{
    		$str .= $this->view->formTextArea($input);
    	}
    	else if($input instanceof \Zend\Form\Element\File)
    	{
    		$str .= $this->view->formFile($input);
    	}
    	else if($input instanceof \Zend\Form\Element\Select)
    	{
    		$str .= $this->view->formElement($input);
    	}
    	else if($input instanceof \Zend\Form\Element\Radio)
    	{
    		$str .= $this->view->formRadio($input);
    	}
    	else if($input instanceof \Zend\Form\Element)
    	{
    		$str .= $this->view->formInput($input);
    	}
    	$str .= $postfix;
    	
    	$str .= $this->view->formElementErrors($input);    	
        $str .= '</div>';
        return $str;
    }
    
    // Prevent linkify-style browser plugin from highlighting this
    private function delinkyfy($str, $delinkify){
    	if(!$delinkify) return $str;
    	
    	$strNew = "";
    	for($i = 0; $i < strlen($str); $i++){
    		$strNew .= '<font>' . $str[$i]. '</font>';
    	}
    	return $strNew;
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