<?php

namespace Application\SetParser;

use Application\Model\Set;

class IsochronDrafterSetParser
{
	public function Parse($string)
	{
		$cards = array();
		
		$rows = explode("\n", $string);
		array_shift($rows); // Two meaningless lines in the start
		array_shift($rows);
		
		$currentCard = new \Application\Model\Card();
		$state = "name";
		foreach($rows as $row => $data)
		{
			$data = trim($data);
			
			switch($state)
			{
				case "name":
					$currentCard->name = $data;
					$state = "rarity";
					break;
				case "rarity":
					if($data == "common") $currentCard->rarity = "C";
					else if($data == "uncommon") $currentCard->rarity = "U";
					else if($data == "rare") $currentCard->rarity = "R";
					else if($data == "mythic rare") $currentCard->rarity = "M";
					else if($data == "basic land") $currentCard->rarity = "B";
					else if($data == "special") $currentCard->rarity = "S";
					else throw new \Exception("Invalid rarity " . $data);
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
					$state = "types";
					break;
				case "types":
					$currentCard->types = $data;
					$state = "cmc";
					break;
				case "cmc":
					$currentCard->cmc = (int)$data;
					$state = "empty";
					break;
				case "empty":
					if(strpos($currentCard->types, 'Token') !== false)
					{
						$cards[] = $currentCard;
					}
					
					$currentCard = new \Application\Model\Card();
					$state = "name";
					break;
				default:
					throw new \Exception("Invalid state");
			}			
		}
		
		if($state != "name" && $state != "empty") throw new \Exception("Unexpected end of file, was in state " . $state);
		
		return $cards;
	}
}
