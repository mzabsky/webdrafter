<?php

namespace Application\Model;

class Card
{

	const SHAPE_NORMAL = 1;
	const SHAPE_SPLIT = 2;
	const SHAPE_FLIP = 3;
	const SHAPE_DOUBLE = 4;
	
	public $cardId;
	public $setVersionId;
	public $shape;
	public $cardNumber;
	public $cmc;
	public $rarity;
	public $artUrl;
	public $urlName;
	
	public $name;	
	public $colors;
	public $manaCost;
	public $types;
	public $rulesText;
	public $flavorText;
	public $power;
	public $toughness;
	public $ptString;
	public $illustrator;

	public $name2;
	public $colors2;
	public $manaCost2;
	public $types2;
	public $rulesText2;
	public $flavorText2;
	public $power2;
	public $toughness2;
	public $ptString2;
	public $illustrator2;
	
    public function exchangeArray($data)
    {
        $this->cardId = (!empty($data['card_id'])) ? $data['card_id'] : null;
        $this->setVersionId = (!empty($data['set_version_id'])) ? $data['set_version_id'] : null;
        $this->shape = (!empty($data['shape'])) ? $data['shape'] : null;
        $this->cardNumber = $data['card_number'];
        $this->cmc = $data['cmc'];
        $this->rarity = (!empty($data['rarity'])) ? $data['rarity'] : null;
        $this->artUrl = (!empty($data['art_url'])) ? $data['art_url'] : null;
        $this->urlName = (!empty($data['url_name'])) ? $data['url_name'] : null;
        
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->colors = (!empty($data['colors'])) ? $data['colors'] : null;
        $this->manaCost = (!empty($data['mana_cost'])) ? $data['mana_cost'] : null;
        $this->types = (!empty($data['types'])) ? $data['types'] : null;
        $this->rulesText = (!empty($data['rules_text'])) ? $data['rules_text'] : null;
        $this->flavorText = (!empty($data['flavor_text'])) ? $data['flavor_text'] : null;
        $this->power = (!empty($data['power'])) ? $data['power'] : null;
        $this->toughness = (!empty($data['toughness'])) ? $data['toughness'] : null;
        $this->ptString = (!empty($data['pt_string'])) ? $data['pt_string'] : null;
        $this->illustrator = (!empty($data['illustrator'])) ? $data['illustrator'] : null;

        $this->name2 = (!empty($data['name_2'])) ? $data['name_2'] : null;
        $this->colors2 = (!empty($data['colors_2'])) ? $data['colors_2'] : null;
        $this->manaCost2 = (!empty($data['mana_cost_2'])) ? $data['mana_cost_2'] : null;
        $this->types2 = (!empty($data['types_2'])) ? $data['types_2'] : null;
        $this->rulesText2 = (!empty($data['rules_text_2'])) ? $data['rules_text_2'] : null;
        $this->flavorText2 = (!empty($data['flavor_text_2'])) ? $data['flavor_text_2'] : null;
        $this->power2 = (!empty($data['power_2'])) ? $data['power_2'] : null;
        $this->toughness2 = (!empty($data['toughness_2'])) ? $data['toughness_2'] : null;
        $this->ptString2 = (!empty($data['pt_string_2'])) ? $data['pt_string_2'] : null;
        $this->illustrator2 = (!empty($data['illustrator_2'])) ? $data['illustrator_2'] : null;
        
    }
    
    public function getShapeName()
    {
    	switch($this->shape)
    	{
    		case self::SHAPE_NORMAL: return "normal";
    		case self::SHAPE_SPLIT: return "split";
    		case self::SHAPE_FLIP: return "flip";
    		case self::SHAPE_DOUBLE: return "double";
    	}
    }
    
    public function getRarityName()
    {
    	switch($this->rarity)
    	{
    		case "C": return "Common";
    		case "U": return "Uncommon";
    		case "R": return "Rare";
    		case "M": return "Mythic";
    		case "S": return "Special";
    		case "T": return "Token";
    		case "L": return "Basic Land";
    	}
    }
    
    public function __toString ()
    {
    	return $this->name;
    }
}