<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;

class CardTable
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
	
	public function fetchByDraft($draftId)
	{
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftId){
			$select->join('draft_set', 'draft_set.set_id = card.set_id');
			$select->where(array('draft_id' => $draftId));
			$select->order("card.name ASC");
		});
		return $resultSet;
	}
	
	public function fetchPickedCards($draftPlayerId, $zone)
	{
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftPlayerId, $zone){
			$select->join('pick', 'card.card_id = pick.card_id', array());
			$select->where(array('pick.current_player_id' => $draftPlayerId, 'is_picked' => 1, 'zone' => $zone));
			$select->order('pick.pack_number ASC, pick.pick_number ASC');
		});
		return $resultSet;
	}
	
	public function getCard($id)
	{
		$id  = (int) $id;
		$rowset = $this->tableGateway->select(array('card_id' => $id));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find card $id");
		}
		return $row;
	}
	
	public function saveCard(Card $card)
	{
		$data = array(
			'card_id' => $card->cardId,
			'set_id' => $card->setId,
			'name'  => $card->name,
			'colors'  => $card->colors,
			'rarity'  => $card->rarity,
			'art_url'  => $card->artUrl,
			'types'  => $card->types,
			'cmc'  => $card->cmc,
		);
	
		$id = (int) $card->cardId;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$card->cardId = $this->tableGateway->lastInsertValue;
		} else {
			if ($this->getCard($id)) {
				$this->tableGateway->update($data, array('card_id' => $id));
			} else {
				throw new \Exception('Card id does not exist');
			}
		}
	}
	
	/*public function delete($id)
	{
		$this->tableGateway->delete(array('id' => (int) $id));
	}*/
}