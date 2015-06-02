<?php
namespace Application\PackGenerator;

use Application\Model\Card;

class CubePackGenerator
{
	public function GeneratePacks($cards, $numberOfPacks)
	{
		$packSize = 15;
		
		// Resultset can't be iterated over repeatedly
		$cardsArray = array();
		while(count($cardsArray) < $numberOfPacks * 15)
		{
			foreach($cards as $card)
			{
				$cardsArray[] = $card;
			}	
		}
		
		$cards = $cardsArray;
		
		shuffle($cards);
		
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
