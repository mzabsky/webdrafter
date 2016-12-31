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
			$select->join('draft_set_version', 'draft_set_version.set_version_id = card.set_version_id');
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
	
	public function queryCards($query)
	{
		$sql = new Sql($this->tableGateway->adapter);
		
		$where = new \Zend\Db\Sql\Where();
		$where->greaterThan("set_version.created_on", "2016-09-29"); // Do not query cards that were uploaded before the on-site hosting was introduced
		
		$tokens = explode(" ", $query);
		
		$processedTokens = array();
		$openToken = NULL;
		foreach($tokens as $token) {
			if(strpos($token, '"') !== false){
				if($openToken === NULL){
					$openToken = $token;
				}
				else {
					$openToken .= " " . $token;
					$processedTokens[] = str_replace('"', '', $openToken);
					$openToken = NULL;
				}
			}
			else if($openToken !== NULL){
				$openToken .= " " . $token;
			}
			else {
				$processedTokens[] = $token;
			}
		}
		
		$messages = array();
		foreach($processedTokens as $token) {
			$token = trim($token);
			if(strlen($token) == 0){
				continue;
			}
		
			$matches = array();
			$isMatch = preg_match("/^(?<prefix>-|!)?((?<attribute>[a-z]+)(?<infix>:|>|<|=|<=|>=|!=))?(?<value>[^=]*?)$/i", $token, $matches);
			if(!$isMatch){
				$messages[] = "Could not parse '{$token}'\n";
				continue;
			}
			
			$value = $matches["value"];
			$attribute = $matches["attribute"];
		
			$negated = false;
			if($matches["prefix"] == "-" || $attribute == "not")
			{
				$negated = true;
				$completeWhere = $where;				
				$where = new \Zend\Db\Sql\Where();
				
				if($attribute == "not"){
					$attribute = "is";
				}
			}
			if($matches["prefix"] == "!"){
				if($attribute != "" || $matches["infix"] != ""){
					$messages[] = "Operator '!' can be only used with a string literal in '{$token}'\n";
					continue;
				}
		
				$where = $where->and->nest()
					->equalTo('card.name', $value)->or
					->equalTo('card.name_2', $value)
					->unnest();
			}
			else if($attribute == "" || $matches["infix"] == ""){
				$where = $where->and->nest()
					->or->like("card.name", "%".$value."%")
					->or->like("card.rules_text", "%".$value."%")
					->or->like("card.name_2", "%".$value."%")
					->or->like("card.rules_text_2", "%".$value."%")
					->unnest();
			}
			else if($attribute == "c" || $attribute == "color"){
				if($matches["infix"] != ":" && $matches["infix"] != "="){
					$messages[] = "Operator '{$matches["infix"]}' cannot be used with color'\n";
					continue;
				}
				
				$str = strtolower($matches["value"]);
				$trans = array(
					"white" => "w",
					"blue" => "u",
					"black" => "b",
					"red" => "r",
					"green" => "g",
					"colorless" => "c",
					"multicolor" => "m"
				);
				$str = strtr($str, $trans);
				if(strpos($str, "w") !== false){
					$where = $where->and->like("colors", "%W%");					
				}
				
				if(strpos($str, "u") !== false){
					$where = $where->and->like("colors", "%U%");
				}
				
				if(strpos($str, "u") !== false){
					$where = $where->and->like("colors", "%U%");
				}
				
				if(strpos($str, "b") !== false){
					$where = $where->and->like("colors", "%B%");
				}
				
				if(strpos($str, "r") !== false){
					$where = $where->and->like("colors", "%R%");
				}
				
				if(strpos($str, "g") !== false){
					$where = $where->and->like("colors", "%G%");
				}
				
				if(strpos($str, "c") !== false){
					$where = $where->and->equalTo("colors", "");
				}
				
				if(strpos($str, "m") !== false){
					$where = $where->andPredicate(new \Zend\Db\Sql\Predicate\Expression("LENGTH(card.colors) >= 2"));
				}				
			}
			else if($attribute == "t" || $attribute == "type"){
				if($matches["infix"] != ":" && $matches["infix"] != "="){
					$messages[] = "Operator '{$matches["infix"]}' cannot be used with type'\n";
					continue;
				}
				
				$where = $where->and->like("types", "%{$matches["value"]}%");			
			}
			else if($attribute == "o" || $attribute == "oracle" || $attribute == "rules" || $attribute == "text"){
				if($matches["infix"] != ":" && $matches["infix"] != "="){
					$messages[] = "Operator '{$matches["infix"]}' cannot be used with rules text'\n";
					continue;
				}
				
				$where = $where->and->nest()
					->or->like("card.rules_text", "%".$value."%")
					->or->like("card.rules_text_2", "%".$value."%")
					->unnest();
			}
			else if($attribute == "pow" || $attribute == "power"){
				$isNumericValid = false;
				if(is_numeric($value)){
					$isNumericValid = true;
					$numericValue = (int)$value;
					$numericValue2 = (int)$value;
				}
				else if($value == "tou" || $value == "toughness"){
					$isNumericValid = true;
					$numericValue = new \Zend\Db\Sql\Expression("card.toughness");
					$numericValue2 = new \Zend\Db\Sql\Expression("card.toughness_2");
				}
				
				if(($matches["infix"] == ">" || $matches["infix"] == "<" || $matches["infix"] == ">=" || $matches["infix"] == "<=") && !$isNumericValid){
					$messages[] = "Power value '{$value}' is not valid\n";
					continue;
				}
				
				if($matches["infix"] == ":" || $matches["infix"] == "="){
					$where = $where->and->nest()
					->or->like("card.pt_string", "{$value}/%")
					->or->like("card.pt_string_2", "{$value}/%")
					->unnest();
				}
				if($matches["infix"] == "!="){
					$where = $where
					->and->nest()
					->or->notLike("card.pt_string", "{$value}/%")
					->or->notLike("card.pt_string_2", "{$value}/%")
					->unnest()
					->and->notEqualTo("card.pt_string", "");
				}
				else if($matches["infix"] == ">"){			
					$where = $where->and->nest()
					->or->greaterThan("card.power", $numericValue)
					->or->greaterThan("card.power_2", $numericValue2)
					->unnest();
				}
				else if($matches["infix"] == ">="){
					$where = $where->and->nest()
					->or->greaterThanOrEqualTo("card.power", $numericValue)
					->or->greaterThanOrEqualTo("card.power_2", $numericValue2)
					->unnest();
				}
				else if($matches["infix"] == "<"){
					$where = $where->and->nest()
					->or->lessThan("card.power", $numericValue)
					->or->lessThan("card.power_2", $numericValue2)
					->unnest();
				}
				else if($matches["infix"] == "<="){
					$where = $where->and->nest()
					->or->lessThanOrEqualTo("card.power", $numericValue)
					->or->lessThanOrEqualTo("card.power_2", $numericValue2)
					->unnest();
				}
			}
			else if($attribute == "tou" || $attribute == "toughness"){
				$isNumericValid = false;
				if(is_numeric($value)){
					$isNumericValid = true;
					$numericValue = (int)$value;
					$numericValue2 = (int)$value;
				}
				else if($value == "pow" || $value == "power"){
					$isNumericValid = true;
					$numericValue = new \Zend\Db\Sql\Expression("card.power");
					$numericValue2 = new \Zend\Db\Sql\Expression("card.power_2");
				}
				
				if(($matches["infix"] == ">" || $matches["infix"] == "<" || $matches["infix"] == ">=" || $matches["infix"] == "<=") && !$isNumericValid){
					$messages[] = "Toughness value '{$value}' is not valid\n";
					continue;
				}
				
				if($matches["infix"] == ":" || $matches["infix"] == "="){
					$where = $where->and->nest()
					->or->like("card.pt_string", "%/{$value}")
					->or->like("card.pt_string_2", "%/{$value}")
					->unnest();
				}
				if($matches["infix"] == "!="){
					$where = $where
					->and->nest()
					->or->notLike("card.pt_string", "%/{$value}")
					->or->notLike("card.pt_string_2", "%/{$value}")
					->unnest()
					->and->notEqualTo("card.pt_string", "");
				}
				else if($matches["infix"] == ">"){			
					$where = $where->and->nest()
					->or->greaterThan("card.toughness", $numericValue)
					->or->greaterThan("card.toughness_2", $numericValue2)
					->unnest();
				}
				else if($matches["infix"] == ">="){
					$where = $where->and->nest()
					->or->greaterThanOrEqualTo("card.toughness", $numericValue)
					->or->greaterThanOrEqualTo("card.toughness_2", $numericValue2)
					->unnest();
				}
				else if($matches["infix"] == "<"){
					$where = $where->and->nest()
					->or->lessThan("card.toughness", $numericValue)
					->or->lessThan("card.toughness_2", $numericValue2)
					->unnest();
				}
				else if($matches["infix"] == "<="){
					$where = $where->and->nest()
					->or->lessThanOrEqualTo("card.toughness", $numericValue)
					->or->lessThanOrEqualTo("card.toughness_2", $numericValue2)
					->unnest();
				}
			}
			else if($attribute == "is"){
				if($matches["infix"] != ":" && $matches["infix"] != "="){
					$messages[] = "Operator '{$matches["infix"]}' cannot be used with is'\n";
					continue;
				}
				
				switch(strtolower($matches["value"])){
					case "normal":
						$where = $where->and->equalTo("shape", Card::SHAPE_NORMAL);
						break;
					case "dfc":
					case "transform":
						$where = $where->and->equalTo("shape", Card::SHAPE_DOUBLE);
						break;
					case "flip":
						$where = $where->and->equalTo("shape", Card::SHAPE_FLIP);
						break;
					case "split":
						$where = $where->and->equalTo("shape", Card::SHAPE_SPLIT);
						break;
					case "spell":
						$where = $where->and->nest()
						->or->like("card.types", "%instant%")
						->or->like("card.types_2", "%instant%")
						->or->like("card.types", "%sorcery%")
						->or->like("card.types_2", "%sorcery%")
						->unnest();
						break;
					case "token":
						$where = $where->and->nest()
						->or->like("card.types", "%token%")
						->unnest();
						break;
					case "emblem":
						$where = $where->and->nest()
						->or->like("card.types", "%emblem%")
						->unnest();
						break;
					case "permanent":
						$where = $where->and->nest()
						->or->like("card.types", "%creature%")
						->or->like("card.types", "%artifact%")
						->or->like("card.types", "%enchantment%")
						->or->like("card.types", "%planeswalker%")
						->or->like("card.types", "%land%")
						->or->like("card.types_2", "%creature%")
						->or->like("card.types_2", "%artifact%")
						->or->like("card.types_2", "%enchantment%")
						->or->like("card.types_2", "%planeswalker%")
						->or->like("card.types_2", "%land%")
						->unnest();
						break;
					case "etb":
						$where = $where->and->nest()
						->or->like("card.rules_text", "%enters the battlefield%")
						->or->like("card.rules_text_2", "%enters the battlefield%")
						->unnest();
						break;
					default:
						$messages[] = "'Is' value '{$value}' is not valid\n";
						continue;
				} 
			}
			else if($attribute == "r" || $attribute == "rarity"){
				if($matches["infix"] != ":" && $matches["infix"] != "="){
					$messages[] = "Operator '{$matches["infix"]}' cannot be used with rarity'\n";
					continue;
				}
				
				switch(strtolower($matches["value"])){
					case "common":
					case "c":
						$where = $where->and->equalTo("rarity", "C");
						break;
					case "uncommon":
					case "u":
						$where = $where->and->equalTo("rarity", "U");
						break;;
					case "rare":
					case "r":
						$where = $where->and->equalTo("rarity", "R");
						break;
					case "mythic":					
					case "m":
						$where = $where->and->equalTo("rarity", "M");
						break;
					case "special":					
					case "s":
						$where = $where->and->equalTo("rarity", "S");
						break;
					default:
						$messages[] = "Rarity value '{$value}' is not valid\n";
						continue;
				}
			}
			else if($attribute == "s" || $attribute == "set" || $attribute == "e" || $attribute == "edition"){
				$orSets = explode("|", $value);
				$where = $where->and->nest();
				foreach ($orSets as $orSet){
					$andSets = explode("+", $orSet);
					$where = $where->or->nest();
					foreach ($andSets as $andSet){
						$where = $where->and->nest()
						->or->like("set.name", "%".$andSet."%")
						->or->like("set.code", "%".$andSet."%")
						->or->like("set.url_name", "%".$andSet."%")
						->unnest();
					}					
					$where = $where->unnest();
				}
				$where = $where->unnest();
			}
			else {
				$messages[] = "Unrecognized attribute '{$attribute}' in '{$token}'\n";
			}
			
			if($negated)
			{
				// ZF doesn't support NOT. Some...fiddling is required to achieve it.
				$openSelect = new \Application\OpenSelect('card');
				$subExpression = $openSelect->processExpressionCallable($where, $this->tableGateway->adapter->getPlatform(), null, null, null);
				$where = $completeWhere;
				$where->andPredicate(new \Zend\Db\Sql\Predicate\Expression("NOT({$subExpression})"));
			}
		}
		
		//var_dump($where->getExpressionData());
		//$selectString = var_dump($sql->getSqlStringForSqlObject($where));
		$select = new Select('card');
		$select->join('set_version', 'card.set_version_id = set_version.set_version_id', array('set_version_name' => 'name'));
		$select->join('set', 'set_version.set_version_id = set.current_set_version_id', array('set_name' => 'name'));
		$select->where->equalTo('set.is_private', 0);
		$select->limit(1000);
		
		//$select->forUpdate();
		$select->where($where);
		//$select->order('draft.created_on DESC');
		$selectString = $sql->getSqlStringForSqlObject($select);
		var_dump($selectString);
		
		$resultSet = $this->tableGateway->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
		
		var_dump($messages);
		
		
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
	
	public function getCardsForBot($setVersionId, $name, $limit)
	{
		$setVersionId  = $setVersionId !== null ? (int) $setVersionId : null;
		
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('card');
			if($setVersionId !== null){
				$select->where->equalTo('set_version_id', $setVersionId);
			}
			else {
				$select->join('set_version', 'card.set_version_id = set_version.set_version_id', array('set_version_name' => 'name'));
				$select->join('set', 'set_version.set_version_id = set.current_set_version_id', array('set_name' => 'name'));
				$select->where->equalTo('set.is_private', 0);
			}
			$select->where->like('card.name', '%' . $name . '%');
			$select->order('name ASC')->limit($limit);
		//});
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
			$o->setName = $result->set_name;
			
			$resultArray[] = $o;
		}
		
		return $resultArray;
	}
	
	public function getCardHistory($firstVersionCardId)
	{
		$sql = new Sql($this->tableGateway->adapter);
		$select = new Select('card');
		//$select->forUpdate();
		$select->columns(array('*'));
		$select->join('set_version', 'set_version.set_version_id = card.set_version_id', array('set_version_name' => 'name', 'set_version_url_name' => 'url_name'), 'left');

		$where = new \Zend\Db\Sql\Where();
		$where->NEST
		->equalTo('card_id', $firstVersionCardId)
		->OR
		->equalTo('first_version_card_id', $firstVersionCardId)
		->UNNEST
		->AND
		->equalTo('is_changed', true);
		
		$select->where($where);
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