<?php

namespace Application\Model;

class Draft
{
	const STATUS_OPEN = 1;
	const STATUS_RUNNING = 2;
	const STATUS_FINISHED = 3;
	

	const MODE_BOOSTER_DRAFT = 1;
	const MODE_CUBE_DRAFT = 2;
	const MODE_CHAOS_DRAFT = 3;
	const MODE_SEALED_DECK = 4;
	const MODE_CUBE_SEALED = 5;
	
	const RARITY_MODE_MRUC = 1;
	const RARITY_MODE_RUC = 2;
	const RARITY_MODE_UC = 3;
	const RARITY_MODE_C = 4;
	
	public $draftId;
	public $status;
	public $createdOn;
	public $hostId;
	public $name;
	public $packNumber;
	public $pickNumber;
	public $lobbyKey;
	public $gameMode;
	public $rarityMode;
	public $tournamentUrl;

    public function exchangeArray($data)
    {
        $this->draftId     = (!empty($data['draft_id'])) ? $data['draft_id'] : null;
        $this->status     = (!empty($data['status'])) ? $data['status'] : null;
        $this->createdOn = (!empty($data['created_on'])) ? $data['created_on'] : null;
        $this->hostId = (!empty($data['host_id'])) ? $data['host_id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->packNumber = $data['pack_number'];
        $this->pickNumber = $data['pick_number'];
        $this->lobbyKey = $data['lobby_key'];
        $this->gameMode = $data['game_mode'];
        $this->rarityMode = $data['rarity_mode'];
        $this->tournamentUrl = $data['tournament_url'];
    }
    
    public static function getGameModeName($gameMode)
    {
    	switch($gameMode)
    	{
    		case Draft::MODE_BOOSTER_DRAFT: return "Booster draft";
    		case Draft::MODE_CUBE_DRAFT: return "Cube draft";
    		case Draft::MODE_CHAOS_DRAFT: return "Chaos draft";
    		case Draft::MODE_SEALED_DECK: return "Sealed deck";
    		case Draft::MODE_CUBE_SEALED: return "Cube sealed deck";
    	}
    }
}