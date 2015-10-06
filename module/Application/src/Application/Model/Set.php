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
	public $userId;
	public $about;
	public $createdOn;
	public $status;
	public $isPrivate;
	public $currentSetVersionId;
	
	const STATUS_UNPLAYABLE = 1;
	const STATUS_DESIGN = 2;
	const STATUS_DEVELOPMENT = 3;
	const STATUS_FINISHING = 4;
	const STATUS_FINISHED = 5;
	const STATUS_DISCONTINUED = 6;
	
	private $inputFilter;
	private $dbAdapter;
	
	public function getArray(){
		return array(
			'set_id' => $this->setId,
			'name' => $this->name,
			'code' => $this->code,
			'user_id' => $this->userId,
			'about' => $this->about,
			'created_on' => $this->createdOn,
			'status' => $this->status,
			'is_private' => $this->isPrivate,
			'current_set_version_id' => $this->currentSetVersionId
		);
	}
	
	public function getStatusName()
	{
		switch($this->status)
		{
			case self::STATUS_UNPLAYABLE: return "Unplayable";
			case self::STATUS_DESIGN: return "Design";
			case self::STATUS_DEVELOPMENT: return "Development";
			case self::STATUS_FINISHING: return "Finishing";
			case self::STATUS_FINISHED: return "Finished";
			case self::STATUS_DISCONTINUED: return "Discontinued";
		}
	}
	
    public function exchangeArray($data)
    {
        $this->setId     = (!empty($data['set_id'])) ? $data['set_id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->code = (!empty($data['code'])) ? $data['code'] : null;
        $this->userId = (!empty($data['user_id'])) ? $data['user_id'] : null;
        $this->about = (!empty($data['about'])) ? $data['about'] : null;
        $this->createdOn = (!empty($data['created_on'])) ? $data['created_on'] : null;
        $this->status = (!empty($data['status'])) ? $data['status'] : null;
        $this->isPrivate = (!empty($data['is_private'])) ? $data['is_private'] : null;
        $this->currentSetVersionId = (!empty($data['current_set_version_id'])) ? $data['current_set_version_id'] : null;
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
    						'exclude' => array
    						(
					            'field' => 'set_id',
					            'value' => $this->setId
					        )
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
    
    		/*$inputFilter->add(array(
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
    		));*/
    
    		$this->inputFilter = $inputFilter;
    	}
    
    	return $this->inputFilter;
    }
}