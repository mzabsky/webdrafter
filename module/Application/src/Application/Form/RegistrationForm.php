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
	
	public function __construct($options = null)
	{
		parent::__construct($options);
		//$this->setMethod("post");
		
		$this->setName('register');

		$factory = new \Zend\Form\Factory();	

		
		$this->add($factory->createElement(array(
			'name' => 'name',
			'options' => array(
				'label' => 'Display name: ',
				'description' => 'Name that will represent you on the site. Must be unique. Cannot be changed.'
			)
		)));
		
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
				'description' => 'Arbitrary text displayed in your profile. Formatted with <a href="https://help.github.com/articles/markdown-basics/">Markdown</a>. Can be changed at any time. Up to 5000 characters.'
			),
		)));

		$this->add($factory->createElement(array(
			'name' => 'submit',
            'attributes' => array(
                'value' => 'Register',
            ),
			'type' => 'submit',
			'required' => true,
		)));

		//$this->addElements(array($name, $code, $infoUrl, $artUrl, $file));
	}
}