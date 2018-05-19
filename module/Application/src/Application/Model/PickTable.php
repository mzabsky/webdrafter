<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;

use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;

class PickTable
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

	public function lockPicks($draftId)
	{
		$draftId = (int)$draftId;
		$this->tableGateway->adapter->query("SELECT * FROM pick JOIN draft_player ON (draft_player_id = starting_player_id) WHERE draft_id=$draftId FOR UPDATE ", Adapter::QUERY_MODE_EXECUTE);
	}
	
	public function fetchBoosterForPlayer($draftPlayerId)
	{
		$sql = new Sql($this->tableGateway->adapter);
    $select = new Select('pick');
    $select->columns(['pick_id', 'card_id', 'starting_player_id', 'current_player_id', 'is_picked', 'pack_number', 'pick_number', 'zone', 'zone_column']);
		$select->join('draft_player', 'draft_player.draft_player_id = pick.current_player_id', array());
		$select->join('draft', 'draft.draft_id = draft_player.draft_id AND draft.pack_number = pick.pack_number', array());
		$select->join('card', 'card.card_id = pick.card_id', array('rarity' => 'rarity'));
		$select->where(array('draft_player.draft_player_id' => $draftPlayerId, 'is_picked' => 0));
		$select->order(new \Zend\Db\Sql\Expression('card_id'));

    $selectString = $sql->getSqlStringForSqlObject($select);
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
		
		$resultArray = array();
		foreach($resultSet as $result) {
			$resultArray[] = array(
				'pickId' => $result->pick_id,
				'cardId' => $result->card_id,
				'startingPlayerId' => $result->starting_player_id,
				'currentPlayerId' => $result->current_player_id,
				'isPicked' => $result->is_picked,
				'packNumber' => $result->pack_number,
				'pickNumber' => $result->pick_number,
				'zone' => $result->zone,
				'zoneColumn' => $result->zone_column,
				'rarity' => $result->rarity
			);
		}
		return $resultArray;
	}
	
	public function hasPickedFromCurrent($draftPlayerId)
	{
		$sql = new Sql($this->tableGateway->adapter);
    $select = new Select('pick');
    $select->columns(array('has_picked' => new \Zend\Db\Sql\Expression('COUNT(*)')));
		$select->join('draft_player', 'draft_player.draft_player_id = pick.current_player_id', array());
		$select->join('draft', 'draft.draft_id = draft_player.draft_id AND draft.pack_number = pick.pack_number AND draft.pick_number = pick.pick_number', array());
		$select->where(array('draft_player.draft_player_id' => $draftPlayerId));

    $selectString = $sql->getSqlStringForSqlObject($select);
    //var_dump($selectString);
    $resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
		
		foreach ($resultSet as $result)
		{
				return $result->has_picked;            
		}
		
		return 0;
	}
	
	public function getNumberOfCurrentPicksMade($draftId)
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('pick');
		//$select->forUpdate();
		$select->columns(array('picks_made' => new \Zend\Db\Sql\Expression('COUNT(*)')));
		$select->join('draft_player', 'draft_player.draft_player_id = pick.current_player_id');
		$select->join('draft', 'draft.draft_id = draft_player.draft_id AND draft.pack_number = pick.pack_number AND draft.pick_number = pick.pick_number');
		$select->where(array('draft.draft_id' => $draftId, 'is_picked' => 1));
	
		$selectString = $sql->getSqlStringForSqlObject($select) . " LOCK IN SHARE MODE";
		//var_dump($selectString);
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
	
		foreach ($resultSet as $result)
		{
			return (int)$result->picks_made;
		}
	
		return 0;
	}
	
	public function fetchPicksForPlayer($draftPlayerId, $orderByName)
	{
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftPlayerId, $orderByName){
			$select->join('draft_player', 'draft_player.draft_player_id = pick.current_player_id', array());
			$select->join('card', 'card.card_id = pick.card_id', array());
			$select->where(array('draft_player.draft_player_id' => $draftPlayerId, 'is_picked' => 1));
			$select->order($orderByName ? 'card.name ASC' : 'pick.pack_number ASC, pick.pack_number ASC');
		});
		return $resultSet;
	}
	
	/*public function fetchByHost($hostId)
	{
		$resultSet = $this->tableGateway->select(array('host_id', $hostId));
		return $resultSet;
	}*/
	
	public function getPick($id)
	{
		$id  = (int) $id;
		$rowset = $this->tableGateway->select(array('pick_id' => $id));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find pick $id");
		}
		return $row;
	}
	
	public function savePick(Pick $pick)
	{
		$data = array(
			'pick_id' => $pick->pickId,
			'card_id' => $pick->cardId,
			'starting_player_id' => $pick->startingPlayerId,
			'current_player_id'  => $pick->currentPlayerId,
			'is_picked'  => $pick->isPicked,
			'pack_number'  => $pick->packNumber,
			'pick_number'  => $pick->pickNumber,
			'zone'  => $pick->zone,
			'zone_column'  => $pick->zoneColumn,
		);
	
		$id = (int) $pick->pickId;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$pick->pickId = $this->tableGateway->lastInsertValue;
		} else {
			if ($this->getPick($id)) {
				$n = $this->tableGateway->update($data, array('pick_id' => $id));
				/*var_dump($n);
				var_dump($id );
		var_dump($data);*/
			} else {
				throw new \Exception('Pick id does not exist');
			}
		}
	}
	
	public function shiftPicks($fromPlayer, $toPlayer, $packNumber)
	{
		$this->tableGateway->update(
				array('current_player_id' => $toPlayer), 
				array(
					'current_player_id' => $fromPlayer, 
					'is_picked' => 0, 
					'pack_number' => $packNumber));
	}
}