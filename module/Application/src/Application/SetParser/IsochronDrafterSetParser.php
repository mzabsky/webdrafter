<?php

namespace Application\SetParser;

use Application\Model\Set;
use Application\Model\Card;

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

		if(trim($version) != "1.0"){
			throw new \Exception("Invalid or outdated version of the exporter.");
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
					else if($data == "emblem") $currentCard->shape = Card::SHAPE_NORMAL;
					else if($data == "split" || $data == "fuse split") $currentCard->shape = Card::SHAPE_SPLIT;
					else if($data == "flip") $currentCard->shape = Card::SHAPE_FLIP;
					else if($data == "double") $currentCard->shape = Card::SHAPE_DOUBLE;
					else if($data == "token") $currentCard->shape = Card::SHAPE_NORMAL;
					else if($data == "plane") $currentCard->shape = Card::SHAPE_PLANE;
					else if($data == "vsplit") $currentCard->shape = Card::SHAPE_VSPLIT;
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
					$actualName = strip_tags($data);
					if(in_array($actualName, $usedNames))
					{
						throw new \Exception("Name \"" . $data . "\" is used for more than one card on line " . $line . ".");
					}

					if(preg_match('/[;\[\]<>:|]/', $actualName))
					{
						throw new \Exception("Card name \"" . $data . "\" must not contain characters ;, |, :, [, ], < and >.");
					}

					$usedNames[] = $actualName;
					$currentCard->name = $actualName;
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
					elseif($data == "multicolor") $currentCard->colors = "WUBRG";

					$state = "manaCost";
					break;
				case "manaCost":
					$currentCard->manaCost = $this->replaceSymbols($data);
					if(strpos($currentCard->manaCost, '<img') !== false) {
						throw new \Exception("Card \"" . $currentCard->name . "\" contains one or more unsupported symbols in its mana cost.");
					}
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
					$currentCard->rulesText = $this->replaceSymbols(str_replace("///br///", "\n", $data));
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
					$currentCard->name2 = strip_tags($data);
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
					$currentCard->manaCost2 = $this->replaceSymbols($data);
					if(strpos($currentCard->manaCost2, '<img') !== false) {
						throw new \Exception("Card \"" . $currentCard->name . "\" contains one or more unsupported symbols in its mana cost.");
					}
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
					$currentCard->rulesText2 = $this->replaceSymbols(str_replace("///br///", "\n", $data));
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
					if(strpos($currentCard->types, 'Token') !== false || strpos($currentCard->types, 'Emblem') !== false)
					{
						$currentCard->rarity = "T";
					}

					if(strpos($currentCard->types, 'Basic') !== false)
					{
						$typeLineWords = preg_split('/\s+/', $currentCard->types);
						if(
							count($typeLineWords) == 4 
							&& in_array("Basic", $typeLineWords) 
							&& in_array("Land", $typeLineWords) 
							&& (in_array("Plains", $typeLineWords) || in_array("Island", $typeLineWords) || in_array("Swamp", $typeLineWords) || in_array("Mountain", $typeLineWords) || in_array("Forest", $typeLineWords))
						) 
						{
							$currentCard->rarity = "B";
						}
					}

					/*if(strpos($currentCard->types, 'Token') === false)
					{*/
						$originalUrlName = \Application\toUrlName($currentCard->name);
						$urlName = $originalUrlName;
						$i = 0;
						while(isset($usedUrlNames[$urlName])){
							$i++;
							$urlName = $originalUrlName . $i;
						}
						$currentCard->urlName = $urlName;

						$cards[] = $currentCard;
					//}

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

	private function replaceSymbols($str) {
 		$str = str_replace("<img src='magic-mana-small-T.png' alt='T' width='14' height='14'>", '[T]', $str);
 		$str = str_replace("<img src='magic-mana-small-Q.png' alt='Q' width='14' height='14'>", '[Q]', $str);
		$str = str_replace("<img src='magic-mana-small-S.png' alt='S' width='14' height='14'>", '[S]', $str);
		$str = str_replace("<img src='magic-mana-small-C.png' alt='C' width='14' height='14'>", '[C]', $str);
		$str = str_replace("<img src='magic-mana-small-H.png' alt='H' width='16' height='16'>", '[P]', $str);

		$str = str_replace("<img src='magic-mana-small-X.png' alt='X' width='14' height='14'>", '[X]', $str);
		$str = str_replace("<img src='magic-mana-small-Y.png' alt='Y' width='14' height='14'>", '[Y]', $str);
		$str = str_replace("<img src='magic-mana-small-Z.png' alt='Z' width='14' height='14'>", '[Z]', $str);

		$str = str_replace("<img src='magic-mana-small-0.png' alt='0' width='14' height='14'>", '[0]', $str);
		$str = str_replace("<img src='magic-mana-small-1.png' alt='1' width='14' height='14'>", '[1]', $str);
		$str = str_replace("<img src='magic-mana-small-2.png' alt='2' width='14' height='14'>", '[2]', $str);
		$str = str_replace("<img src='magic-mana-small-3.png' alt='3' width='14' height='14'>", '[3]', $str);
		$str = str_replace("<img src='magic-mana-small-4.png' alt='4' width='14' height='14'>", '[4]', $str);
		$str = str_replace("<img src='magic-mana-small-5.png' alt='5' width='14' height='14'>", '[5]', $str);
		$str = str_replace("<img src='magic-mana-small-6.png' alt='6' width='14' height='14'>", '[6]', $str);
		$str = str_replace("<img src='magic-mana-small-7.png' alt='7' width='14' height='14'>", '[7]', $str);
		$str = str_replace("<img src='magic-mana-small-8.png' alt='8' width='14' height='14'>", '[8]', $str);
		$str = str_replace("<img src='magic-mana-small-9.png' alt='9' width='14' height='14'>", '[9]', $str);
		$str = str_replace("<img src='magic-mana-small-10.png' alt='10' width='14' height='14'>", '[10]', $str);
		$str = str_replace("<img src='magic-mana-small-11.png' alt='11' width='14' height='14'>", '[11]', $str);
		$str = str_replace("<img src='magic-mana-small-12.png' alt='12' width='14' height='14'>", '[12]', $str);
		$str = str_replace("<img src='magic-mana-small-13.png' alt='13' width='14' height='14'>", '[13]', $str);
		$str = str_replace("<img src='magic-mana-small-14.png' alt='14' width='14' height='14'>", '[14]', $str);
		$str = str_replace("<img src='magic-mana-small-15.png' alt='15' width='14' height='14'>", '[15]', $str);
		$str = str_replace("<img src='magic-mana-small-16.png' alt='16' width='14' height='14'>", '[16]', $str);
		$str = str_replace("<img src='magic-mana-small-17.png' alt='17' width='14' height='14'>", '[17]', $str);
		$str = str_replace("<img src='magic-mana-small-18.png' alt='18' width='14' height='14'>", '[18]', $str);
		$str = str_replace("<img src='magic-mana-small-19.png' alt='19' width='14' height='14'>", '[19]', $str);
		$str = str_replace("<img src='magic-mana-small-20.png' alt='20' width='14' height='14'>", '[20]', $str);

		$str = str_replace("<img src='magic-mana-small-W.png' alt='W' width='14' height='14'>", '[W]', $str);
		$str = str_replace("<img src='magic-mana-small-U.png' alt='U' width='14' height='14'>", '[U]', $str);
		$str = str_replace("<img src='magic-mana-small-B.png' alt='B' width='14' height='14'>", '[B]', $str);
		$str = str_replace("<img src='magic-mana-small-R.png' alt='R' width='14' height='14'>", '[R]', $str);
		$str = str_replace("<img src='magic-mana-small-G.png' alt='G' width='14' height='14'>", '[G]', $str);

		$str = str_replace("<img src='magic-mana-small-2W.png' alt='2/W' width='16' height='16'>", '[2W]', $str);
		$str = str_replace("<img src='magic-mana-small-2U.png' alt='2/U' width='16' height='16'>", '[2U]', $str);
		$str = str_replace("<img src='magic-mana-small-2B.png' alt='2/B' width='16' height='16'>", '[2B]', $str);
		$str = str_replace("<img src='magic-mana-small-2R.png' alt='2/R' width='16' height='16'>", '[2R]', $str);
		$str = str_replace("<img src='magic-mana-small-2G.png' alt='2/G' width='16' height='16'>", '[2G]', $str);

		$str = str_replace("<img src='magic-mana-small-HW.png' alt='H/W' width='16' height='16'>", '[PW]', $str);
		$str = str_replace("<img src='magic-mana-small-HU.png' alt='H/U' width='16' height='16'>", '[PU]', $str);
		$str = str_replace("<img src='magic-mana-small-HB.png' alt='H/B' width='16' height='16'>", '[PB]', $str);
		$str = str_replace("<img src='magic-mana-small-HR.png' alt='H/R' width='16' height='16'>", '[PR]', $str);
		$str = str_replace("<img src='magic-mana-small-HG.png' alt='H/G' width='16' height='16'>", '[PG]', $str);

		$str = str_replace("<img src='magic-mana-small-WU.png' alt='W/U' width='16' height='16'>", '[WU]', $str);
		$str = str_replace("<img src='magic-mana-small-UW.png' alt='U/W' width='16' height='16'>", '[WU]', $str);
		$str = str_replace("<img src='magic-mana-small-UB.png' alt='U/B' width='16' height='16'>", '[UB]', $str);
		$str = str_replace("<img src='magic-mana-small-BU.png' alt='B/U' width='16' height='16'>", '[UB]', $str);
		$str = str_replace("<img src='magic-mana-small-BR.png' alt='B/R' width='16' height='16'>", '[BR]', $str);
		$str = str_replace("<img src='magic-mana-small-RB.png' alt='R/B' width='16' height='16'>", '[BR]', $str);
		$str = str_replace("<img src='magic-mana-small-RG.png' alt='R/G' width='16' height='16'>", '[RG]', $str);
		$str = str_replace("<img src='magic-mana-small-GR.png' alt='G/R' width='16' height='16'>", '[RG]', $str);
		$str = str_replace("<img src='magic-mana-small-GW.png' alt='G/W' width='16' height='16'>", '[GW]', $str);
		$str = str_replace("<img src='magic-mana-small-WG.png' alt='W/G' width='16' height='16'>", '[GW]', $str);
		$str = str_replace("<img src='magic-mana-small-WB.png' alt='W/B' width='16' height='16'>", '[WB]', $str);
		$str = str_replace("<img src='magic-mana-small-BW.png' alt='B/W' width='16' height='16'>", '[WB]', $str);
		$str = str_replace("<img src='magic-mana-small-UR.png' alt='U/R' width='16' height='16'>", '[UR]', $str);
		$str = str_replace("<img src='magic-mana-small-RU.png' alt='R/U' width='16' height='16'>", '[UR]', $str);
		$str = str_replace("<img src='magic-mana-small-BG.png' alt='B/G' width='16' height='16'>", '[BG]', $str);
		$str = str_replace("<img src='magic-mana-small-GB.png' alt='G/B' width='16' height='16'>", '[BG]', $str);
		$str = str_replace("<img src='magic-mana-small-RW.png' alt='R/W' width='16' height='16'>", '[RW]', $str);
		$str = str_replace("<img src='magic-mana-small-WR.png' alt='W/R' width='16' height='16'>", '[RW]', $str);
		$str = str_replace("<img src='magic-mana-small-UG.png' alt='U/G' width='16' height='16'>", '[UG]', $str);
		$str = str_replace("<img src='magic-mana-small-GU.png' alt='G/U' width='16' height='16'>", '[UG]', $str);
		$str = str_replace("<img src='magic-mana-small-chaos.png' alt='chaos' width='17' height='14'>", '[CHAOS]', $str);

		$str = str_replace("<span class=\"symbol\">", "", $str);
		$str = str_replace("</span>", "", $str);

		$str = preg_replace("/<img.*?>/", '	&#9072;', $str);


		return $str;
	}
}
