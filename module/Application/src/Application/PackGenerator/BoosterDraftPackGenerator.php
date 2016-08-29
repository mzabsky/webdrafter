<?php
namespace Application\PackGenerator;

use Application\Model\Card;
use Application\Model\SetVersion;

class BoosterDraftPackGenerator
{
	private function mtShuffle(&$array) {
		$array = array_values($array);
		for($i = count($array) - 1; $i > 0; --$i) {
			$j = mt_rand(0, $i);
			if($i !== $j) {
				list($array[$i], $array[$j]) = array($array[$j], $array[$i]);
			}
		}
		return true;
	}
	
	public function GeneratePacks($cards, $numberOfPacks, $basicLandSlotMode, $basicLandSlotNeedle)
	{
		$cardsArray = array();
		$basicLandSlotCardsArray = array();
		$basicLandSlotWeightSum = 0;
		
		$basicLandSlotCardCountByRarity = array();
		
		$cardsArray = array();
		foreach($cards as $card)
		{
			$belongsToBasicLandSlot = false;
			if(
				($basicLandSlotMode == SetVersion::BASIC_LAND_SLOT_NONBASIC_LAND && strpos($card->types, "Land") !== false) ||
				($basicLandSlotMode == SetVersion::BASIC_LAND_SLOT_SPECIAL && $card->rarity == 'S') ||
				($basicLandSlotMode == SetVersion::BASIC_LAND_SLOT_DFC && $card->shape == Card::SHAPE_DOUBLE) ||
				($basicLandSlotMode == SetVersion::BASIC_LAND_SLOT_TYPE && $basicLandSlotNeedle != null && $basicLandSlotNeedle != "" && strpos($card->types, $basicLandSlotNeedle) !== false) ||
				($basicLandSlotMode == SetVersion::BASIC_LAND_SLOT_RULES_TEXT && $basicLandSlotNeedle != null && $basicLandSlotNeedle != "" && strpos($card->rulesText, $basicLandSlotNeedle) !== false)
			) 
			{
				$belongsToBasicLandSlot = true;
			}
			
			if($belongsToBasicLandSlot)
			{
				
				if($card->rarity == 'C') $card->weight = 10;
				else if($card->rarity == 'U') $card->weight = 3;
				else if($card->rarity == 'R') $card->weight = 7.0/8.0;
				else if($card->rarity == 'M') $card->weight = 1.0/8.0;
				else if($card->rarity == 'S') $card->weight = 1;				
				
				if(isset($basicLandSlotCardCountByRarity[$card->rarity])){
					$basicLandSlotCardCountByRarity[$card->rarity]++;
				}
				else {
					$basicLandSlotCardCountByRarity[$card->rarity] = 1;
				}
					
				$basicLandSlotCardsArray[] = $card;
			}
			else 
			{
				$cardsArray[] = $card;
			}
		}
		
		/*$sum = 0;
		foreach($basicLandSlotCardsArray as $card)
		{
			$card->weight = $card->weight / ($basicLandSlotCardCountByRarity[$card->rarity] * 1.0);

			$sum += $card->weight / 14.0 * 100;
		}
		
		echo $sum;*/
		
		//foreach ($basicLandSlotCardsArray as $card) echo $card->name . " - " . ($card->weight / 14.0 * 100) . "%<br/>";
		
		$list = array();
		for($i = 0; $i < $numberOfPacks; $i++)
		{
			$list[] = $this->GeneratePack($cardsArray, $basicLandSlotCardsArray, 10 + 3 + 1);
		}

		return $list;
	}
	
	private function GeneratePack($cards, $basicLandSlotCards, $basicLandSlotWeightSum)
	{
		$hasMythics = false;
		$hasRares = false;
		$hasUncommons = false;
		
		foreach($cards as $card)
		{
			if($card->rarity == 'M') $hasMythics = true;
			if($card->rarity == 'R') $hasRares = true;
			if($card->rarity == 'U') $hasUncommons = true;
		}
		
		
		$numberOfCommons = 10;
		$numberOfUncommons = 3;
		
		/*$cards = */$this->mtShuffle($cards);
		
		$pack = array();
		$expectedNumberOfRares = 0;
		if($hasRares){
			$expectedNumberOfRares = 1;
			// Add rare
			if($hasMythics && rand(1, 8) == 8)
			{
				// Mythic
				foreach($cards as $card)
				{
					if($card->rarity == "M")
					{
						$pack[] = $card;
						break;
					}
				}
				//echo "Mythic<br/>";
					
			}
			else
			{
				// Rare
				foreach($cards as $card)
				{
					if($card->rarity == "R")
					{
						$pack[] = $card;
						break;
					}
				}
			}
		}
		
		// Fill in uncommons
		$uncommons = array_filter($cards, function($card)
		{
			return $card->rarity == "U";
		});
		/*$uncommons = */$this->mtShuffle($uncommons);
		//shuffle($uncommons);
		while(count($pack) < $numberOfUncommons + $expectedNumberOfRares && count($uncommons) > 0)
		{
			$pack[] = array_pop($uncommons);
		}
		
		// Add one common of each color, if able
		foreach(array("W", "U", "B", "R", "G") as $color)
		{
			foreach ($cards as $card)
			{
				if($card->colors == $color && $card->rarity == 'C')
				{
					$pack[] = $card;
					break;
				}
			}
		}
		
		// Fill in remaining commons
		$commons = array_filter($cards, function($card)
		{
			return $card->rarity == "C";
		});
		$commons = array_diff($commons, $pack);
		//shuffle($commons);
		/*$commons = */$this->mtShuffle($commons);
		while(count($pack) < $numberOfCommons + $numberOfUncommons + 1 && count($commons) > 0)
		{
			$pack[] = array_pop($commons);
		}

		if(count($pack) != $numberOfCommons + $numberOfUncommons + 1)
		{
			throw new \Exception("Could not generate booster pack, because the set doesn't have the necessary cards in it - it must have at least 10 commons, 3 uncommons a rare and a mythic rare.");
		}
		
		if(count($basicLandSlotCards) > 0)
		{
			// Select a card for the basic land slot
			$randomWeight = mt_rand(0, $basicLandSlotWeightSum * 100) / 100.0; // We need more precise weight than integers (since mythics have weight 1/8)
			$exploredWeight = 0;
			foreach($basicLandSlotCards as $card){
				$exploredWeight += $card->weight;
				if($exploredWeight > $randomWeight)
				{
					$pack[] = $card;
					break;
				} 	
			}
		}
		
		// Make sure initial five commons are not the pre-planned WUBRG commons
		//shuffle($pack);
		/*$pack = */$this->mtShuffle($pack);		
		
		return $pack;
	}
}
