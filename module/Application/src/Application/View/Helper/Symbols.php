<?php
namespace Application\View\Helper;

use Traversable;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\RouteStackInterface;
use Zend\View\Exception;

class Symbols extends \Zend\View\Helper\AbstractHelper
{
	const STATE_NONE = 0;
	const STATE_W = 1;
	const STATE_U = 1;
	const STATE_B = 1;
	const STATE_R = 1;
	const STATE_G = 1;
	const STATE_P = 1; // Phyrexian
	const STATE_HP = 1; // Phyrexian
	const STATE_HW = 1; // Open hybrid
	const STATE_HU = 1;
	const STATE_HB = 1;
	const STATE_HR = 1;
	const STATE_HG = 1;
	const STATE_2 = 1; //
	const STATE_H2 = 1; // Open twobrid
	
    public function __invoke($str)
    {
    	$str = str_replace('[W]', '<span class="icon-wrapper"><i class="mtg white"></i></span>', $str);
    	$str = str_replace('[U]', '<span class="icon-wrapper"><i class="mtg blue"></i></span>', $str);
    	$str = str_replace('[B]', '<span class="icon-wrapper"><i class="mtg black"></i></span>', $str);
    	$str = str_replace('[R]', '<span class="icon-wrapper"><i class="mtg red"></i></span>', $str);
    	$str = str_replace('[G]', '<span class="icon-wrapper"><i class="mtg green"></i></span>', $str);
    	$str = str_replace('[C]', '<span class="icon-wrapper"><i class="mtg colorless"></i></span>', $str);
    	
    	$str = str_replace('[0]', '<span class="icon-wrapper"><i class="mtg mana-0"></i></span>', $str);
    	$str = str_replace('[1]', '<span class="icon-wrapper"><i class="mtg mana-1"></i></span>', $str);
    	$str = str_replace('[2]', '<span class="icon-wrapper"><i class="mtg mana-2"></i></span>', $str);
    	$str = str_replace('[3]', '<span class="icon-wrapper"><i class="mtg mana-3"></i></span>', $str);
    	$str = str_replace('[4]', '<span class="icon-wrapper"><i class="mtg mana-4"></i></span>', $str);
    	$str = str_replace('[5]', '<span class="icon-wrapper"><i class="mtg mana-5"></i></span>', $str);
    	$str = str_replace('[6]', '<span class="icon-wrapper"><i class="mtg mana-6"></i></span>', $str);
    	$str = str_replace('[7]', '<span class="icon-wrapper"><i class="mtg mana-7"></i></span>', $str);
    	$str = str_replace('[8]', '<span class="icon-wrapper"><i class="mtg mana-8"></i></span>', $str);
    	$str = str_replace('[9]', '<span class="icon-wrapper"><i class="mtg mana-9"></i></span>', $str);
    	$str = str_replace('[10]', '<span class="icon-wrapper"><i class="mtg mana-10"></i></span>', $str);
    	$str = str_replace('[11]', '<span class="icon-wrapper"><i class="mtg mana-11"></i></span>', $str);
    	$str = str_replace('[12]', '<span class="icon-wrapper"><i class="mtg mana-12"></i></span>', $str);
    	$str = str_replace('[13]', '<span class="icon-wrapper"><i class="mtg mana-13"></i></span>', $str);
    	$str = str_replace('[14]', '<span class="icon-wrapper"><i class="mtg mana-14"></i></span>', $str);
    	$str = str_replace('[15]', '<span class="icon-wrapper"><i class="mtg mana-15"></i></span>', $str);
    	$str = str_replace('[16]', '<span class="icon-wrapper"><i class="mtg mana-16"></i></span>', $str);
    	$str = str_replace('[17]', '<span class="icon-wrapper"><i class="mtg mana-17"></i></span>', $str);
    	$str = str_replace('[18]', '<span class="icon-wrapper"><i class="mtg mana-18"></i></span>', $str);
    	$str = str_replace('[19]', '<span class="icon-wrapper"><i class="mtg mana-19"></i></span>', $str);
    	$str = str_replace('[20]', '<span class="icon-wrapper"><i class="mtg mana-20"></i></span>', $str);
    	$str = str_replace('[X]', '<span class="icon-wrapper"><i class="mtg mana-x"></i></span>', $str);
    	$str = str_replace('[Y]', '<span class="icon-wrapper"><i class="mtg mana-y"></i></span>', $str);
    	$str = str_replace('[Z]', '<span class="icon-wrapper"><i class="mtg mana-z"></i></span>', $str);
    	$str = str_replace('[T]', '<span class="icon-wrapper"><i class="mtg tap"></i></span>', $str);
    	$str = str_replace('[UT]', '<span class="icon-wrapper"><i class="mtg untap"></i></span>', $str);
    	$str = str_replace('[Q]', '<span class="icon-wrapper"><i class="mtg untap"></i></span>', $str);
    	
    	$str = str_replace('[WU]', '<span class="icon-wrapper"><i class="mtg hybrid-wu"></i></span>', $str);
    	$str = str_replace('[UW]', '<span class="icon-wrapper"><i class="mtg hybrid-wu"></i></span>', $str);
    	$str = str_replace('[UB]', '<span class="icon-wrapper"><i class="mtg hybrid-ub"></i></span>', $str);
    	$str = str_replace('[BU]', '<span class="icon-wrapper"><i class="mtg hybrid-ub"></i></span>', $str);
    	$str = str_replace('[BR]', '<span class="icon-wrapper"><i class="mtg hybrid-br"></i></span>', $str);
    	$str = str_replace('[RB]', '<span class="icon-wrapper"><i class="mtg hybrid-br"></i></span>', $str);
    	$str = str_replace('[RG]', '<span class="icon-wrapper"><i class="mtg hybrid-rg"></i></span>', $str);
    	$str = str_replace('[GR]', '<span class="icon-wrapper"><i class="mtg hybrid-rg"></i></span>', $str);
    	$str = str_replace('[GW]', '<span class="icon-wrapper"><i class="mtg hybrid-gw"></i></span>', $str);
    	$str = str_replace('[WG]', '<span class="icon-wrapper"><i class="mtg hybrid-gw"></i></span>', $str);
    	$str = str_replace('[WB]', '<span class="icon-wrapper"><i class="mtg hybrid-wb"></i></span>', $str);
    	$str = str_replace('[BW]', '<span class="icon-wrapper"><i class="mtg hybrid-wb"></i></span>', $str);
    	$str = str_replace('[UR]', '<span class="icon-wrapper"><i class="mtg hybrid-ur"></i></span>', $str);
    	$str = str_replace('[RU]', '<span class="icon-wrapper"><i class="mtg hybrid-ur"></i></span>', $str);
    	$str = str_replace('[BG]', '<span class="icon-wrapper"><i class="mtg hybrid-bg"></i></span>', $str);
    	$str = str_replace('[GB]', '<span class="icon-wrapper"><i class="mtg hybrid-gb"></i></span>', $str);
    	$str = str_replace('[RW]', '<span class="icon-wrapper"><i class="mtg hybrid-rw"></i></span>', $str);
    	$str = str_replace('[WR]', '<span class="icon-wrapper"><i class="mtg hybrid-rw"></i></span>', $str);
    	$str = str_replace('[GU]', '<span class="icon-wrapper"><i class="mtg hybrid-gu"></i></span>', $str);
    	$str = str_replace('[UG]', '<span class="icon-wrapper"><i class="mtg hybrid-ug"></i></span>', $str);
    	$str = str_replace('[WU]', '<span class="icon-wrapper"><i class="mtg hybrid-wu"></i></span>', $str);
    	$str = str_replace('[2W]', '<span class="icon-wrapper"><i class="mtg hybrid-2w"></i></span>', $str);
    	$str = str_replace('[2U]', '<span class="icon-wrapper"><i class="mtg hybrid-2u"></i></span>', $str);
    	$str = str_replace('[2B]', '<span class="icon-wrapper"><i class="mtg hybrid-2b"></i></span>', $str);
    	$str = str_replace('[2R]', '<span class="icon-wrapper"><i class="mtg hybrid-2r"></i></span>', $str);
    	$str = str_replace('[2G]', '<span class="icon-wrapper"><i class="mtg hybrid-2g"></i></span>', $str);
    	$str = str_replace('[PW]', '<span class="icon-wrapper"><i class="mtg phyrexian-w"></i></span>', $str);
    	$str = str_replace('[PU]', '<span class="icon-wrapper"><i class="mtg phyrexian-u"></i></span>', $str);
    	$str = str_replace('[PB]', '<span class="icon-wrapper"><i class="mtg phyrexian-b"></i></span>', $str);
    	$str = str_replace('[PR]', '<span class="icon-wrapper"><i class="mtg phyrexian-r"></i></span>', $str);
    	$str = str_replace('[PG]', '<span class="icon-wrapper"><i class="mtg phyrexian-g"></i></span>', $str);
    	$str = str_replace('[P]', '<span class="icon-wrapper"><i class="mtg phyrexian"></i></span>', $str);
    	$str = str_replace('[S]', '<span class="icon-wrapper"><i class="mtg snow"></i></span>', $str);
    	$str = str_replace('[CHAOS]', '<span class="icon-wrapper"><i class="mtg chaos"></i></span>', $str);
    	return $str;
    }
}
