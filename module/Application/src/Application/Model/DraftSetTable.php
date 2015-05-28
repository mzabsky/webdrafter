<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;

class DraftSetTable
{
	protected $tableGateway;
	
	public function __construct(TableGateway $tableGateway)
	{
		$this->tableGateway = $tableGateway;
	}
	
	public function fetchByDraft($draftId)
	{
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftId){
			$select->where(array('draft_id' => $draftId));
			$select->order("pack_number ASC");
		});
		return $resultSet;
	}
	
	public function getDraftSet($id)
	{
		$id  = (int) $id;
		$rowset = $this->tableGateway->select(array('draft_set_id' => $id));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find draft set $id");
		}
		return $row;
	}
	
	public function saveDraftSet(DraftSet $draftSet)
	{
		$data = array(
			'draft_id' => $draftSet->draftId,
			'set_id' => $draftSet->setId,
			'pack_number' => $draftSet->packNumber
		);
	
		$id = (int) $draftSet->draftSetId;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$draftSet->draftSetId = $this->tableGateway->lastInsertValue;
		} else {
			if ($this->getDraftSet($id)) {
				$this->tableGateway->update($data, array('draft_set_id' => $id));
			} else {
				throw new \Exception('Draft set id does not exist');
			}
		}
	}
}