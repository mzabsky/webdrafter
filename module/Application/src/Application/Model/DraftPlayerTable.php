<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Select;

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
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftId){
			$select->where(array('draft_id' => $draftId));
			$select->order("draft_player_id ASC");
		});
		return $resultSet;
	}
	
	public function fetchJoinedByDraft($draftId)
	{
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftId){
			$select->where(array('draft_id' => $draftId, 'has_joined' => 1));
			$select->order("draft_player_id ASC");
		});
		return $resultSet;
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
			'player_number'  => $draftPlayer->playerNumber,
			'datetime'  => $draftPlayer->datetime,
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