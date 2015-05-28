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
					$currentCard->color = "";
					$state = "rarity";
					break;
				case "rarity":
					if($data == "common") $currentCard->rarity = "C";
					else if($data == "uncommon") $currentCard->rarity = "U";
					else if($data == "rare") $currentCard->rarity = "R";
					else if($data == "mythic rare") $currentCard->rarity = "M";
					else if($data == "basic land") $currentCard->rarity = "B";
					else if($data == "special") $currentCard->rarity = "S";
					else throw new \Exception("Invalid rarity");
					$state = "empty";
					break;
				case "empty":
					$cards[] = $currentCard;
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
