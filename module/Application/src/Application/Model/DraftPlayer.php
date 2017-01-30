<?php

namespace Application\Model;

class DraftPlayer
{
	public $draftPlayerId;
	public $hasJoined;
	public $name;
	public $userId;
	public $draftId;
	public $inviteKey;
	public $playerNumber;
	public $isAi;
    public function exchangeArray($data)
    {
        $this->draftPlayerId     = (!empty($data['draft_player_id'])) ? $data['draft_player_id'] : null;
        $this->hasJoined = (int)$data['has_joined'];
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->userId = (!empty($data['user_id'])) ? $data['user_id'] : null;
        $this->draftId = (!empty($data['draft_id'])) ? $data['draft_id'] : null;
        $this->inviteKey = (!empty($data['invite_key'])) ? $data['invite_key'] : null;
        $this->playerNumber = (!empty($data['player_number'])) ? $data['player_number'] : null;
        $this->isAi = $data['is_ai'];
    }
}