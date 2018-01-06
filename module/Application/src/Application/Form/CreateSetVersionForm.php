<?php

namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element\Text;
use Zend\Form\Element\File;
use Application\Model\SetVersion;

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
				'name' => 'basic_land_slot',
				'type' => 'Zend\Form\Element\Select',
				'required' => true,
				'options' => array(
						'label' => 'Basic land slot:',
						'description' => 'Determines which cards are put into the basic land slot in a booster pack. Has no effect in cube-type events.',
						'value_options' => [
								SetVersion::BASIC_LAND_SLOT_BASIC_LAND => 'Basic land (doesn\'t appear in draft)',
								SetVersion::BASIC_LAND_SLOT_NONBASIC_LAND => 'Nonbasic land (weighed by rarity)',
								SetVersion::BASIC_LAND_SLOT_SPECIAL => 'Special rarity card',
								SetVersion::BASIC_LAND_SLOT_DFC => 'Double faced card (weighed by rarity)',
								SetVersion::BASIC_LAND_SLOT_TYPE => 'Card with specific string in its type line (weighed by rarity)',
								SetVersion::BASIC_LAND_SLOT_RULES_TEXT => 'Card with specific string in its rules text (weighed by rarity)',
						],
				),
		)));

		$this->add($factory->createElement(array(
				'name' => 'basic_land_slot_needle',
				/*'options' => array(
						'label' => 'Download URL:',
						'description' => 'URL from which the players can download the files necessary to play this version of set (such as Cockatrice package). Cannot be changed later.'
				),*/
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