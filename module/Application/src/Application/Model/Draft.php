<?php

namespace Application\Model;

class Draft
{
	const STATUS_OPEN = 1;
	const STATUS_RUNNING = 2;
	const STATUS_FINISHED = 3;
	
	public $draftId;
	public $status;
	public $createdOn;
	public $hostId;
	public $name;
	public $packNumber;
	public $pickNumber;
	public $lobbyKey;

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
    }
}