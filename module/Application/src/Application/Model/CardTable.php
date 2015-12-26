<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;

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
			return null;
		}
		return $row;
	}
	
	public function getCardByUrlName($setVersionId, $urlName)
	{
		$setVersionId  = (int) $setVersionId;
		$rowset = $this->tableGateway->select(array('set_version_id' => $setVersionId, 'url_name' => $urlName));
		$row = $rowset->current();
		if (!$row) {
			return null;
		}
		return $row;
	}
	
	public function getCardByName($setVersionId, $name)
	{
		$setVersionId  = (int) $setVersionId;
		$rowset = $this->tableGateway->select(array('set_version_id' => $setVersionId, 'name' => $name));
		$row = $rowset->current();
		if (!$row) {
			return null;
		}
		return $row;
	}
	
	public function getCardHistory($firstVersionCardId)
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('card');
		//$select->forUpdate();
		$select->columns(array('*'));
		$select->join('set_version', 'set_version.set_version_id = card.set_version_id', array('set_version_name' => 'name', 'set_version_url_name' => 'url_name'), 'left');
		$select->where(array('first_version_card_id' => $firstVersionCardId, 'is_changed' => true));
		$select->order("card.card_id DESC");
		$selectString = $sql->getSqlStringForSqlObject($select);
		//var_dump($selectString);
		
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
	
		$resultArray = array();
		foreach ($resultSet as $result)
		{
			$o = new \Application\Model\Card();
			$o->cardId = $result->card_id;
			$o->setVersionId = $result->set_version_id;
			$o->shape = $result->shape;
			$o->cardNumber = $result->card_number;
			$o->cmc = $result->cmc;
			$o->rarity = $result->rarity;
			$o->artUrl = $result->art_url;
			$o->urlName = $result->url_name;
			$o->firstVersionCardId = $result->first_version_card_id;
			$o->isChanged = $result->is_changed;
			$o->name = $result->name;
			$o->colors = $result->colors;
			$o->manaCost = $result->mana_cost;
			$o->types = $result->types;
			$o->rulesText = $result->rules_text;
			$o->flavorText = $result->flavor_text;
			$o->power = $result->power;
			$o->toughness = $result->toughness;
			$o->ptString = $result->pt_string;
			$o->illustrator = $result->illustrator;
			$o->name2 = $result->name_2;
			$o->colors2 = $result->colors_2;
			$o->manaCost2 = $result->mana_cost_2;
			$o->types2 = $result->types_2;
			$o->rulesText2 = $result->rules_text_2;
			$o->flavorText2 = $result->flavor_text_2;
			$o->power2 = $result->power_2;
			$o->toughness2 = $result->toughness_2;
			$o->ptString2 = $result->pt_string_2;
			$o->llustrator2 = $result->illustrator_2;
					
			$o->setVersionName = $result->set_version_name;
			$o->setVersionUrlName = $result->set_version_url_name;
			
			$resultArray[] = $o;
		}
		
		return $resultArray;
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
			'url_name'  => $card->urlName,
			'first_version_card_id'  => $card->firstVersionCardId,
			'is_changed'  => $card->isChanged,
			'changed_on'  => $card->changedOn,

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