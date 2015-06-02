<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;

class SetTable
{
	protected $tableGateway;
	
	public function __construct(TableGateway $tableGateway)
	{
		$this->tableGateway = $tableGateway;
	}
	
	public function fetchAll()
	{
		$resultSet = $this->tableGateway->select(array('is_retired' => 0));
		return $resultSet;
	}
	
	public function fetchByUser($userId)
	{
		$resultSet = $this->tableGateway->select(array('is_retired' => 0, 'user_id' => $userId));
		return $resultSet;
	}
	
	public function getSet($id)
	{
		$id  = (int) $id;
		$rowset = $this->tableGateway->select(array('set_id' => $id));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find set $id");
		}
		return $row;
	}
	
	public function saveSet(Set $set)
	{
		$data = array(
			'set_id' => $set->setId,
			'name'  => $set->name,
			'code'  => $set->code,
			'url'  => $set->url,
			'user_id'  => $set->userId,
			'is_retired'  => $set->isRetired,
			'download_url'  => $set->downloadUrl,
		);
	
		$id = (int) $set->setId;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$set->setId = $this->tableGateway->lastInsertValue;
		} else {
			if ($this->getSet($id)) {
				$this->tableGateway->update($data, array('set_id' => $id));
			} else {
				throw new \Exception('Set id does not exist');
			}
		}
	}
	
	/*public function delete($id)
	{
		$this->tableGateway->delete(array('id' => (int) $id));
	}*/
}