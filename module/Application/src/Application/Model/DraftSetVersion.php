<?php

namespace Application\Model;

class DraftSetVersion
{
	public $draftSetVersionId;
	public $draftId;
	public $setVersionId;
	public $packNumber;

    public function exchangeArray($data)
    {
        $this->draftSetVersionId     = (!empty($data['draft_set_version_id'])) ? $data['draft_set_version_id'] : null;
        $this->draftId     = (!empty($data['draft_id'])) ? $data['draft_id'] : null;
        $this->setVersionId = (!empty($data['set_version_id'])) ? $data['set_version_id'] : null;
        $this->packNumber = (!empty($data['pack_number'])) ? $data['pack_number'] : null;
    }
}