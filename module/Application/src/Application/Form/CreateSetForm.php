<?php

namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element\Text;
use Zend\Form\Element\File;

class CreateSetForm extends Form
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
				'description' => 'Representative name of the set. Must be unique.'
			)
		)));
		
		$this->add($factory->createElement(array(
			'name' => 'code',
			'options' => array(
				'label' => 'Code: ',
				'description' => '3-5 uppercase letter code. Doesn\'t have to be unique.'
			)
		)));
		
		$this->add($factory->createElement(array(
			'name' => 'about',
			'type' => 'textarea',
			'options' => array(
				'label' => 'About the set: ',
				'description' => 'Arbitrary text displayed on the set page. Formatted with <a href="https://help.github.com/articles/markdown-basics/" target="blank">Markdown</a>. Up to 5000 characters.'
			),
		)));
		
		/*
		$this->add($factory->createElement(array(
			'name' => 'url',
			'type' => 'url',
			'options' => array(
				'label' => 'Information URL:',
				'description' => 'Link to a webpage containing additional information about the set (such as forum thread).'
			),
		)));
		
		$this->add($factory->createElement(array(
			'name' => 'art_url',
			'type' => 'url',
			'required' => true,
			'allow_empty' => false,
			'options' => array(
				'label' => 'Art source URL:',
				'description' => 'Base URL of a location where the art for individual cards is hosted, without the tailing slash. The final image URL will be composed as <base URL>/<file name> (as chosen below).'
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
		)));*/
		
		/*$this->add($factory->createElement(array(
				'name' => 'art_url_format',
				'type' => 'Zend\Form\Element\Select',
				'required' => true,
				'options' => array(
						'label' => 'Art file name format:',
						'description' => '',//URL from which the players can download the files necessary to play with the set (such as Cockatrice package).'
						'value_options' => [
								CreateSetForm::NAME_DOT_PNG => '<card name>.png',
								CreateSetForm::NAME_DOT_FULL_DOT_PNG => '<card name>.full.png',
								CreateSetForm::NAME_DOT_JPG => '<card name>.jpg',
								CreateSetForm::NAME_DOT_FULL_DOT_JPG => '<card name>.full.jpg',
						],
				),
		)));*/
		
		/*$this->add($factory->createElement(array(
				'name' => 'download_url',
				'type' => 'url',
				'options' => array(
						'label' => 'Download URL:',
						'description' => 'URL from which the players can download the files necessary to play with the set (such as Cockatrice package).'
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

		$this->add($factory->createElement(array(
				'name' => 'google_file_id',
				'type' => 'hidden',
		)));*/

		
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
                'value' => 'Save',
            ),
			'type' => 'submit',
			'required' => true,
		)));

		//$this->addElements(array($name, $code, $infoUrl, $artUrl, $file));
	}
}