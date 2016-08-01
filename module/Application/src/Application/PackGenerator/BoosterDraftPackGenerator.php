<?php
namespace Application\PackGenerator;

use Application\Model\Card;

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
	
	public function GeneratePacks($cards, $numberOfPacks)
	{
		// Resultset can't be iterated over repeatedly
		if(!is_array($cards))
		{
			$cardsArray = array();
			foreach($cards as $card)
			{
				$cardsArray[] = $card;
			}
			$cards = $cardsArray;
		}
		
		$list = array();
		for($i = 0; $i < $numberOfPacks; $i++)
		{
			$list[] = $this->GeneratePack($cards);
		}

		return $list;
	}
	
	private function GeneratePack($cards)
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
		
		// Make sure initial five commons are not the pre-planned WUBRG commons
		//shuffle($pack);
		/*$pack = */$this->mtShuffle($pack);		
		
		return $pack;
	}
}
