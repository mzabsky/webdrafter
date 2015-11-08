<?php

namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element\Text;
use Zend\Form\Element\File;

class RegistrationForm extends Form
{
	const EMAIL_PRIVACY_PUBLIC = 1;
	const EMAIL_PRIVACY_REGISTERED_ONLY = 2;
	const EMAIL_PRIVACY_PRIVATE = 3;
	
	private $includeName;
	
	public function isNameIncluded(){
		return $this->includeName;
	}
	
	public function __construct($includeName)
	{
		parent::__construct();
		//$this->setMethod("post");

		$this->includeName = $includeName;
		
		$this->setName('register');

		$factory = new \Zend\Form\Factory();	

		if($includeName){
			$this->add($factory->createElement(array(
					'name' => 'name',
					'options' => array(
							'label' => 'Display name: ',
							'description' => 'Name that will represent you on the site. Must be unique. Cannot be changed.'
					)
			)));
			
			$this->add($factory->createElement(array(
				'name' => 'url_name',
				'attributes' => array(
						'class' => 'url-name-input',
				),
				'options' => array(
					'label' => 'URL name: ',
					'description' => 'URL name used for your public profile. Can only contain lower case english alphabet letters, numbers and minus sign. Must be unique. Cannot be changed.'
				)
			)));
		}	
		
		$this->add($factory->createElement(array(
			'name' => 'email_privacy',
			'type' => 'Zend\Form\Element\Select',
			'options' => array(
				'label' => 'Display my email to: ',
				'description' => 'If you allow it, your email address will be displayed on your profile page. Can be changed at any time.',
				'value_options' => [
						RegistrationForm::EMAIL_PRIVACY_PUBLIC => 'Everyone',
						RegistrationForm::EMAIL_PRIVACY_REGISTERED_ONLY => 'Registered users only',
						RegistrationForm::EMAIL_PRIVACY_PRIVATE => 'Noone',
				],
			)
		)));
		
		$this->add($factory->createElement(array(
			'name' => 'about',
			'type' => 'textarea',
			'options' => array(
				'label' => 'About me: ',
				'description' => 'Arbitrary text displayed in your profile. Formatted with <a href="https://help.github.com/articles/markdown-basics/" target="blank">Markdown</a>. Can be changed at any time. Up to 5000 characters.'
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

		//$this->addElements(array($name, $code, $infoUrl, $artUrl, $file));
	}
}