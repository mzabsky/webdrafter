<?php

require_once __DIR__."/../../../../../../vendor/Michelf/MarkdownInterface.php";
require_once __DIR__."/../../../../../../vendor/Michelf/Markdown.php";
require_once __DIR__."/../../../../../../vendor/Michelf/MarkdownExtra.php";

// This is separate to be callable from the wiki
function processMarkdown($text, $contextSetUrlName = null, $contextSetVersionUrlName = null)
{
	$str = \Michelf\MarkdownExtra::defaultTransform($text);
	 
	$str = processSymbols($str, $contextSetUrlName, $contextSetVersionUrlName);

	$config = HTMLPurifier_Config::createDefault();
	$def = $config->getHTMLDefinition(true);
	//var_dump($def);die();
	$def->addElement(
		'style',
		false,
		'Optional:', // Not `Empty` to not allow to autoclose the <script /> tag @see https://www.w3.org/TR/html4/interact/scripts.html
		'Common',
		array(
				// While technically not required by the spec, we're forcing
				// it to this value.
				'type' => 'Enum#text/css',
		)
	);
	
	/*$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
	$config->set('CSS.Trusted', true);
	$config->set('HTML.Allowed', 'b,style');*/
	$config->set('Core.HiddenElements', array('script'));
	$purifier = new HTMLPurifier($config);
	$str = $purifier->purify($str);



	$str .= "<div class='clear'></div>";
	return $str;
}

function processSymbols($str, $contextSetUrlName = null, $contextSetVersionUrlName = null, $fromWiki = false)
{
	if(!$fromWiki) 
	{
		$openBracket = "[";
		$closeBracket = "]";
	}
	else 
	{
		$openBracket = "{";
		$closeBracket = "}";
	}
	
	
	$alignments = array();
	$alignments["l"] = "left";
	$alignments["r"] = "right";

	$str = preg_replace_callback("/!([lr]?)\\$openBracket\\[(([a-z][a-z0-9-]+|[0-9]+|[0-9]+):(([a-z][a-z0-9-]+|[0-9]+|[0-9]+):)?)?(.+?|[0-9]+)(\|(.+))?\\]\\$closeBracket/",
			function($matches) use($contextSetUrlName, $contextSetVersionUrlName, $alignments)
			{
				if(strpos($matches[0], "<nowiki>") !== false) return $matches[0];
				return '<a href="/autocard?context=' . $contextSetUrlName . '&contextVersion=' . $contextSetVersionUrlName . '&set=' . urlencode($matches[3]) . '&setVersion=' . urlencode($matches[5]) . '&card=' . urlencode($matches[6]) . '" class="autocard" target="_blank">
		<img src="/autocard?image&context=' . $contextSetUrlName .'&contextVersion=' . $contextSetVersionUrlName . '&set=' . urlencode($matches[3]) .'&setVersion=' . urlencode($matches[5]) .'&card=' . urlencode($matches[6]) . '" class="autocard-image ' . ($matches[1] != NULL ? 'autocard-image-' . $alignments[$matches[1]] : '') . '" />
		</a>';
			}, $str);

	$str = preg_replace_callback("/\\$openBracket\\[(([a-z][a-z0-9-]+|[0-9]+|[0-9]+):(([a-z][a-z0-9-]+|[0-9]+|[0-9]+):)?)?(.+?|[0-9]+)(\|(.+))?\\]\\$closeBracket/",
			function($matches) use($contextSetUrlName, $contextSetVersionUrlName){
				if(strpos($matches[0], "<nowiki>") !== false) return $matches[0];
				return '<a href="/autocard?context=' . $contextSetUrlName . '&contextVersion=' . $contextSetVersionUrlName . '&set=' . urlencode($matches[2]) . '&setVersion=' . urlencode($matches[4]) . '&card=' . urlencode($matches[5]) . '" class="autocard" target="_blank">' . (count($matches) > 7 ? $matches[7] : $matches[5]) . '</a>';
			}, $str);

	$str = str_replace($openBracket . "W$closeBracket", '<span class="icon-wrapper"><i class="mtg white"></i></span>', $str);
	$str = str_replace($openBracket . "U$closeBracket", '<span class="icon-wrapper"><i class="mtg blue"></i></span>', $str);
	$str = str_replace($openBracket . "B$closeBracket", '<span class="icon-wrapper"><i class="mtg black"></i></span>', $str);
	$str = str_replace($openBracket . "R$closeBracket", '<span class="icon-wrapper"><i class="mtg red"></i></span>', $str);
	$str = str_replace($openBracket . "G$closeBracket", '<span class="icon-wrapper"><i class="mtg green"></i></span>', $str);
	$str = str_replace($openBracket . "C$closeBracket", '<span class="icon-wrapper"><i class="mtg colorless"></i></span>', $str);
	$str = str_replace($openBracket . "bW$closeBracket", '<span class="icon-wrapper"><i class="mtg plains"></i></span>', $str);
	$str = str_replace($openBracket . "bU$closeBracket", '<span class="icon-wrapper"><i class="mtg island"></i></span>', $str);
	$str = str_replace($openBracket . "bB$closeBracket", '<span class="icon-wrapper"><i class="mtg swamp"></i></span>', $str);
	$str = str_replace($openBracket . "bR$closeBracket", '<span class="icon-wrapper"><i class="mtg mountain"></i></span>', $str);
	$str = str_replace($openBracket . "bG$closeBracket", '<span class="icon-wrapper"><i class="mtg forest"></i></span>', $str);

	$str = str_replace($openBracket . "0$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-0"></i></span>', $str);
	$str = str_replace($openBracket . "1$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-1"></i></span>', $str);
	$str = str_replace($openBracket . "2$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-2"></i></span>', $str);
	$str = str_replace($openBracket . "3$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-3"></i></span>', $str);
	$str = str_replace($openBracket . "4$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-4"></i></span>', $str);
	$str = str_replace($openBracket . "5$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-5"></i></span>', $str);
	$str = str_replace($openBracket . "6$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-6"></i></span>', $str);
	$str = str_replace($openBracket . "7$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-7"></i></span>', $str);
	$str = str_replace($openBracket . "8$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-8"></i></span>', $str);
	$str = str_replace($openBracket . "9$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-9"></i></span>', $str);
	$str = str_replace($openBracket . "10$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-10"></i></span>', $str);
	$str = str_replace($openBracket . "11$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-11"></i></span>', $str);
	$str = str_replace($openBracket . "12$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-12"></i></span>', $str);
	$str = str_replace($openBracket . "13$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-13"></i></span>', $str);
	$str = str_replace($openBracket . "14$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-14"></i></span>', $str);
	$str = str_replace($openBracket . "15$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-15"></i></span>', $str);
	$str = str_replace($openBracket . "16$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-16"></i></span>', $str);
	$str = str_replace($openBracket . "17$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-17"></i></span>', $str);
	$str = str_replace($openBracket . "18$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-18"></i></span>', $str);
	$str = str_replace($openBracket . "19$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-19"></i></span>', $str);
	$str = str_replace($openBracket . "20$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-20"></i></span>', $str);
	$str = str_replace($openBracket . "X$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-x"></i></span>', $str);
	$str = str_replace($openBracket . "Y$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-y"></i></span>', $str);
	$str = str_replace($openBracket . "Z$closeBracket", '<span class="icon-wrapper"><i class="mtg mana-z"></i></span>', $str);
	$str = str_replace($openBracket . "T$closeBracket", '<span class="icon-wrapper"><i class="mtg tap"></i></span>', $str);
	$str = str_replace($openBracket . "UT$closeBracket", '<span class="icon-wrapper"><i class="mtg untap"></i></span>', $str);
	$str = str_replace($openBracket . "Q$closeBracket", '<span class="icon-wrapper"><i class="mtg untap"></i></span>', $str);

	$str = str_replace($openBracket . "WU$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-wu"></i></span>', $str);
	$str = str_replace($openBracket . "UW$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-wu"></i></span>', $str);
	$str = str_replace($openBracket . "UB$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-ub"></i></span>', $str);
	$str = str_replace($openBracket . "BU$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-ub"></i></span>', $str);
	$str = str_replace($openBracket . "BR$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-br"></i></span>', $str);
	$str = str_replace($openBracket . "RB$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-br"></i></span>', $str);
	$str = str_replace($openBracket . "RG$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-rg"></i></span>', $str);
	$str = str_replace($openBracket . "GR$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-rg"></i></span>', $str);
	$str = str_replace($openBracket . "GW$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-gw"></i></span>', $str);
	$str = str_replace($openBracket . "WG$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-gw"></i></span>', $str);
	$str = str_replace($openBracket . "WB$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-wb"></i></span>', $str);
	$str = str_replace($openBracket . "BW$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-wb"></i></span>', $str);
	$str = str_replace($openBracket . "UR$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-ur"></i></span>', $str);
	$str = str_replace($openBracket . "RU$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-ur"></i></span>', $str);
	$str = str_replace($openBracket . "BG$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-bg"></i></span>', $str);
	$str = str_replace($openBracket . "GB$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-gb"></i></span>', $str);
	$str = str_replace($openBracket . "RW$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-rw"></i></span>', $str);
	$str = str_replace($openBracket . "WR$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-rw"></i></span>', $str);
	$str = str_replace($openBracket . "GU$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-gu"></i></span>', $str);
	$str = str_replace($openBracket . "UG$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-ug"></i></span>', $str);
	$str = str_replace($openBracket . "WU$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-wu"></i></span>', $str);
	$str = str_replace($openBracket . "2W$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-2w"></i></span>', $str);
	$str = str_replace($openBracket . "2U$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-2u"></i></span>', $str);
	$str = str_replace($openBracket . "2B$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-2b"></i></span>', $str);
	$str = str_replace($openBracket . "2R$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-2r"></i></span>', $str);
	$str = str_replace($openBracket . "2G$closeBracket", '<span class="icon-wrapper"><i class="mtg hybrid-2g"></i></span>', $str);
	$str = str_replace($openBracket . "PW$closeBracket", '<span class="icon-wrapper"><i class="mtg phyrexian-w"></i></span>', $str);
	$str = str_replace($openBracket . "PU$closeBracket", '<span class="icon-wrapper"><i class="mtg phyrexian-u"></i></span>', $str);
	$str = str_replace($openBracket . "PB$closeBracket", '<span class="icon-wrapper"><i class="mtg phyrexian-b"></i></span>', $str);
	$str = str_replace($openBracket . "PR$closeBracket", '<span class="icon-wrapper"><i class="mtg phyrexian-r"></i></span>', $str);
	$str = str_replace($openBracket . "PG$closeBracket", '<span class="icon-wrapper"><i class="mtg phyrexian-g"></i></span>', $str);
	$str = str_replace($openBracket . "P$closeBracket", '<span class="icon-wrapper"><i class="mtg phyrexian"></i></span>', $str);
	$str = str_replace($openBracket . "S$closeBracket", '<span class="icon-wrapper"><i class="mtg snow"></i></span>', $str);
	$str = str_replace($openBracket . "CHAOS$closeBracket", '<span class="icon-wrapper"><i class="mtg chaos"></i></span>', $str);

	// https://fortawesome.github.io/Font-Awesome/cheatsheet/
	$str = preg_replace("/\\${openBracket}fa-([a-z0-9-]+)\\{$closeBracket}/", '<i class="fa fa-$1"></i>', $str);

	$str = str_replace(':)', '<i class="fa fa-smile-o"></i>', $str);
	$str = str_replace(':(', '<i class="fa fa-frown-o"></i>', $str);
	
	// Clear floats at the end if there are any floated images
	if(strpos($str, "autocard-image-") !== false) {
		$str .= "<div class='clear'></div>";
	}
	
	return $str;
}

