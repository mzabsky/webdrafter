<?php

namespace Application\Model;

class Collation
{
	public $collationId;
	public $setVersionId;
	public $packNumber;
	public $cardId;
	
    public function exchangeArray($data)
    {
        $this->collationId = (!empty($data['collation_id'])) ? $data['collation_id'] : null;
        $this->setVersionId = (!empty($data['set_version_id'])) ? $data['set_version_id'] : null;
        $this->packNumber = (!empty($data['pack_number'])) ? $data['pack_number'] : null;
        $this->cardId = $data['card_id'];
    }

    public function __toString ()
    {
    	return $this->name;
    }
}
