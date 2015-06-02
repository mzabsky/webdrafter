<?php

namespace Application\Model;

class Set
{
	public $setId;
	public $name;
	public $code;
	public $url;
	public $userId;
	public $isRetired;
	public $downloadUrl;

    public function exchangeArray($data)
    {
        $this->setId     = (!empty($data['set_id'])) ? $data['set_id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->code = (!empty($data['code'])) ? $data['code'] : null;
        $this->url = (!empty($data['url'])) ? $data['url'] : null;
        $this->userId = (!empty($data['user_id'])) ? $data['user_id'] : null;
        $this->isRetired = $data['is_retired'];
        $this->downloadUrl = (!empty($data['download_url'])) ? $data['download_url'] : null;
    }
}