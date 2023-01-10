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
	const AUTO = -1;
	const NAME_DOT_PNG_ALNUM = 1;
	const NAME_DOT_FULL_DOT_PNG_ALNUM = 2;
	const NAME_DOT_JPG_ALNUM = 3;
	const NAME_DOT_FULL_DOT_JPG_ALNUM = 4;
	const NAME_DOT_PNG_SPECIAL = 5;
	const NAME_DOT_FULL_DOT_PNG_SPECIAL = 6;
	const NAME_DOT_JPG_SPECIAL = 7;
	const NAME_DOT_FULL_DOT_JPG_SPECIAL = 8;
	
	private $inputFilter;
	
	public function __construct($options = null)
	{
		parent::__construct($options);
		//$this->setMethod("post");
		
		$this->setName('create_set');
		$this->setAttribute('enctype', 'multipart/form-data');

		$factory = new \Zend\Form\Factory();	
		
		
		
		
		/*$this->add($factory->createElement(array(
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
		)));*/
		
		$this->add($factory->createElement(array(
				'name' => 'art_url_format',
				'type' => 'Zend\Form\Element\Select',
				'required' => true,
				'options' => array(
						'label' => 'Art file name format:',
						'description' => 'Scheme used for names of individual card image files. Use \'Automatic\' unless you encounter issues with the website not assigning card images to cards correctly. Special characters are any non-alphanumeric non-space characters.',
						'value_options' => [
								self::AUTO => 'Automatic',
								/*elf::NAME_DOT_PNG_ALNUM => '<card name>.png (without special characters)',
								self::NAME_DOT_FULL_DOT_PNG_ALNUM => '<card name>.full.png (without special characters)',*/
								self::NAME_DOT_JPG_ALNUM => '<card name>.jpg (without special characters)',
								self::NAME_DOT_FULL_DOT_JPG_ALNUM => '<card name>.full.jpg (without special characters)',
								/*self::NAME_DOT_PNG_SPECIAL => '<card name>.png (with special characters)',
								self::NAME_DOT_FULL_DOT_PNG_SPECIAL => '<card name>.full.png (with special characters)',*/
								self::NAME_DOT_JPG_SPECIAL => '<card name>.jpg (with special characters)',
								self::NAME_DOT_FULL_DOT_JPG_SPECIAL => '<card name>.full.jpg (with special characters)',
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
				'description' => 'Text file containing the cards. Exported using <a href="/download/magic-planesculptors.mse-export-template.zip">PlaneSculptors MSE2 set exporter</a> .'
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
			'name' => 'submit_upload',
            'attributes' => array(
                'value' => 'Proceed',
            ),
			'type' => 'submit',
			'required' => true,
		)));
		
		/*$this->add($factory->createElement(array(
				'name' => 'file_list',
				'type' => 'hidden',
		)));*/

		$this->add($factory->createElement(array(
				'name' => 'upload_id',
				'type' => 'hidden',
		)));

		//$this->addElements(array($name, $code, $infoUrl, $artUrl, $file));
	}
	
// 	public function getInputFilter()
// 	{
// 		if (!$this->inputFilter) {
// 			$inputFilter = new InputFilter();
	
// 			/*$inputFilter->add(array(
// 				'name'     => 'art_url',
// 				'required' => true,
// 				'filters'  => array(
// 					array('name' => 'StringTrim'),
// 				),
// 				'validators' => array(
// 					array(
// 						'name'    => 'StringLength',
// 						'options' => array(
// 							'encoding' => 'UTF-8',
// 							'min'      => 1,
// 							'max'      => 255,
// 						),
// 					),
// 					array(
// 						'name'    => 'Uri',
// 					),
// 				),
// 			));*/
	
// 			$inputFilter->add(array(
// 				'name'     => 'file',
// 				'required' => true,
// 				'filters'  => array(
// 					//array('name' => 'StringTrim'),
// 				),
// 				'validators' => array(
// 					array(
// 						'name'    => 'File\Size',
// 						'options' => array(
// 							'max' => '1MB'
// 						),
// 					),
// 					array(
// 						'name'    => 'File\UploadFile',
// 						'options' => array(),
// 					),
// 				),
// 			));
	
// 			$this->inputFilter = $inputFilter;
// 		}
	
// 		return $this->inputFilter;
// 	}
}