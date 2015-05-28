<?php

namespace Application\Model;

class User
{
	public $userId;
	public $email;
	
    public function exchangeArray($data)
    {
        $this->userId     = (!empty($data['user_id'])) ? $data['user_id'] : null;
        $this->email = (!empty($data['email'])) ? $data['email'] : null;
    }
}