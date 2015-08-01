<?php

namespace Application\Model;

class DraftPlayerBasic
{
	public $draftPlayerBasicId;
	public $draftPlayerId;
	public $color;
	public $count;

    public function exchangeArray($data)
    {
    	$this->draftPlayerBasicId     = (!empty($data['draft_player_basic_id'])) ? $data['draft_player_basic_id'] : null;
        $this->draftPlayerId     = (!empty($data['draft_player_id'])) ? $data['draft_player_id'] : null;
        $this->color     = (!empty($data['color'])) ? $data['color'] : null;
        $this->count = $data['count'];
    }
}