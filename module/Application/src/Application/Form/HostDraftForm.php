<?php

namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element\Text;
use Zend\Form\Element\File;
use Application\Model\SetTable;

class HostDraftForm extends Form
{
	public function __construct(SetTable $setTable, $mode)
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
    			'name' => 'mode',
    			'type' => 'Zend\Form\Element\Hidden',
    			'required' => true,
			    'attributes' => array(
			         'value' => $_REQUEST['mode']
			    )
    	)));

    	if(isset($_REQUEST['number_of_packs']))
    	{
    		$numberOfPacks = (int)$_REQUEST['number_of_packs'];
    	}
    	else {
    		switch($mode)
    		{
    			case \Application\Model\Draft::MODE_BOOSTER_DRAFT:
    				$numberOfPacks = 3;
    				break;
    			case \Application\Model\Draft::MODE_CHAOS_DRAFT:
    			case \Application\Model\Draft::MODE_CUBE_DRAFT:
    				$numberOfPacks = 1;
    				break;
    			case \Application\Model\Draft::MODE_SEALED_DECK:
    				$numberOfPacks = 6;
    				break;
    			default:
    				throw new \Exception("Invalid game mode " . $mode);
    					
    		}
    		
    	}
    	
    	$this->add($factory->createElement(array(
    			'name' => 'number_of_packs',
    			'type' => 'Zend\Form\Element\Hidden',
    			'required' => true,
    			'attributes' => array(
    					'value' => $numberOfPacks
    			)
    	)));
    	
    	for($i = 1; $i <= $numberOfPacks; $i++)
    	{
    		$this->add($factory->createElement(array(
    				'name' => 'pack' . $i,
    				'type' => 'Zend\Form\Element\Select',
    				'required' => true,
    				'options' => array(
    						'label' => 'Pack #' . $i,
    						'value_options' => $packOptions,
    						//'description' => 'Set for the first pack.'
    				),
    				'attributes' => array(
    						'multiple' => $mode == \Application\Model\Draft::MODE_CHAOS_DRAFT ? 'multiple' : '0',
    				),
    		)));    			
    	}
		
		$this->add($factory->createElement(array(
			'name' => 'submit',
            'attributes' => array(
                'value' => 'Open the event',
            ),
			'options' => array(
				'description' => 'Once the event is opened, you can generate invite links and allow players to join.'
			),
			'type' => 'submit',
		)));

		//$this->addElements(array($name, $code, $infoUrl, $artUrl, $file));
	}
}