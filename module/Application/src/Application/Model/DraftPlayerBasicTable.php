<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Select;

class DraftPlayerBasicTable
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
	
	public function fetchByDraftPlayer($draftPlayerId)
	{
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftPlayerId){
			$select->where(array('draft_player_id' => $draftPlayerId));
			$select->order("color ASC");
		});
		return $resultSet;
	}
	
	public function getByDraftPlayerAndColor($draftPlayerId, $color)
	{
		$draftPlayerId  = (int) $draftPlayerId;
		$rowset = $this->tableGateway->select(array('draft_player_id' => $draftPlayerId, 'color' => $color));
		$row = $rowset->current();
		if (!$row) {
			return null;
		}
		return $row;
	}
	
	public function getDraftPlayerBasic($draftPlayerBasicId)
	{
		$draftPlayerBasicId  = (int) $draftPlayerBasicId;
		$rowset = $this->tableGateway->select(array('draft_player_basic_id' => $draftPlayerBasicId));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception('Draft player basic id does not exist');
		}
		return $row;
	}
	
	public function saveDraftPlayerBasic(DraftPlayerBasic $draftPlayerBasic)
	{
		$data = array(
			'draft_player_basic_id' => $draftPlayerBasic->draftPlayerBasicId,
			'draft_player_id' => $draftPlayerBasic->draftPlayerId,
			'color' => $draftPlayerBasic->color,
			'count'  => $draftPlayerBasic->count,
		);
	
		$id = (int) $draftPlayerBasic->draftPlayerBasicId;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$draftPlayerBasic->draftPlayerBasicId = $this->tableGateway->lastInsertValue;
		} else {
			if ($this->getDraftPlayerBasic($id)) {
				$this->tableGateway->update($data, array('draft_player_basic_id' => $id,));
			} else {
				throw new \Exception('Draft player basic does not exist');
			}
		}
	}
}