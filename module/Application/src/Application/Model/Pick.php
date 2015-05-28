<?php

namespace Application\Model;

class Pick
{
	const ZONE_MAINDECK = 0;
	const ZONE_SIDEBOARD = 1;
	const ZONE_HIDDEN = 2;
	
	public $pickId;
	public $cardId;
	public $startingPlayerId;
	public $currentPlayerId;
	public $isPicked;
	public $packNumber;
	public $pickNumber;
	public $zone;
	public $zoneColumn;

    public function exchangeArray($data)
    {
        $this->pickId     = (!empty($data['pick_id'])) ? $data['pick_id'] : null;
        $this->cardId     = (!empty($data['card_id'])) ? $data['card_id'] : null;
        $this->startingPlayerId = (!empty($data['starting_player_id'])) ? $data['starting_player_id'] : null;
        $this->currentPlayerId = (!empty($data['current_player_id'])) ? $data['current_player_id'] : null;
        $this->isPicked = (int)$data['is_picked'];
        $this->packNumber = (!empty($data['pack_number'])) ? $data['pack_number'] : null;
        $this->pickNumber = (!empty($data['pick_number'])) ? $data['pick_number'] : null;
        $this->zone = (int)$data['zone'];
        $this->zoneColumn = (int)$data['zone_column'];
    }
}