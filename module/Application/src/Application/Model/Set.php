<?php

namespace Application\Model;


use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Set implements InputFilterAwareInterface
{
	public $setId;
	public $name;
	public $code;
	public $url;
	public $userId;
	public $isRetired;
	public $downloadUrl;
	
	private $inputFilter;
	private $dbAdapter;
	
    public function exchangeArray($data)
    {
        $this->setId     = (!empty($data['set_id'])) ? $data['set_id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->code = (!empty($data['code'])) ? $data['code'] : null;
        $this->url = (!empty($data['url'])) ? $data['url'] : null;
        $this->userId = (!empty($data['user_id'])) ? $data['user_id'] : null;
        $this->isRetired = $data['is_retired'];
        $this->downloadUrl = (!empty($data['download_url'])) ? $data['download_url'] : null;
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
    						'table' => 'set',
    						'field' => 'name',
    						'adapter' => $this->dbAdapter,
    					),
    				),
    			),
    		));
    
    		$inputFilter->add(array(
    			'name'     => 'code',
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
							'min'      => 3,
 							'max'      => 5,
    					),
    				),
    			),
    		));
    
    		$inputFilter->add(array(
    			'name'     => 'url',
    			'required' => false,
    			'filters'  => array(
    				array('name' => 'StringTrim'),
    			),
    			'validators' => array(
    				array(
    					'name'    => 'StringLength',
    					'options' => array(
    						'encoding' => 'UTF-8',
    						'min'      => 1,
    						'max'      => 255,
    					),
    				),
    				array(
    					'name'    => 'Uri',
    				),
    			),
    		));
    
    		$inputFilter->add(array(
    			'name'     => 'download_url',
    			'required' => false,
    			'filters'  => array(
    				array('name' => 'StringTrim'),
    			),
    			'validators' => array(
    				array(
    					'name'    => 'StringLength',
    					'options' => array(
    						'encoding' => 'UTF-8',
    						'min'      => 1,
    						'max'      => 255,
    					),
    				),
    				array(
    					'name'    => 'Uri',
    				),
    			),
    		));
    
    		$inputFilter->add(array(
    			'name'     => 'art_url',
    			'required' => true,
    			'filters'  => array(
    				array('name' => 'StringTrim'),
    			),
    			'validators' => array(
    				array(
    					'name'    => 'StringLength',
    					'options' => array(
    						'encoding' => 'UTF-8',
    						'min'      => 1,
    						'max'      => 255,
    					),
    				),
    				array(
    					'name'    => 'Uri',
    				),
    			),
    		));
    
    		$inputFilter->add(array(
    			'name'     => 'file',
    			'required' => true,
    			'filters'  => array(
    				//array('name' => 'StringTrim'),
    			),
    			'validators' => array(
    				array(
    					'name'    => 'File\Size',
    					'options' => array(
    						'max' => '1MB'
    					),
    				),
    				array(
    					'name'    => 'File\UploadFile',
    					'options' => array(),
    				),
    			),
    		));
    
    		$this->inputFilter = $inputFilter;
    	}
    
    	return $this->inputFilter;
    }
}