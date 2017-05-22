<?php

namespace Application\SetParser;

use Application\Model\Set;
use Application\Model\Card;

class JsonSetParser
{
	public function Parse($string)
	{
		// TODO repeat card validation
		
		$jsonData = json_decode($string);
		$cards = array();
		$usedNames = array();
		$usedUrlNames = array();
		
		if(count($jsonData) > 1000) {
			throw new \Exception("Too many cards in the card file (1000 is maximum)");	
		}
		
		foreach ($jsonData as $i => $cardData) 
		{
			$currentCard = new \Application\Model\Card();
			$currentCard->shape = Card::SHAPE_NORMAL;
			
			$currentCard->name = $cardData->name;
			
			$currentCard->colors = "";
			if(in_array('White', $cardData->colors) !== false || in_array('W', $cardData->colors) !== false) $currentCard->colors .= "W";
			if(in_array('Blue', $cardData->colors) !== false || in_array('U', $cardData->colors) !== false) $currentCard->colors .= "U";
			if(in_array('Black', $cardData->colors) !== false || in_array('B', $cardData->colors) !== false) $currentCard->colors .= "B";
			if(in_array('Red', $cardData->colors) !== false || in_array('R', $cardData->colors) !== false) $currentCard->colors .= "R";
			if(in_array('Green', $cardData->colors) !== false || in_array('G', $cardData->colors) !== false) $currentCard->colors .= "G";
			
			$currentCard->cmc = (int)$cardData->cmc;
			
			$currentCard->flavorText = $cardData->flavor;
			
			$currentCard->imageName = $cardData->imageName;
			
			$currentCard->manaCost = $this->replaceSymbols($cardData->manaCost);
			
			$currentCard->cardNumber = (int)$cardData->number;
			
			$currentCard->power = (int)$cardData->power;
			$currentCard->toughness = (int)$cardData->toughness;
			$currentCard->ptString = $cardData->power != null && $cardData->toughness != null ? $cardData->power . "/" . $cardData->toughness : null;
			
			switch($cardData->rarity) {
				case "C":
				case "Common":
				case "B":
				case "Basic Land":
				case "T":
				case "Token":
					$currentCard->rarity = "C";
					break;
				case "U":
				case "Uncommon":
					$currentCard->rarity = "U";
					break;
				case "R":
				case "Rare":
					$currentCard->rarity = "R";
					break;
				case "Mythic Rare":
				case "M":
					$currentCard->rarity = "M";
					break;
				case "Special":
				case "S":
					$currentCard->rarity = "S";
					break;
				default:
				 	throw new \Exception("Unrecognized rarity \"$currentCard->rarity\" in card \"$currentCard->name\" (card $i)");
			}
			
			$currentCard->rulesText = $this->replaceSymbols($cardData->text);
			
			$currentCard->types = $cardData->types;
			
			$currentCard->illustrator = $cardData->artist;
			
			$originalUrlName = \Application\toUrlName($currentCard->name);
			$urlName = $originalUrlName;
			$i = 0;
			while(isset($usedUrlNames[$urlName])){
				$i++;
				$urlName = $originalUrlName . $i;
			}
			$currentCard->urlName = $urlName;
			
			$cards[] = $currentCard;
		}
		
		//var_dump($cards);die();

		return $cards;
	}

	private function replaceSymbols($str) {
 		$str = str_replace("{T}", '[T]', $str);
 		$str = str_replace("{Q}", '[Q]', $str);
		$str = str_replace("{S}", '[S]', $str);
		$str = str_replace("{C}", '[C]', $str);
		$str = str_replace("{P}", '[P]', $str);
		$str = str_replace("{E}", '[E]', $str);
                            
		$str = str_replace("{X}", '[X]', $str);
		$str = str_replace("{Y}", '[Y]', $str);
		$str = str_replace("{Z}", '[Z]', $str);
                            
		$str = str_replace("{0}", '[0]', $str);
		$str = str_replace("{1}", '[1]', $str);
		$str = str_replace("{2}", '[2]', $str);
		$str = str_replace("{3}", '[3]', $str);
		$str = str_replace("{4}", '[4]', $str);
		$str = str_replace("{5}", '[5]', $str);
		$str = str_replace("{6}", '[6]', $str);
		$str = str_replace("{7}", '[7]', $str);
		$str = str_replace("{8}", '[8]', $str);
		$str = str_replace("{9}", '[9]', $str);
		$str = str_replace("{10}", '[10]', $str);
		$str = str_replace("{11}", '[11]', $str);
		$str = str_replace("{12}", '[12]', $str);
		$str = str_replace("{13}", '[13]', $str);
		$str = str_replace("{14}", '[14]', $str);
		$str = str_replace("{15}", '[15]', $str);
		$str = str_replace("{16}", '[16]', $str);
		$str = str_replace("{17}", '[17]', $str);
		$str = str_replace("{18}", '[18]', $str);
		$str = str_replace("{19}", '[19]', $str);
		$str = str_replace("{20}", '[20]', $str);

		$str = str_replace("{W}", '[W]', $str);
		$str = str_replace("{U}", '[U]', $str);
		$str = str_replace("{B}", '[B]', $str);
		$str = str_replace("{R}", '[R]', $str);
		$str = str_replace("{G}", '[G]', $str);
		
		$str = str_replace("{2/W}", '[2W]', $str);
		$str = str_replace("{2/U}", '[2U]', $str);
		$str = str_replace("{2/B}", '[2B]', $str);
		$str = str_replace("{2/R}", '[2R]', $str);
		$str = str_replace("{2/G}", '[2G]', $str);
		
		$str = str_replace("{P/W}", '[PW]', $str);
		$str = str_replace("{P/U}", '[PU]', $str);
		$str = str_replace("{P/B}", '[PB]', $str);
		$str = str_replace("{P/R}", '[PR]', $str);
		$str = str_replace("{P/G}", '[PG]', $str);
		
		$str = str_replace("{W/P}", '[PW]', $str);
		$str = str_replace("{U/P}", '[PU]', $str);
		$str = str_replace("{B/P}", '[PB]', $str);
		$str = str_replace("{R/P}", '[PR]', $str);
		$str = str_replace("{G/P}", '[PG]', $str);
		
		$str = str_replace("{W/U}", '[WU]', $str);
		$str = str_replace("{U/W}", '[WU]', $str);
		$str = str_replace("{U/B}", '[UB]', $str);
		$str = str_replace("{B/U}", '[UB]', $str);
		$str = str_replace("{B/R}", '[BR]', $str);
		$str = str_replace("{R/B}", '[BR]', $str);
		$str = str_replace("{R/G}", '[RG]', $str);
		$str = str_replace("{G/R}", '[RG]', $str);
		$str = str_replace("{G/W}", '[GW]', $str);
		$str = str_replace("{W/G}", '[GW]', $str);
		$str = str_replace("{W/B}", '[WB]', $str);
		$str = str_replace("{B/W}", '[WB]', $str);
		$str = str_replace("{U/R}", '[UR]', $str);
		$str = str_replace("{R/U}", '[UR]', $str);
		$str = str_replace("{B/G}", '[BG]', $str);
		$str = str_replace("{G/B}", '[BG]', $str);
		$str = str_replace("{R/W}", '[RW]', $str);
		$str = str_replace("{W/R}", '[RW]', $str);
		$str = str_replace("{U/G}", '[UG]', $str);
		$str = str_replace("{G/U}", '[UG]', $str);

		return $str;
	}
}
