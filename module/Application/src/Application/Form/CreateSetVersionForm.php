<?php

namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element\Text;
use Zend\Form\Element\File;

class CreateSetVersionForm extends Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		//$this->setMethod("post");
		
		$this->setName('create_set');
		$this->setAttribute('enctype', 'multipart/form-data');

		$factory = new \Zend\Form\Factory();	
		
		$this->add($factory->createElement(array(
			'name' => 'name',
			'options' => array(
				'label' => 'Name: ',
				'description' => 'Representative name of this set version. Cannot be changed later.'
			)
		)));
		
		$this->add($factory->createElement(array(
			'name' => 'url_name',
			'attributes' => array(
					'class' => 'url-name-input',
			),
			'options' => array(
				'label' => 'URL name: ',
				'description' => 'Name used in URLs to this set version. Can only contain lower case english alphabet letters, numbers and minus sign. Must be unique.'
			)
		)));
		
		$this->add($factory->createElement(array(
				'name' => 'download_url',
				'type' => 'url',
				'options' => array(
						'label' => 'Download URL:',
						'description' => 'URL from which the players can download the files necessary to play this version of set (such as Cockatrice package). Cannot be changed later.'
				),
		)));
		
		$this->add($factory->createElement(array(
			'name' => 'about',
			'type' => 'textarea',
			'options' => array(
				'label' => 'About the version: ',
				'description' => 'Arbitrary description of this version. Can contain information such as change log. <a href="/tutorial#formatting" target="blank">Formatting help</a>. Can be changed at any time. Up to 50000 characters.'
			),
		)));
		
		$this->add($factory->createElement(array(
			'name' => 'submit',
            'attributes' => array(
                'value' => 'Submit',
            ),
			'type' => 'submit',
			'required' => true,
		)));
	}
}