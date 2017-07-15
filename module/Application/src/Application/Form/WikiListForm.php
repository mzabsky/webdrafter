<?php

namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element\Text;
use Zend\Form\Element\File;

class WikiListForm extends Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		//$this->setMethod("post");

		$this->setName('wiki_list');

		$factory = new \Zend\Form\Factory();

		$this->add($factory->createElement(array(
				'name' => 'sets',
				'type' => 'textarea',
				/*'attributes' => array(
					'value' => 'url-name-input',
				),*/
				'options' => array(
						'label' => 'Sets: ',
						'description' => 'List of set URL names, new line separated.',
						'value' => 'aernyr,dreamscape,ankheret,lorado,tesla'
				)
		)));

		$this->add($factory->createElement(array(
				'name' => 'cards',
				'type' => 'textarea',
				/*'attributes' => array(
					'class' => 'url-name-input',
				),*/
				'options' => array(
						'label' => 'Deck list: ',
						'description' => 'Deck list in cockatrice format (<a href="https://gist.github.com/mzabsky/69bd4f353bd535e690904db3e1648f7e">example</a>)'
				)
		)));

		$this->add($factory->createElement(array(
				'name' => 'submit',
				'attributes' => array(
				    'value' => 'Submit'
				),
				'label' => 'Generate',
				'type' => 'submit',
				'required' => true,
		)));

		//$this->addElements(array($name, $code, $infoUrl, $artUrl, $file));
	}
}