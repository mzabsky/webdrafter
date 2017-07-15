<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;

class SetTable
{
	protected $tableGateway;
	
	public function __construct(TableGateway $tableGateway)
	{
		$this->tableGateway = $tableGateway;
	}
	
	public function fetchAll()
	{
		$resultSet = $this->tableGateway->select(array('is_private' => 0));
		return $resultSet;
	}
	
	public function fetchByUser($userId, $includePrivate = false)
	{
		if($includePrivate)
		{
			$resultSet = $this->tableGateway->select(array('user_id' => $userId));
		}
		else {
			$resultSet = $this->tableGateway->select(array('is_private' => 0, 'user_id' => $userId));
		}
		return $resultSet;
	}
	
	public function getSetsByUser($userId, $includePrivate)
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('set');
		//$select->forUpdate();
		$select->columns(array('set_name' => 'name', 'set_id', 'set_url_name' => 'url_name', 'set_status' => 'status'));
		$select->join('set_version', 'set_version.set_version_id = set.current_set_version_id', array(), 'left');
		$select->join(array('draft_set_version_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(DISTINCT draft_id) count, set_version_id FROM draft_set_version GROUP BY set_version_id)')), 'set_version.set_version_id = draft_set_version_count.set_version_id', array('draft_count' => 'count'), 'left');
		$select->join(array('card_set_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(card_id) count, set_version_id FROM card GROUP BY set_version_id)')), 'set.current_set_version_id = card_set_count.set_version_id', array('card_count' => 'count'), 'left');

		if($includePrivate)
		{
			$select->where(array('set.user_id' => $userId));
		}
		else 
		{
			$select->where(array('set.user_id' => $userId, 'set.is_private' => 0));
		}		
		//$select->order('draft.created_on DESC');
		$selectString = $sql->getSqlStringForSqlObject($select);
		//var_dump($selectString);
	
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
	
		$resultArray = array();
		foreach ($resultSet as $result)
		{
			$resultArray[] = array(
					'setId' => $result->set_id,
					'setName' => $result->set_name,
					'setStatus' => $result->set_status,
					'setUrlName' => $result->set_url_name,
					'draftCount' => $result->draft_count != null ? $result->draft_count : 0,
					'cardCount' => $result->card_count != null ? $result->card_count : 0
			);
		}
	
		return $resultArray;
	}
	
	public function getSets()
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('set');
		//$select->forUpdate();
		$select->columns(array('set_name' => 'name', 'set_id', 'url_name', 'created_on', 'is_featured', 'set_status' => 'status'));
		$select->join('set_version', 'set_version.set_version_id = set.current_set_version_id', array(), 'left');
		$select->join(array('draft_set_version_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(DISTINCT draft_id) count, set_version_id FROM draft_set_version GROUP BY set_version_id)')), 'set_version.set_version_id = draft_set_version_count.set_version_id', array('draft_count' => 'count'), 'left');
		$select->join(array('card_set_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(card_id) count, set_version_id FROM card GROUP BY set_version_id)')), 'set_version.set_version_id = card_set_count.set_version_id', array('card_count' => 'count'), 'left');
		$select->join('user', 'set.user_id = user.user_id', array('user_name' => 'name'));
		$select->where(array('set.is_private' => 0));
		$select->where->notIn('set.status', array(Set::STATUS_DISCONTINUED));
		//$select->order('draft.created_on DESC');
		$selectString = $sql->getSqlStringForSqlObject($select);
		//var_dump($selectString);
	
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
	
		$resultArray = array();
		foreach ($resultSet as $result)
		{
			$resultArray[] = array(
					'setId' => $result->set_id,
					'setName' => $result->set_name,
					'setStatus' => $result->set_status,
					'urlName' => $result->url_name,
					'draftCount' => $result->draft_count != null ? $result->draft_count : 0,
					'cardCount' => $result->card_count != null ? $result->card_count : 0,
					'userName' => $result->user_name,
					'createdOn' => $result->created_on,
					'isFeatured' => $result->is_featured
			);
		}
	
		return $resultArray;
	}
	
	public function getSetsToHost($userId)
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('set');
		//$select->forUpdate();
		$select->columns(array('set_name' => 'name', 'set_id', 'url_name', 'created_on', 'is_featured', 'set_status' => 'status', 'current_set_version_id', 'set_code' => 'code'));
		$select->join('set_version', 'set_version.set_version_id = set.current_set_version_id', array('current_set_version_name' => 'name'), 'left');
		$select->join(array('draft_set_version_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(DISTINCT draft_id) count, set_version_id FROM draft_set_version GROUP BY set_version_id)')), 'set_version.set_version_id = draft_set_version_count.set_version_id', array('draft_count' => 'count'), 'left');
		$select->join(array('card_set_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(card_id) count, set_version_id FROM card GROUP BY set_version_id)')), 'set_version.set_version_id = card_set_count.set_version_id', array('card_count' => 'count'), 'left');
		$select->join('user', 'set.user_id = user.user_id', array('user_name' => 'name'));
		$select->where(array(
				'set.status NOT IN(' . Set::STATUS_UNPLAYABLE . ', ' . Set::STATUS_DISCONTINUED . ')', 
				'(set.is_private = 0 OR set.user_id = ' . (int)$userId . ')',
				'current_set_version_id IS NOT NULL'
		));
		//$select->where(array();
		//$select->order('draft.created_on DESC');
		$selectString = $sql->getSqlStringForSqlObject($select);
		//var_dump($selectString);
	
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
	
		$resultArray = array();
		foreach ($resultSet as $result)
		{
			$resultArray[] = array(
					'setId' => $result->set_id,
					'setName' => $result->set_name,
					'setCode' => $result->set_code,
					'setStatus' => $result->set_status,
					'urlName' => $result->url_name,
					'draftCount' => $result->draft_count != null ? $result->draft_count : 0,
					'cardCount' => $result->card_count != null ? $result->card_count : 0,
					'userName' => $result->user_name,
					'createdOn' => $result->created_on,
					'isFeatured' => $result->is_featured,
					'currentSetVersionId' => $result->current_set_version_id,
					'currentSetVersionName' => $result->current_set_version_name,
			);
		}
	
		return $resultArray;
	}
	
	public function getSet($id)
	{
		$id  = (int) $id;
		$rowset = $this->tableGateway->select(array('set_id' => $id));
		$row = $rowset->current();
		if (!$row) {
			return null;
		}
		return $row;
	}
	
	public function getSetByUrlName($urlName)
	{
		$rowset = $this->tableGateway->select(array('url_name' => $urlName));
		$row = $rowset->current();
		if (!$row) {
			return null;
		}
		return $row;
	}
	
	public function getSetForBot($urlName)
	{
		$where = new \Zend\Db\Sql\Where;
		$where->equalTo( 'url_name', $urlName );
		$where->OR->equalTo( 'code', $urlName );
		$where->OR->equalTo( 'name', $urlName );
		
		$rowset = $this->tableGateway->select($where);
		$row = $rowset->current();
		if (!$row) {
			return null;
		}
		return $row;
	}
	
	public function saveSet(Set $set)
	{
		$data = array(
			'set_id' => $set->setId,
			'name'  => $set->name,
			'url_name'  => $set->urlName,
			'code'  => $set->code,
			'user_id'  => $set->userId,
			'about'  => $set->about,
			'status'  => $set->status,
			'is_private'  => $set->isPrivate,
			'current_set_version_id'  => $set->currentSetVersionId,
			'is_featured'  => $set->isFeatured,
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