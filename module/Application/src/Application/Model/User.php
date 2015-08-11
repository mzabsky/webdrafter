<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class User implements InputFilterAwareInterface
{
	public $userId;
	public $email;
	public $name;
	public $emailPrivacy;
	public $about;
	
	private $inputFilter;
	private $dbAdapter;
	
    public function exchangeArray($data)
    {
        $this->userId     = (!empty($data['user_id'])) ? $data['user_id'] : null;
        $this->email = (!empty($data['email'])) ? $data['email'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->emailPrivacy = (!empty($data['email_privacy'])) ? $data['email_privacy'] : null;
        $this->about = (!empty($data['about'])) ? $data['about'] : null;
    }
    
    public function setDbAdapter(\Zend\Db\Adapter\Adapter $adapter)
    {
    	$this->dbAdapter = $adapter;
    }
    
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
    	throw new \Exception("Not used");
    }
    
    public function getInputFilter()
    {
    	if (!$this->inputFilter) {
    		$inputFilter = new InputFilter();
    
    		$inputFilter->add(array(
    				'name'     => 'name',
    				'required' => true,
    				'filters'  => array(
    						array('name' => 'StripTags'),
    						array('name' => 'StringTrim'),
    				),
    				'validators' => array(
    						array(
    								'name'    => 'StringLength',
    								'options' => array(
    	    					'encoding' => 'UTF-8',
    										'min'      => 1,
    										'max'      => 40,
    								),
    						),
    						array(
    								'name'    => 'Db\NoRecordExists',
    								'options' => array(
    										'table' => 'user',
    										'field' => 'name',
    										'adapter' => $this->dbAdapter,
    								),
    						),
    				),
    		));
    
    		$inputFilter->add(array(
    				'name'     => 'about',
    				'required' => false,
    				'filters'  => array(
    						array('name' => 'StripTags'),
    				),
    				'validators' => array(
    						array(
    								'name'    => 'StringLength',
    								'options' => array(
    										'encoding' => 'UTF-8',
    										'min'      => 0,
    										'max'      => 5000,
    								),
    						),
    				),
    		));
    
    		$this->inputFilter = $inputFilter;
    	}
    
    	return $this->inputFilter;
    }
}