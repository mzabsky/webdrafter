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
	
	public function queryCards($query, &$messages)
	{
		$sql = new Sql($this->tableGateway->adapter);
		
		$unroll = false;
		if(substr($query,0,2) == "++"){
			$unroll = true;
			$query = substr($query, 2, strlen($query) - 2);
		}		
		
		$where = new \Zend\Db\Sql\Where();
		$where->greaterThan("set_version.created_on", "2016-09-29"); // Do not query cards that were uploaded before the on-site hosting was introduced
		
		$tokens = explode(" ", $query);
		
		$processedTokens = array();
		$openToken = null;
		foreach($tokens as $token) {
			if(strpos($token, '"') !== false){
				if($openToken === null){
					$openToken = $token;
				}
				else {
					$openToken .= " " . $token;
					$processedTokens[] = str_replace('"', '', $openToken);
					$openToken = null;
				}
			}
			else if($openToken !== null){
				$openToken .= " " . $token;
			}
			else {
				$processedTokens[] = $token;
			}
		}
		
		if($openToken !== null) {
			$processedTokens[] = str_replace('"', '', $openToken);
			$openToken = null;
		}
		
		$messages = array();
		foreach($processedTokens as $token) {
			$token = trim($token);
			if(strlen($token) == 0){
				continue;
			}
		
			$matches = array();
			$isMatch = preg_match("/^(?<prefix>-|!)?((?<attribute>[a-z]+)(?<infix>:|>|<|=|<=|>=|!|!=))?(?<value>[^=]*?)$/i", $token, $matches);
			if(!$isMatch){
				$messages[] = "Could not parse '{$token}'\n";
				continue;
			}
			
			$value = $matches["value"];
			$attribute = strtolower($matches["attribute"]);
			$infix = $matches["infix"];
			$prefix = $matches["prefix"];
		
			$negated = false;
			
			// Transform != a negated prefix
			if($infix == "!=") {
				if($prefix == "-"){
					$prefix = "";
					$infix = "=";
				}
				else if($prefix == ""){
					$prefix = "-";
					$infix = "=";
				}
			}
			
			if($prefix == "-" || $attribute == "not" )
			{
				$negated = true;
				$completeWhere = $where;				
				$where = new \Zend\Db\Sql\Where();
				
				if($attribute == "not"){
					$attribute = "is";
				}
			}
			
			if($infix == ":") {
				$infix = "=";
			}
			
			if($prefix == "!"){
				if($attribute != "" || $infix != ""){
					$messages[] = "Prefix operator '!' can be only used with a string literal in '{$token}'\n";
					continue;
				}
		
				$where = $where->and->nest()
					->equalTo('card.name', $value)->or
					->equalTo('card.name_2', $value)
					->unnest();
			}
			else if($attribute == "" || $infix == ""){
				$where = $where->and->nest()
					->or->like("card.name", "%".$value."%")
					->or->like("card.types", "%".$value."%")
					->or->like("card.rules_text", "%".$value."%")
					->or->like("card.name_2", "%".$value."%")
					->or->like("card.types_2", "%".$value."%")
					->or->like("card.rules_text_2", "%".$value."%")
					->unnest();
			}
			else if($attribute == "c" || $attribute == "color"){
				if($infix != "=" && $infix != "!"){
					$messages[] = "Operator '{$infix}' cannot be used with color\n";
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
				
				$numberOfColors = 0;
				if(strpos($str, "w") !== false){
					$where = $where->and->nest()
					->like('card.colors', "%W%")->or
					->like('card.colors_2', "%W%")
					->unnest();
					$numberOfColors++;					
				}
				
				if(strpos($str, "u") !== false){
					$where = $where->and->nest()
					->like('card.colors', "%U%")->or
					->like('card.colors_2', "%U%")
					->unnest();
					$numberOfColors++;
				}
				
				if(strpos($str, "b") !== false){
					$where = $where->and->nest()
					->like('card.colors', "%B%")->or
					->like('card.colors_2', "%B%")
					->unnest();
					$numberOfColors++;
				}
				
				if(strpos($str, "r") !== false){
					$where = $where->and->nest()
					->like('card.colors', "%R%")->or
					->like('card.colors_2', "%R%")
					->unnest();
					$numberOfColors++;
				}
				
				if(strpos($str, "g") !== false){
					$where = $where->and->nest()
					->like('card.colors', "%G")->or
					->like('card.colors_2', "%G%")
					->unnest();
					$numberOfColors++;
				}
				
				if(strpos($str, "c") !== false){
					$where = $where->and->nest()
					->equalTo('card.colors', "")->or
					->nest()->notEqualTo("shape", Card::SHAPE_NORMAL)->and->equalTo('card.colors_2', "")->unnest()
					->unnest();
					$numberOfColors = 0;
				}
				
				if(strpos($str, "m") !== false){
					$where = $where->and->nest()
					->andPredicate(new \Zend\Db\Sql\Predicate\Expression("LENGTH(card.colors) >= 2"))
					->orPredicate(new \Zend\Db\Sql\Predicate\Expression("LENGTH(card.colors) >= 2"))
					->unnest();
					$where = $where->andPredicate(new \Zend\Db\Sql\Predicate\Expression("LENGTH(card.colors) >= 2"));
				}				
				
				if($infix == "!") {
					// We have already tested presence of all the colors. Now test that there are no others
					$where = $where->andPredicate(new \Zend\Db\Sql\Predicate\Expression("LENGTH(card.colors) = $numberOfColors"));
				}
			}
			else if($attribute == "t" || $attribute == "type"){
				if($infix != "=" && $infix != "!"){
					$messages[] = "Operator '{$infix}' cannot be used with type\n";
					continue;
				}
				
				$value = str_replace("--", mb_convert_encoding("\x20\x14", 'UTF-8', 'UTF-16BE'), $value);
				$value = str_replace("-", mb_convert_encoding("\x20\x14", 'UTF-8', 'UTF-16BE'), $value);
				
				if($infix == "="){
					$where = $where->and->like("types", "%{$value}%");
				}			
				else if($infix == "!") {
					$where = $where->and->equalTo("types", $value);
				}
			}
			else if($attribute == "o" || $attribute == "oracle" || $attribute == "rules" || $attribute == "rt" || $attribute == "text"){
				if($infix != "=" && $infix != "!"){
					$messages[] = "Operator '{$infix}' cannot be used with rules text\n";
					continue;
				}
				
				if($infix == "="){
					$where = $where->and->nest()
					->or->like("card.rules_text", "%".$value."%")
					->or->like("card.rules_text_2", "%".$value."%")
					->unnest();
				}
				else if($infix == "!"){
					$where = $where->and->nest()
					->or->equalTo("card.rules_text", $value)
					->or->nest()->notEqualTo("shape", Card::SHAPE_NORMAL)->and->equalTo('card.rules_text_2', $value)->unnest()
					->unnest();
				}
			}
			else if($attribute == "f" || $attribute == "ft" || $attribute == "flavor"){
				if($infix != "=" && $infix != "!"){
					$messages[] = "Operator '{$infix}' cannot be used with flavor text\n";
					continue;
				}
				
				if($infix == "="){
					$where = $where->and->nest()
					->or->like("card.flavor_text", "%".$value."%")
					->or->like("card.flavor_text_2", "%".$value."%")
					->unnest();
				}
				else if($infix == "!"){
					$where = $where->and->nest()
					->or->equalTo("card.flavor_text", $value)
					->or->nest()->notEqualTo("shape", Card::SHAPE_NORMAL)->and->equalTo('card.flavor_text_2', $value)->unnest()
					->unnest();
				}
			}
			else if($attribute == "pow" || $attribute == "power"){
				if($infix == "!") {
					$infix = "=";
				}
				
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
				
				if(($infix == ">" || $infix == "<" || $infix == ">=" || $infix == "<=") && !$isNumericValid){
					$messages[] = "Power value '{$value}' is not valid\n";
					continue;
				}
				
				if($infix == "="){
					$where = $where->and->nest()
					->or->like("card.pt_string", "{$value}/%")
					->or->like("card.pt_string_2", "{$value}/%")
					->unnest();
				}
				if($infix == "!="){
					$where = $where
					->and->nest()
					->or->notLike("card.pt_string", "{$value}/%")
					->or->notLike("card.pt_string_2", "{$value}/%")
					->unnest()
					->and->notEqualTo("card.pt_string", "");
				}
				else if($infix == ">"){			
					$where = $where->and->nest()
					->or->greaterThan("card.power", $numericValue)
					->or->greaterThan("card.power_2", $numericValue2)
					->unnest();
				}
				else if($infix == ">="){
					$where = $where->and->nest()
					->or->greaterThanOrEqualTo("card.power", $numericValue)
					->or->greaterThanOrEqualTo("card.power_2", $numericValue2)
					->unnest();
				}
				else if($infix == "<"){
					$where = $where->and->nest()
					->or->lessThan("card.power", $numericValue)
					->or->lessThan("card.power_2", $numericValue2)
					->unnest();
				}
				else if($infix == "<="){
					$where = $where->and->nest()
					->or->lessThanOrEqualTo("card.power", $numericValue)
					->or->lessThanOrEqualTo("card.power_2", $numericValue2)
					->unnest();
				}
			}
			else if($attribute == "tou" || $attribute == "toughness"){
				if($infix == "!") {
					$infix = "=";
				}
				
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
				
				if(($infix == ">" || $infix == "<" || $infix == ">=" || $infix == "<=") && !$isNumericValid){
					$messages[] = "Toughness value '{$value}' is not valid\n";
					continue;
				}
				
				if($infix == "="){
					$where = $where->and->nest()
					->or->like("card.pt_string", "%/{$value}")
					->or->like("card.pt_string_2", "%/{$value}")
					->unnest();
				}
				if($infix == "!="){
					$where = $where
					->and->nest()
					->or->notLike("card.pt_string", "%/{$value}")
					->or->notLike("card.pt_string_2", "%/{$value}")
					->unnest()
					->and->notEqualTo("card.pt_string", "");
				}
				else if($infix == ">"){			
					$where = $where->and->nest()
					->or->greaterThan("card.toughness", $numericValue)
					->or->greaterThan("card.toughness_2", $numericValue2)
					->unnest();
				}
				else if($infix == ">="){
					$where = $where->and->nest()
					->or->greaterThanOrEqualTo("card.toughness", $numericValue)
					->or->greaterThanOrEqualTo("card.toughness_2", $numericValue2)
					->unnest();
				}
				else if($infix == "<"){
					$where = $where->and->nest()
					->or->lessThan("card.toughness", $numericValue)
					->or->lessThan("card.toughness_2", $numericValue2)
					->unnest();
				}
				else if($infix == "<="){
					$where = $where->and->nest()
					->or->lessThanOrEqualTo("card.toughness", $numericValue)
					->or->lessThanOrEqualTo("card.toughness_2", $numericValue2)
					->unnest();
				}
			}
			else if($attribute == "is"){
				if($infix == "!") {
					$infix = "=";
				}
				
				if($infix != "="){
					$messages[] = "Operator '{$infix}' cannot be used with is\n";
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
				if($infix == "!") {
					$infix = "=";
				}
				
				if($infix != "="){
					$messages[] = "Operator '{$infix}' cannot be used with rarity\n";
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
				if($infix == "!") {
					$infix = "=";
				}
				
				if($infix != "="){
					$messages[] = "Operator '{$infix}' cannot be used with set\n";
					continue;
				}
				
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
			else if($attribute == "artist" || $attribute == "art" || $attribute == "a"){
				if($infix != "=" && $infix != "!"){
					$messages[] = "Operator '{$infix}' cannot be used with artist\n";
					continue;
				}

				if($infix == "="){
					$where = $where->and->nest()
					->or->like("card.illustrator", "%".$value."%")
					->or->like("card.illustrator_2", "%".$value."%")
					->unnest();
				}
				else if($infix == "!"){
					$where = $where->and->nest()
					->or->equalTo("card.illustrator", $value)
					->or->nest()->notEqualTo("shape", Card::SHAPE_NORMAL)->and->equalTo('card.illustrator_2', $value)->unnest()
					->unnest();
				}
			}
			else if($attribute == "au" || $attribute == "author"){
				if($infix != "=" && $infix != "!"){
					$messages[] = "Operator '{$infix}' cannot be used with author\n";
					continue;
				}

				if($infix == "="){
					$where = $where->and->nest()
					->or->like("user.name", "%".$value."%")
					->or->like("user.url_name", "%".$value."%")
					->unnest();
				}
				else if($infix == "!"){
					$where = $where->and->nest()
					->or->equalTo("user.name", $value)
					->or->equalTo("user.url_name", $value)
					->unnest();
				}
			}
			else if($attribute == "d" || $attribute == "date"){
				$date = strtotime($value);
				
				if($date === false){
					$dateObject = \DateTime::createFromFormat("Y-m-d", $value);
					if($dateObject == null){
						$messages[] = "Date '{$matches["value"]}' could not be parsed\n";
						continue;
					}
					else {
						$date = $dateObject->getTimestamp();
					}
				}
				
				$date = date("Y-m-d H:i:s", $date);
				
				if($infix == "=" || $infix == "!"){
					$where = $where->and->equalTo("set_version.created_on", $date);
				}
				else if($infix == ">"){
					$where = $where->and->greaterThan("set_version.created_on", $date);
				}
				else if($infix == ">="){
					$where = $where->and->greaterThanOrEqualTo("set_version.created_on", $date);
				}
				else if($infix == "<"){
					$where = $where->and->lessThan("set_version.created_on", $date);
				}
				else if($infix == "<="){
					$where = $where->and->lessThanOrEqualTo("set_version.created_on", $date);
				}
			}
			else if($attribute == "st" || $attribute == "status"){
				if($infix == "!") {
					$infix = "=";
				}
				
				if($value == "play" || $value == "playable")
				{
					if($infix != "="){
						$messages[] = "Operator '{$infix}' cannot be used with status:playable\n";
						continue;
					}
					
					$value = "design";
					$infix = ">=";
				}
				
				$includeDiscontinued = false;
				switch (strtolower($value)){
					case "unplayable":
					case "un":
						$status = Set::STATUS_UNPLAYABLE;
						break;
					case "design":
						$status = Set::STATUS_DESIGN;
						break;
					case "development":
					case "develop":
					case "dev":
						$status = Set::STATUS_DEVELOPMENT;
						break;
					case "finishing":
						$status = Set::STATUS_FINISHING;
						break;
					case "finished":
					case "done":
						$status = Set::STATUS_FINISHED;
						break;
					case "discontinued":
					case "dis":
						$status = Set::STATUS_DISCONTINUED;
						$includeDiscontinued = true;
						break;
					default:
						$messages[] = "Status value '{$value}' is not valid\n";
						continue;
				}
				
				if($infix == "="){
					$where = $where->and->equalTo("set.status", $status);
				}
				else if($infix == "!="){
					$where = $where->and->notEqualTo("set.status", $status);
				}
				else if($infix == ">"){
					$where = $where->and->greaterThan("set.status", $status);
				}
				else if($infix == ">="){
					$where = $where->and->greaterThanOrEqualTo("set.status", $status);
				}
				else if($infix == "<"){
					$where = $where->and->lessThan("set.status", $status);
				}
				else if($infix == "<="){
					$where = $where->and->lessThanOrEqualTo("set.status", $status);
				}
				
				if(!$includeDiscontinued || $infix == "!="){
					$where = $where->and->notEqualto("set.status", Set::STATUS_DISCONTINUED);
				}
			}
			else if($attribute == "m" || $attribute == "mana"){
				if($infix != "=" && $infix != "!"){
					$messages[] = "Operator '{$infix}' cannot be used with mana cost\n";
					continue;
				}
				
				$value = strtr($value, "{}", "[]");
				
				// Wrap stand-alone letters in square braces (eg. 7GG -> [7][G][G] and 2[2/R] -> [2][2/R])
				$processedValue = "";
				$isInBrace = false;
				for ($i = 0; $i < strlen($value); $i++){
					$c = $value[$i];
					switch($c){						
						case "[":
							$isInBrace = true;
							$processedValue .= $c;
							break;
						case "]":
							$isInBrace = false;
							$processedValue .= $c;
							break;
						case "/":
							if(!$isInBrace){
								$processedValue .= $c;
							}
							break;
						default:
							if($isInBrace){
								$processedValue .= $c;
							}
							else {
								$processedValue .= "[{$c}]";
							}
					}
				}
				
				if($infix == "="){
					$where = $where->and->nest()->like("card.mana_cost", "%{$processedValue}%")->or->like("card.mana_cost_2", "%{$processedValue}%")->unnest();
				}
				else if($infix == "!"){
					$where = $where->and->nest()
					->or->equalTo("card.mana_cost", $processedValue)
					->or->nest()->notEqualTo("shape", Card::SHAPE_NORMAL)->and->equalTo('card.mana_cost_2', $processedValue)->unnest()
					->unnest();
				}
			}
			else if($attribute == "cmc"){
				if($infix == "!") {
					$infix = "=";
				}
				
				$isNumericValid = false;
				if(is_numeric($value)){
					$isNumericValid = true;
					$numericValue = (int)$value;
				}
				
				if(!$isNumericValid){
					$messages[] = "CMC value '{$value}' is not valid\n";
					continue;
				}
				
				if($infix == "="){
					$where = $where->and->equalTo("card.cmc", $numericValue);
				}
				else if($infix == ">"){			
					$where = $where->and->greaterThan("card.cmc", $numericValue);
				}
				else if($infix == ">="){
					$where = $where->and->greaterThanOrEqualTo("card.cmc", $numericValue);
				}
				else if($infix == "<"){
					$where = $where->and->lessThan("card.cmc", $numericValue);
				}
				else if($infix == "<="){
					$where = $where->and->lessThanOrEqualTo("card.cmc", $numericValue);
				}
			}
			else {
				$messages[] = "Unrecognized attribute '{$attribute}' in '{$token}\n";
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
		
		if(count($messages) > 0){
			return null;
		}
		
		//var_dump($where->getExpressionData());
		//$selectString = var_dump($sql->getSqlStringForSqlObject($where));
		$select = new Select('card');
		$select->join('set_version', 'card.set_version_id = set_version.set_version_id', array('set_version_name' => 'name'));
		if($unroll){
			$select->join('set', 'set_version.set_id = set.set_id', array('set_name' => 'name'));
		}
		else {
			$select->join('set', 'set_version.set_version_id = set.current_set_version_id', array('set_name' => 'name'));
		}	
		$select->join('user', 'set.user_id = user.user_id');
		
		$select->where->equalTo('set.is_private', 0);
		$select->limit(1000);
		
		//$select->forUpdate();
		$select->where($where);
		$select->order('set.set_id DESC, card.card_number ASC');
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