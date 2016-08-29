<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;


class DraftPlayerTable
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
	
	public function fetchByDraft($draftId)
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('draft_player');
		//$select->forUpdate();
		//$select->columns(array('version_name' => 'name', 'url_name','set_version_id', 'created_on', 'about'));
		$select->join(array('user' => 'user'), 'draft_player.user_id = user.user_id', array('url_name' => 'url_name'), 'left');
		//$select->join(array('draft_set_version_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(DISTINCT draft_id) count, set_version_id FROM draft_set_version GROUP BY set_version_id)')), 'set_version.set_version_id = draft_set_version_count.set_version_id', array('draft_count' => 'count'), 'left');
		//$select->join(array('card_set_version_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(card_id) count, set_version_id FROM card GROUP BY set_version_id)')), 'set_version.set_version_id = card_set_version_count.set_version_id', array('card_count' => 'count'), 'left');
		$select->where(array('draft_player.draft_id' => $draftId ));
		//$select->order('set_version.created_on DESC');
		$selectString = $sql->getSqlStringForSqlObject($select);
		//var_dump($selectString);
		
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
		
		$resultArray = array();
		foreach ($resultSet as $result)
		{
			$obj = new DraftPlayer();
			$obj->draftPlayerId = $result->draft_player_id;
			$obj->hasJoined = $result->has_joined;
			$obj->name = $result->name;
			$obj->userId = $result->user_id;
			$obj->draftId = $result->draft_id;
			$obj->inviteKey = $result->invite_key;
			$obj->playerNumber = $result->player_number;
			$obj->playerNumber = $result->player_number;
			$obj->urlName = $result->url_name;
				
			$resultArray[] = $obj;
		}
		
		return $resultArray;
		
		/*$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftId){
			$select->where(array('draft_id' => $draftId));
			$select->order("draft_player_id ASC");
		});
		return $resultSet;*/
	}
	
	public function fetchJoinedByDraft($draftId)
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('draft_player');
		//$select->forUpdate();
		//$select->columns(array('version_name' => 'name', 'url_name','set_version_id', 'created_on', 'about'));
		$select->join(array('user' => 'user'), 'draft_player.user_id = user.user_id', array('url_name' => 'url_name'), 'left');
		//$select->join(array('draft_set_version_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(DISTINCT draft_id) count, set_version_id FROM draft_set_version GROUP BY set_version_id)')), 'set_version.set_version_id = draft_set_version_count.set_version_id', array('draft_count' => 'count'), 'left');
		//$select->join(array('card_set_version_count' => new \Zend\Db\Sql\Expression('(SELECT COUNT(card_id) count, set_version_id FROM card GROUP BY set_version_id)')), 'set_version.set_version_id = card_set_version_count.set_version_id', array('card_count' => 'count'), 'left');
		$select->where(array('draft_player.draft_id' => $draftId, 'has_joined' => 1 ));
		//$select->order('set_version.created_on DESC');
		$selectString = $sql->getSqlStringForSqlObject($select);
		//var_dump($selectString);
		
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
		
		$resultArray = array();
		foreach ($resultSet as $result)
		{
			$obj = new \Application\Model\DraftPlayer();
			$obj->draftPlayerId = $result->draft_player_id;
			$obj->hasJoined = $result->has_joined;
			$obj->name = $result->name;
			$obj->userId = $result->user_id;
			$obj->draftId = $result->draft_id;
			$obj->inviteKey = $result->invite_key;
			$obj->playerNumber = $result->player_number;
			$obj->playerNumber = $result->player_number;
			$obj->urlName = $result->url_name;
			
			$resultArray[] = $obj;
		}
		
		return $resultArray;
		
		
		/*$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftId){
			$select->join(array('user'), 'draft_player.user_id = user.user_id', array('user_url_name' => 'url_name'));
			$select->where(array('draft_id' => $draftId, 'has_joined' => 1));
			$select->order("draft_player_id ASC");
		});
		return $resultSet;*/
	}
	
	public function getDraftPlayer($id)
	{
		$id  = (int) $id;
		$rowset = $this->tableGateway->select(array('draft_player_id' => $id));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find draft player $id");
		}
		return $row;
	}
	
	public function checkPlayerNameOpenInDraft($playerName, $draftId)
	{
		$draftId  = (int) $draftId;
		$rowset = $this->tableGateway->select(array('draft_id' => $draftId, 'name' => $playerName));
		$row = $rowset->current();
		if (!$row) {
			return true;
		}
		return false;
	}
	
	public function getDraftPlayerByInviteKey($inviteKey)
	{
		$rowset = $this->tableGateway->select(array('invite_key' => $inviteKey));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find draft player with invite key $inviteKey");
		}
		return $row;
	}
	
	public function getDraftPlayerByUserId($draftId, $userId)
	{
		$rowset = $this->tableGateway->select(array('draft_id' => $draftId, 'user_id' => $userId));
		$row = $rowset->current();
		if (!$row) {
			return null;
		}
		return $row;
	}
	
	public function deleteDraftPlayerByUserId($draftId, $userId)
	{
		$this->tableGateway->delete(array('draft_id' => $draftId, 'user_id' => $userId));
	}
	
	public function saveDraftPlayer(DraftPlayer $draftPlayer)
	{
		$data = array(
			'draft_player_id' => $draftPlayer->draftPlayerId,
			'has_joined' => $draftPlayer->hasJoined,
			'name'  => $draftPlayer->name,
			'draft_id'  => $draftPlayer->draftId,
			'user_id'  => $draftPlayer->userId,
			'name'  => $draftPlayer->name,
			'invite_key'  => $draftPlayer->inviteKey,
			'player_number'  => $draftPlayer->playerNumber
		);
	
		$id = (int) $draftPlayer->draftPlayerId;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$draftPlayer->draftPlayerId = $this->tableGateway->lastInsertValue;
		} else {
			if ($this->getDraftPlayer($id)) {
				$this->tableGateway->update($data, array('draft_player_id' => $id));
			} else {
				throw new \Exception('Draft player id does not exist');
			}
		}
	}
}