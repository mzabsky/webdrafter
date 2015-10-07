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
	
	public function fetchBySet($setId)
	{
		$resultSet = $this->tableGateway->select(array('set_id' => $setId));
		return $resultSet;
	}
	
	public function getSetVersionsBySet($setId)
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('set_version');
		//$select->forUpdate();
		$select->columns(array('version_name' => 'name', 'url_name','set_version_id', 'created_on', 'about'));
		//$select->join('user', 'user.user_id = set.user_id', array('user_name' => 'name', 'user_id'));
		$select->join(array('draft_set_version_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(DISTINCT draft_id) count, set_version_id FROM draft_set_version GROUP BY set_version_id)')), 'set_version.set_version_id = draft_set_version_count.set_version_id', array('draft_count' => 'count'), 'left');
		$select->join(array('card_set_version_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(card_id) count, set_version_id FROM card GROUP BY set_version_id)')), 'set_version.set_version_id = card_set_version_count.set_version_id', array('card_count' => 'count'), 'left');		
		$select->where(array('set_version.set_id' => $setId ));
		$select->order('set_version.created_on DESC');
		$selectString = $sql->getSqlStringForSqlObject($select);
		//var_dump($selectString);
	
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
	
		$resultArray = array();
		foreach ($resultSet as $result)
		{
			$resultArray[] = array(
					'set_version_id' => $result->set_version_id,
					'name' => $result->version_name,
					'about' => $result->about,
					'createdOn' => $result->created_on,
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
			'url_name'  => $setVersion->urlName,
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