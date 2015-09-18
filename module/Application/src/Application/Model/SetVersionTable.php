<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;

class SetVersionTable
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
	
	public function fetchBySet($setVersionId)
	{
		$resultSet = $this->tableGateway->select(array('set_version_id' => $setVersionId));
		return $resultSet;
	}
	
	public function getSetsVersionsBySet($userId)
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('set');
		//$select->forUpdate();
		$select->columns(array('set_name' => 'name', 'set_id'));
		//$select->join('user', 'user.user_id = set.user_id', array('user_name' => 'name', 'user_id'));
		$select->join(array('draft_set_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(DISTINCT draft_id) count, set_id FROM draft_set GROUP BY set_id)')), 'set.set_id = draft_set_count.set_id', array('draft_count' => 'count'));
		$select->join(array('card_set_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(card_id) count, set_id FROM card GROUP BY set_id)')), 'set.set_id = card_set_count.set_id', array('card_count' => 'count'));		
		$select->where(array('set.user_id' => $userId, 'set.is_retired' => 0));
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
					'draftCount' => $result->draft_count,
					'cardCount' => $result->card_count
			);
		}
	
		return $resultArray;
	}
	
	public function getSetVersion($id)
	{
		$id  = (int) $id;
		$rowset = $this->tableGateway->select(array('set_version_id' => $id));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find set version $id");
		}
		return $row;
	}
	
	public function saveSetVersion(SetVersion $setVersion)
	{
		$data = array(
			'set_version_id' => $setVersion->setVersionId,
			'name'  => $setVersion->name,
			'download_url'  => $setVersion->downloadUrl,
			'about'  => $setVersion->about,
			'set_id'  => $setVersion->setId,
			//'created_on'  => $setVersion->createdOn,
		);
	
		$id = (int) $setVersion->setVersionId;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$setVersion->setVersionId = $this->tableGateway->lastInsertValue;
		} else {
			if ($this->getSetVersion($id)) {
				$this->tableGateway->update($data, array('set_version_id' => $id));
			} else {
				throw new \Exception('Set version id does not exist');
			}
		}
	}
	
	/*public function delete($id)
	{
		$this->tableGateway->delete(array('id' => (int) $id));
	}*/
}