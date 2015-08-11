<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;

class UserTable
{
	protected $tableGateway;
	
	public function __construct(TableGateway $tableGateway)
	{
		$this->tableGateway = $tableGateway;
	}
	
	public function fetchAll()
	{
		$resultSet = $this->tableGateway->select();
		return $resultSet;
	}
	
	public function getUser($id)
	{
		$id  = (int) $id;
		$rowset = $this->tableGateway->select(array('user_id' => $id));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find user $id");
		}
		return $row;
	}
	
	public function tryGetUserByEmail($email)
	{

		$rowset = $this->tableGateway->select(array('email' => $email));
		$row = $rowset->current();
		if (!$row) {
			return null;
		}
		return $row;
	}
	
	public function saveUser(User $user)
	{
		$data = array(
			'user_id' => $user->userId,
			'email'  => $user->email,
			'name'  => $user->name,
			'email_privacy'  => $user->emailPrivacy,
			'email'  => $user->email,
		);
	
		$id = (int) $user->userId;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$user->userId = $this->tableGateway->lastInsertValue;
		} else {
			if ($this->getUser($id)) {
				$this->tableGateway->update($data, array('user_id' => $id));
			} else {
				throw new \Exception('User id does not exist');
			}
		}
	}
}