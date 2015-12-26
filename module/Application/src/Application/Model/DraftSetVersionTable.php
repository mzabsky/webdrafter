<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;

class DraftSetVersionTable
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
	
	public function getDraftSetVersion($id)
	{
		$id  = (int) $id;
		$rowset = $this->tableGateway->select(array('draft_set_version_id' => $id));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find draft set version $id");
		}
		return $row;
	}
	
	public function saveDraftSetVersion(DraftSetVersion $draftSetVersion)
	{
		$data = array(
			'draft_id' => $draftSetVersion->draftId,
			'set_version_id' => $draftSetVersion->setVersionId,
			'pack_number' => $draftSetVersion->packNumber
		);
	
		$id = (int) $draftSetVersion->draftSetVersionId;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$draftSet->draftSetVersionId = $this->tableGateway->lastInsertValue;
		} else {
			if ($this->getDraftSetVersion($id)) {
				$this->tableGateway->update($data, array('draft_set_version_id' => $id));
			} else {
				throw new \Exception('Draft set id does not exist');
			}
		}
	}
}