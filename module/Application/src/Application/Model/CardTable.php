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
	
	public function fetchBySetVersion($setVersionId)
	{
		$resultSet = $this->tableGateway->select(array('set_version_id' => $setVersionId));
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
	
	public function fetchPickedCards($draftPlayerId, $zone = null)
	{
		$resultSet = $this->tableGateway->select(function(\Zend\Db\Sql\Select $select) use($draftPlayerId, $zone){
			$select->join('pick', 'card.card_id = pick.card_id', array());
			$select->where(array('pick.current_player_id' => $draftPlayerId, 'is_picked' => 1));
			
			if($zone !== null)
			{
				$select->where(array('zone' => $zone));
			}
			
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
	
	public function getCardbyName($setVersionId, $name)
	{
		$setVersionId  = (int) $setVersionId;
		$rowset = $this->tableGateway->select(array('set_version_id' => $setVersionId, 'name' => $name));
		$row = $rowset->current();
		if (!$row) {
			throw new \Exception("Could not find card $name");
		}
		return $row;
	}
	
	public function saveCard(Card $card)
	{
		$data = array(
			'card_id' => $card->cardId,
			'set_version_id' => $card->setVersionId,
			'shape' => $card->shape,
			'card_number' => $card->cardNumber,
			'cmc'  => $card->cmc,
			'rarity'  => $card->rarity,
			'art_url'  => $card->artUrl,

			'name'  => $card->name,
			'colors'  => $card->colors,
			'mana_cost'  => $card->manaCost,
			'types'  => $card->types,
			'rules_text'  => $card->rulesText,
			'flavor_text'  => $card->flavorText,
			'power'  => $card->power,
			'toughness'  => $card->toughness,
			'pt_string'  => $card->ptString,
			'illustrator'  => $card->illustrator,

			'name_2'  => $card->name2,
			'colors_2'  => $card->colors2,
			'mana_cost_2'  => $card->manaCost2,
			'types_2'  => $card->types2,
			'rules_text_2'  => $card->rulesText2,
			'flavor_text_2'  => $card->flavorText2,
			'power_2'  => $card->power2,
			'toughness_2'  => $card->toughness2,
			'pt_string_2'  => $card->ptString2,
			'illustrator_2'  => $card->illustrator2
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