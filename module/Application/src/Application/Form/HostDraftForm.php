<?php

namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element\Text;
use Zend\Form\Element\File;
use Application\Model\SetTable;

class HostDraftForm extends Form
{
	public function __construct(SetTable $setTable)
	{
		parent::__construct(null);
		//$this->setMethod("post");
		
		$this->setName('host_draft');

		$factory = new \Zend\Form\Factory();	
		
		$packOptions = array();
		
    	$sets = $setTable->fetchAll();
		
    	foreach($sets as $set)
    	{
    		$packOptions[$set->setId] = $set->code . " - " . $set->name;
    	}
    	
		$this->add($factory->createElement(array(
			'name' => 'pack1',
			'type' => 'Zend\Form\Element\Select',
			'required' => true,
			'options' => array(
				'label' => 'Pack #1',
				'value_options' => $packOptions,
				//'description' => 'Set for the first pack.'
			),
		)));

		$this->add($factory->createElement(array(
				'name' => 'pack2',
				'type' => 'Zend\Form\Element\Select',
				'required' => true,
				'options' => array(
						'label' => 'Pack #2',
						'value_options' => $packOptions,
						//'description' => 'Set for the first pack.'
				),
		)));
		
		$this->add($factory->createElement(array(
				'name' => 'pack3',
				'type' => 'Zend\Form\Element\Select',
				'required' => true,
				'options' => array(
						'label' => 'Pack #3',
						'value_options' => $packOptions,
						//'description' => 'Set for the first pack.'
				),
		)));
		
		/*$this->add($factory->createElement(array(
				'name' => 'mode',
				'type' => 'Zend\Form\Element\Radio',
				'required' => false,
				'allow_empty' => true,
				'attributes' => array(
					'disabled' => 'disabled',
					'value' => 1
				),
				'options' => array(
						'label' => 'Game mode',
						'value_options' => array(
							1 => 'Booster draft',
							2 => 'Cube draft',
							3 => 'Sealed deck',
						),
						'description' => '(Only Booster draft is currently supported)'
				),
		)));*/
		$this->add($factory->createElement(array(
			'name' => 'submit',
            'attributes' => array(
                'value' => 'Open the draft',
            ),
			'options' => array(
				'description' => 'Once the draft is opened, you can generate invite links and allow players to join.'
			),
			'type' => 'submit',
			'required' => true,
		)));

		//$this->addElements(array($name, $code, $infoUrl, $artUrl, $file));
	}
}