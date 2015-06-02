<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;

class DraftTable
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
	
	public function fetchByHost($hostId)
	{
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($hostId){
			$select->where(array('host_id' => $hostId));
			$select->order('created_on DESC');
		});
		return $resultSet;
	}
	
	public function fetchByUser($userId)
	{
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($userId){
			$select->join('draft_player', 'draft_player.draft_id = draft.draft_id', array());
			$select->where(array('draft_player.user_id' => $userId));
			$select->order('created_on DESC');
		});
		return $resultSet;
	}
	
	public function getPastDraftsByUser($userId)
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('draft');
		//$select->forUpdate();
		$select->columns(array('draft_name' => 'name', 'draft_status' => 'status', 'pack_number', 'pick_number'));
		$select->join('draft_player', 'draft_player.draft_id = draft.draft_id', array('invite_key'));
		$select->where(array('draft_player.user_id' => $userId));
		$select->order('draft.created_on DESC');
		//var_dump($selectString);
		$selectString = $sql->getSqlStringForSqlObject($select);
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
	
		$resultArray = array();
		foreach ($resultSet as $result)
		{
			$resultArray[] = array(
				'inviteKey' => $result->invite_key, 
				'draftName' => $result->draft_name, 
				'draftStatus' => $result->draft_status, 
				'packNumber' => $result->pack_number, 
				'pickNumber' => $result->pick_number
			);
		}
	
		return $resultArray;
	}
	
	public function fetchPickIndicators($draftId)
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('draft_player', array());
		$select->columns(array(
			'draft_player_name' => new \Zend\Db\Sql\Expression('draft_player.name'),
			'has_picked' => new \Zend\Db\Sql\Expression('pick.is_picked')
		));
		$select->join('draft', 'draft.draft_id = draft_player.draft_id');
		$select->join('pick', 'draft_player.draft_player_id = pick.current_player_id AND draft.pack_number = pick.pack_number AND draft.pick_number = pick.pick_number', array(), 'left');		
		$select->where(array('draft.draft_id' => $draftId, 'has_joined' => 1));
	
		$selectString = $sql->getSqlStringForSqlObject($select);
		//var_dump($selectString);
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
	
		$indicators = array();
		foreach ($resultSet as $result)
		{
			$indicators[$result->draft_player_name] = !is_null($result->has_picked);
		}
	
		return $indicators;
	}
	
	public function getDraft($id)
	{
		$id  = (int) $id;
		$rowset = $this->tableGateway->select(array('draft_id' => $id));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find draft $id");
		}
		return $row;
	}
	
	public function getDraftByLobbyKey($lobbyKey)
	{
		$rowset = $this->tableGateway->select(array('lobby_key' => $lobbyKey));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find draft $lobbyKey");
		}
		return $row;
	}
	
	public function saveDraft(Draft $draft)
	{
		$data = array(
			'draft_id' => $draft->draftId,
			'status' => $draft->status,
			'created_on'  => $draft->createdOn,
			'host_id'  => $draft->hostId,
			'name'  => $draft->name,
			'pack_number'  => $draft->packNumber,
			'pick_number'  => $draft->pickNumber,
			'lobby_key'  => $draft->lobbyKey,
			'game_mode'  => $draft->game_mode,
		);
	
		$id = (int) $draft->draftId;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$draft->draftId = $this->tableGateway->lastInsertValue;
		} else {
			if ($this->getDraft($id)) {
				$this->tableGateway->update($data, array('draft_id' => $id));
			} else {
				throw new \Exception('Draft id does not exist');
			}
		}
	}
}