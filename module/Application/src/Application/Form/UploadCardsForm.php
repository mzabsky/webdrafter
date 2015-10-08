<?php

namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element\Text;
use Zend\Form\Element\File;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class UploadCardsForm extends Form
{
	const NAME_DOT_PNG = 1;
	const NAME_DOT_FULL_DOT_PNG = 2;
	const NAME_DOT_JPG = 3;
	const NAME_DOT_FULL_DOT_JPG = 4;
	
	private $inputFilter;
	
	public function __construct($options = null)
	{
		parent::__construct($options);
		//$this->setMethod("post");
		
		$this->setName('create_set');
		$this->setAttribute('enctype', 'multipart/form-data');

		$factory = new \Zend\Form\Factory();	
		
		
		
		
		$this->add($factory->createElement(array(
			'name' => 'art_url',
			'type' => 'url',
			'required' => true,
			'allow_empty' => false,
			'options' => array(
				'label' => 'Art source URL:',
				'description' => 'Base URL of a location where the art for individual cards is hosted, without the tailing slash. The final image URL will be composed as &lt;base URL&gt;/&lt;file name&gt; (as chosen below).'
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
						'label' => 'Art file name format:',
						'description' => 'Scheme used for names of individual card image files.',//URL from which the players can download the files necessary to play with the set (such as Cockatrice package).'
						'value_options' => [
								self::NAME_DOT_PNG => '<card name>.png',
								self::NAME_DOT_FULL_DOT_PNG => '<card name>.full.png',
								self::NAME_DOT_JPG => '<card name>.jpg',
								self::NAME_DOT_FULL_DOT_JPG => '<card name>.full.jpg',
						],
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
			'name' => 'upload',
            'attributes' => array(
                'value' => 'Upload',
            ),
			'type' => 'submit',
			'required' => true,
		)));

		//$this->addElements(array($name, $code, $infoUrl, $artUrl, $file));
	}
	
	public function getInputFilter()
	{
		if (!$this->inputFilter) {
			$inputFilter = new InputFilter();
	
			$inputFilter->add(array(
				'name'     => 'art_url',
				'required' => true,
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
				'name'     => 'file',
				'required' => true,
				'filters'  => array(
					//array('name' => 'StringTrim'),
				),
				'validators' => array(
					array(
						'name'    => 'File\Size',
						'options' => array(
							'max' => '1MB'
						),
					),
					array(
						'name'    => 'File\UploadFile',
						'options' => array(),
					),
				),
			));
	
			$this->inputFilter = $inputFilter;
		}
	
		return $this->inputFilter;
	}
}