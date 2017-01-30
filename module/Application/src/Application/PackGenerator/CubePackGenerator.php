<?php
namespace Application\PackGenerator;

use Application\Model\Card;

class CubePackGenerator
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
		$packSize = 15;
		
		// Resultset can't be iterated over repeatedly
		$cardsArray = array();
		while(count($cardsArray) < $numberOfPacks * 15)
		{
			foreach($cards as $card)
			{
				if(strpos($card->types, "Basic") === false && strpos($card->types, "Token") === false)
				{
					$cardsArray[] = $card;
				}
			}	
		}
		
		$cards = $cardsArray;
		
		$this->mtShuffle($cards);
		
		$list = array();
		for($i = 0; $i < $numberOfPacks; $i++)
		{
			$pack = array();
			
			for($j = 0; $j < $packSize; $j++)
			{
				$pack[] = array_pop($cards);
			}
			
			$list[] = $pack;
		}

		return $list;
	}
}
