<?php

namespace Application\SetParser;

use Application\Model\Set;
use Application\Model\Card;
use function Application\toUrlName;

class IsochronDrafterSetParser
{
	public function Parse($string)
	{
		$cards = array();
		$usedNames = array();
		$usedUrlNames = array();
		
		$rows = explode("\n", $string);
		array_shift($rows); // Three meaningless lines in the start
		$version = array_shift($rows);
		array_shift($rows);
		
		if(trim($version) != "3"){
			throw new \Exception("Invalid version of the exporter.");
		}
		
		$currentCard = new \Application\Model\Card();
		$state = "shape";		
		foreach($rows as $row => $data)
		{
			$line = $row + 1; 
			
			$data = trim($data);
			
			//echo $state . " => " . $data . "<br/>";
			
			switch($state)
			{							
				case "shape":
					if($data == "normal") $currentCard->shape = Card::SHAPE_NORMAL;
					else if($data == "split") $currentCard->shape = Card::SHAPE_SPLIT;
					else if($data == "flip") $currentCard->shape = Card::SHAPE_FLIP;
					else if($data == "double") $currentCard->shape = Card::SHAPE_DOUBLE;
					else throw new \Exception("Invalid shape \"" . $data . "\" on line " . $line . ".");
					$state = "cardNumber";
					break;
				case "cardNumber":
					$currentCard->cardNumber = (int)(explode("/", $data)[0]);		

					if(!is_numeric($currentCard->cardNumber))
					{
						throw new \Exception("Card number must be an integer on line " . $line . ".");
					}
					
					$state = "cmc";
					break;
				case "cmc":
					if(!is_numeric($data))
					{
						throw new \Exception("CMC must be an integer on line " . $line . ".");
					}
					
					$currentCard->cmc = (int)$data;					
					$state = "rarity";
					break;
				case "rarity":
					if($data == "common") $currentCard->rarity = "C";
					else if($data == "uncommon") $currentCard->rarity = "U";
					else if($data == "rare") $currentCard->rarity = "R";
					else if($data == "mythic rare") $currentCard->rarity = "M";
					else if($data == "basic land") $currentCard->rarity = "B";
					else if($data == "special") $currentCard->rarity = "S";
					else throw new \Exception("Invalid rarity \"" . $data . "\" on line " . $line . ".");
					$state = "name";
					break;
					
				case "name":			
					if(in_array($data, $usedNames))
					{
						throw new \Exception("Name \"" . $data . "\" is used for more than one card on line " . $line . ".");
					}
					$usedNames[] = $data;
					$currentCard->name = $data;					
					$state = "colors";
					break;
				case "colors":
					$currentCard->colors = "";
					if(strpos($data, 'white') !== false) $currentCard->colors .= "W";
					if(strpos($data, 'blue') !== false) $currentCard->colors .= "U";
					if(strpos($data, 'black') !== false) $currentCard->colors .= "B";
					if(strpos($data, 'red') !== false) $currentCard->colors .= "R";
					if(strpos($data, 'green') !== false) $currentCard->colors .= "G";
					if(strpos($data, 'land') !== false) $currentCard->colors = "";
					$state = "manaCost";
					break;
				case "manaCost":
					$currentCard->manaCost = $data;
					$state = "types";
					break;
				case "types":
					$currentCard->types = $data;
					$state = "power";
					break;
				case "power":
					$currentCard->power = $data != "" ? (int)$data : null;
					$currentCard->ptString = $data != "" ? ($data . "/") : "";
					$state = "toughness";
					break;
				case "toughness":
					$currentCard->toughness = $data != "" ? (int)$data : null;	
					$currentCard->ptString .= $data;				
					$state = "rulesText";
					break;
				case "rulesText":
					$currentCard->rulesText = str_replace("///br///", "\n", $data);
					$state = "flavorText";
					break;
				case "flavorText":
					$currentCard->flavorText = str_replace("///br///", "\n", $data);
					$state = "illustrator";
					break;
				case "illustrator":
					$currentCard->illustrator = $data;
					$state = "name2";
					break;
					
				case "name2":
					$currentCard->name2 = $data;
					$state = "colors2";
					break;
				case "colors2":
					$currentCard->colors2 = "";
					if(strpos($data, 'white') !== false) $currentCard->colors2 .= "W";
					if(strpos($data, 'blue') !== false) $currentCard->colors2 .= "U";
					if(strpos($data, 'black') !== false) $currentCard->colors2 .= "B";
					if(strpos($data, 'red') !== false) $currentCard->colors2 .= "R";
					if(strpos($data, 'green') !== false) $currentCard->colors2 .= "G";
					if(strpos($data, 'land') !== false) $currentCard->colors2 = "";
					$state = "manaCost2";
					break;
				case "manaCost2":
					$currentCard->manaCost2 = $data;
					$state = "types2";
					break;
				case "types2":
					$currentCard->types2 = $data;
					$state = "power2";
					break;
				case "power2":
					$currentCard->power2 = $data != "" ? (int)$data : null;
					$currentCard->ptString2 = $data != "" ? ($data . "/") : "";
					$state = "toughness2";
					break;
				case "toughness2":
					$currentCard->toughness2 = $data != "" ? (int)$data : null;	
					$currentCard->ptString2 .= $data;				
					$state = "rulesText2";
					break;
				case "rulesText2":
					$currentCard->rulesText2 = str_replace("///br///", "\n", $data);
					$state = "flavorText2";
					break;
				case "flavorText2":
					$currentCard->flavorText2 = str_replace("///br///", "\n", $data);
					$state = "illustrator2";
					break;
				case "illustrator2":
					$currentCard->illustrator2 = $data;
					$state = "empty";
					break;

				case "empty":					
					if(strpos($currentCard->types, 'Token') === false)
					{
						$originalUrlName = toUrlName($currentCard->name);
						$urlName = $originalUrlName;
						$i = 0;
						while(isset($usedUrlNames[$urlName])){
							$i++;
							$urlName = $originalUrlName . $i;
						}
						$currentCard->urlName = $urlName;
						
						$cards[] = $currentCard;
					}
					
					$currentCard = new \Application\Model\Card();
					$state = "shape";
					break;
				default:
					throw new \Exception("Invalid state on line " . $line . ".");
			}			
		}
		
		if($state != "shape" && $state != "empty") throw new \Exception("Unexpected end of file, was in state " . $state . ".");
		
		return $cards;
	}
}
