<?php
namespace Application\PackGenerator;

use Application\Model\Card;
use Application\Model\SetVersion;

class PrecollatedPackGenerator
{
  private $collations;

  function __construct($collations) {
    $this->collations = \Application\resultSetToArray($collations);
  }

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
    //var_dump("collations", $this->collations);
    //var_dump("cards", $cards);

    $cardsById = array();
		foreach($cards as $card)
		{
      $cardsById[$card->cardId] = $card;
    }
    


    $cardsByPackNumber = array();
    foreach($this->collations as $collation)
    {
      if(array_key_exists($collation->cardId, $cardsById)) 
      { 
        // Card does not exist if the card doesn't belong to the current pack's

        if(!array_key_exists($collation->packNumber, $cardsByPackNumber)) 
        {
          $cardsByPackNumber[$collation->packNumber] = array();
        }

        $card = $cardsById[$collation->cardId];

        $cardsByPackNumber[$collation->packNumber][] = $card;
      }
    }

    $precollatedPackCount = count($cardsByPackNumber);

    // var_dump($cardsByPackNumber);

    $generatedPacks = array();
    for($i = 0; $i < $numberOfPacks; $i++) {
      if($i % $precollatedPackCount == 0)
      {
        // This changes indexing from string pack numbers to regular 0-based integer indexing
        $this->mtShuffle($cardsByPackNumber);
        
        // var_dump($cardsByPackNumber);
      }

      $generatedPacks[$i] = $cardsByPackNumber[$i % $precollatedPackCount];
    }

    // var_dump($generatedPacks);die();

    return $generatedPacks;
	}
}
