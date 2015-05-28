<?php

namespace Application\Model;

class DraftSet
{
	public $draftSetId;
	public $draftId;
	public $setId;
	public $packNumber;

    public function exchangeArray($data)
    {
        $this->draftSetId     = (!empty($data['draft_set_id'])) ? $data['draft_set_id'] : null;
        $this->draftId     = (!empty($data['draft_id'])) ? $data['draft_id'] : null;
        $this->setId = (!empty($data['set_id'])) ? $data['set_id'] : null;
        $this->packNumber = (!empty($data['pack_number'])) ? $data['pack_number'] : null;
    }
}