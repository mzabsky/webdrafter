<?php

namespace Application\Model;


use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class SetVersion implements InputFilterAwareInterface
{
	public $setVersionId;
	public $name;
	public $urlName;
	public $setId;
	public $about;
	public $createdOn;
	public $downloadUrl;
	
	private $inputFilter;
	private $dbAdapter;
	
	public function getArray(){
		return array(
			'set_version_id' => $this->setVersionId,
			'name' => $this->name,
			'url_name' => $this->urlName,
			'set_id' => $this->setId,
			'created_on' => $this->createdOn,
			'about' => $this->about,
		);
	}
	
    public function exchangeArray($data)
    {
        $this->setVersionId     = (!empty($data['set_version_id'])) ? $data['set_version_id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->urlName = (!empty($data['url_name'])) ? $data['url_name'] : null;
        $this->setId = (!empty($data['set_id'])) ? $data['set_id'] : null;
        $this->about = (!empty($data['about'])) ? $data['about'] : null;
        $this->createdOn = (!empty($data['created_on'])) ? $data['created_on'] : null;
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
    						'table' => 'set_version',
    						'field' => 'name',
    						'adapter' => $this->dbAdapter,
							'exclude' => '(set_id = ' . ((int)$this->setId) . ' AND  set_version_id != ' . ((int)$this->setVersionId) . ')'
    					)
    				),
    			),
    		));
    		
    		$inputFilter->add(array(
    				'name'     => 'url_name',
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
    								'name'    => 'Regex',
    								'options' => array(
    										'pattern' => '/^[a-z][a-z0-9\-]+$/',
    										'messages' => array(
    												'regexNotMatch' => 'URL name must start with a lower case english alphabet letter, and can only contain lower case english alphabet letters, numbers and minus sign'
    										),
    								),
    						),
    						array(
    								'name'    => 'Db\NoRecordExists',
    								'options' => array(
    										'table' => 'set_version',
    										'field' => 'url_name',
    										'adapter' => $this->dbAdapter,
    										'exclude' => '(set_id = ' . ((int)$this->setId) . ' AND  set_version_id != ' . ((int)$this->setVersionId) . ')'
    								),
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