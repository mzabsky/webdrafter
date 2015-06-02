<?php

namespace Application\Model;

class Card
{
	public $cardId;
	public $setId;
	public $name;
	public $colors;
	public $types;
	public $cmc;
	public $rarity;
	public $artUrl;

    public function exchangeArray($data)
    {
        $this->cardId     = (!empty($data['card_id'])) ? $data['card_id'] : null;
        $this->setId     = (!empty($data['set_id'])) ? $data['set_id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->colors = (!empty($data['colors'])) ? $data['colors'] : null;
        $this->rarity = (!empty($data['rarity'])) ? $data['rarity'] : null;
        $this->artUrl = (!empty($data['art_url'])) ? $data['art_url'] : null;
        $this->types = (!empty($data['types'])) ? $data['types'] : null;
        $this->cmc = $data['cmc'];
    }
    
    public function __toString ()
    {
    	return $this->name;
    }
}