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
	
	public function fetchBoosterForPlayer($draftPlayerId)
	{
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftPlayerId){			
			$select->join('draft_player', 'draft_player.draft_player_id = pick.current_player_id', array());
			$select->join('draft', 'draft.draft_id = draft_player.draft_id AND draft.pack_number = pick.pack_number', array());
			$select->where(array('draft_player.draft_player_id' => $draftPlayerId, 'is_picked' => 0));
			$select->order(new \Zend\Db\Sql\Expression('card_id'));
		});
		return $resultSet;
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
	
	public function fetchPicksForPlayer($draftPlayerId)
	{
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftPlayerId){
			$select->join('draft_player', 'draft_player.draft_player_id = pick.current_player_id', array());
			$select->where(array('draft_player.draft_player_id' => $draftPlayerId, 'is_picked' => 1));
			$select->order('pick.pack_number ASC, pick.pick_number ASC');
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