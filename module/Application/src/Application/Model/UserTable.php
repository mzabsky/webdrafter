<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;

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
			return null;
		}
		return $row;
	}
	
	public function getUserByUrlName($urlName)
	{
		$rowset = $this->tableGateway->select(array('url_name' => $urlName));
		$row = $rowset->current();
		if (!$row) {
			return null;
		}
		return $row;
	}
	
	public function getUsers()
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('user');
		$select->columns(array('user_name' => 'name', 'user_url_name' => 'url_name', 'user_id'));
		$select->join(array('draft_player_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(DISTINCT draft_id) count, user_id FROM draft_player GROUP BY user_id)')), 'user.user_id = draft_player_count.user_id', array('draft_count' => 'count'), 'left');
		$select->join(array('set_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(set_id) count, user_id FROM `set` WHERE is_private = 0 GROUP BY user_id)')), 'user.user_id = set_count.user_id', array('set_count' => 'count'), 'left');
		$select->where(array('user.name IS NOT NULL'));
		$selectString = $sql->getSqlStringForSqlObject($select);
		
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
	
		$resultArray = array();
		foreach ($resultSet as $result)
		{
			$resultArray[] = array(
					'userId' => $result->user_id,
					'userName' => $result->user_name,
					'userUrlName' => $result->user_url_name,
					'draftCount' => $result->draft_count != null ? $result->draft_count : 0,
					'setCount' => $result->set_count != null ? $result->set_count : 0
			);
		}
	
		return $resultArray;
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
	
	public function tryGetUserByApiKey($apiKey)
	{
	
		$rowset = $this->tableGateway->select(array('api_key' => $apiKey));
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
			'url_name'  => $user->urlName,
			'email_privacy'  => $user->emailPrivacy,
			'about'  => $user->about,
			'challonge_api_key'  => $user->challongeApiKey,
			'refresh_token'  => $user->refreshToken,
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