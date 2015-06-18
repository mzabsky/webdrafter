<?php

namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element\Text;
use Zend\Form\Element\File;

class CreateSetForm extends Form
{
	const NAME_DOT_PNG = 1;
	const NAME_DOT_FULL_DOT_PNG = 2;
	const NAME_DOT_JPG = 3;
	const NAME_DOT_FULL_DOT_JPG = 4;
	
	public function __construct($options = null)
	{
		parent::__construct($options);
		//$this->setMethod("post");
		
		$this->setName('create_set');

		$factory = new \Zend\Form\Factory();	
		
		$this->add($factory->createElement(array(
			'name' => 'name',
			'required' => true,
			'allow_empty' => false,
			'options' => array(
				'label' => 'Name',
				'description' => 'Representative name of the set. Must be unique (consider including version or date).'
			),
			'validators' => array(
				array(
					'name' => 'StringLength',
						'options' => array(
						'encoding' => 'UTF-8',
						'min' => '4',
						'max' => '255',
					),
				),
			),
		)));
		
		$this->add($factory->createElement(array(
			'name' => 'code',
			'required' => true,
			'allow_empty' => false,
			'options' => array(
				'label' => 'Code:',
				'description' => '3-5 uppercase letter code. Doesn\'t have to be unique.'
			),
			'validators' => array(
				array(
					'name' => 'StringLength',
					'options' => array(
						'encoding' => 'UTF-8',
						'min' => '3',
						'max' => '5',
					),
				),
			),
		)));
		
		$this->add($factory->createElement(array(
			'name' => 'info_url',
			'required' => true,
			'allow_empty' => false,
			'options' => array(
				'label' => 'Information URL:',
				'description' => 'Link to a webpage containing additional information about the set (such as forum thread).'
			),
			'validators' => array(
				array(
					'name' => 'StringLength',
					'options' => array(
						'encoding' => 'UTF-8',
						'min' => '3',
						'max' => '255',
					),
				),
			),
		)));
		
		$this->add($factory->createElement(array(
			'name' => 'art_url',
			'required' => true,
			'allow_empty' => false,
			'options' => array(
				'label' => 'Art source URL:',
				'description' => 'Base URL of a location where the art for individual cards is hosted, without the tailing slash. The final image URL will be composed using the format below.'
			),
			'validators' => array(
				array(
					'name' => 'StringLength',
					'options' => array(
					'encoding' => 'UTF-8',
						'min' => '3',
						'max' => '255',
					),
				),
			),
		)));
		
		$this->add($factory->createElement(array(
				'name' => 'art_url_format',
				'type' => 'Zend\Form\Element\Select',
				'required' => true,
				'options' => array(
						'label' => 'Art URL format:',
						'description' => '',//URL from which the players can download the files necessary to play with the set (such as Cockatrice package).'
						'value_options' => [
								CreateSetForm::NAME_DOT_PNG => '<base URL>/<card name>.png',
								CreateSetForm::NAME_DOT_FULL_DOT_PNG => '<base URL>/<card name>.full.png',
								CreateSetForm::NAME_DOT_JPG => '<base URL>/<card name>.jpg',
								CreateSetForm::NAME_DOT_FULL_DOT_JPG => '<base URL>/<card name>.full.jpg',
						],
				),
		)));
		
		$this->add($factory->createElement(array(
				'name' => 'download_url',
				'required' => false,
				'allow_empty' => true,
				'options' => array(
						'label' => 'Download URL:',
						'description' => 'URL from which the players can download the files necessary to play with the set (such as Cockatrice package).'
				),
				'validators' => array(
						array(
								'name' => 'StringLength',
								'options' => array(
										'encoding' => 'UTF-8',
										'min' => '3',
										'max' => '255',
								),
						),
				),
		)));
		
		$this->add($factory->createElement(array(
			'name' => 'file',
			'type' => 'file',
			'required' => true,
			'allow_empty' => false,
			'options' => array(
				'label' => 'Set file: ',
				'description' => 'Text file containing the cards. Exported using <a href="http://puu.sh/i8GMt/28e82db942.zip">WebDrafter</a> MSE2 set exporter.'
			),
			'filters' => array(
				array(
					'name' => 'filerenameupload',
					'options' => array(
               			'target' => './data/tmpuploads/',
                		'randomize' => true,
            		)
				),
			),
		)));
		
		/*$file = new File('file');
		$file->setLabel('Set file:')
			//->setDestination('/var/www/upload')
			->addValidator('Count', false, 1)
			->addValidator('Size', false, 102400)
			->addValidator('Extension', false, 'txt')
			->setAttrib('enctype', 'multipart/form-data');*/

		$this->add($factory->createElement(array(
			'name' => 'submit',
            'attributes' => array(
                'value' => 'Create',
            ),
			'type' => 'submit',
			'required' => true,
		)));

		//$this->addElements(array($name, $code, $infoUrl, $artUrl, $file));
	}
}