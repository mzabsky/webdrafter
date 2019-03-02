function Alea(seed) {
    if(seed === undefined) {seed = +new Date() + Math.random();}
    function Mash() {
        var n = 4022871197;
        return function(r) {
            for(var t, s, u = 0, e = 0.02519603282416938; u < r.length; u++)
            s = r.charCodeAt(u), f = (e * (n += s) - (n*e|0)),
            n = 4294967296 * ((t = f * (e*n|0)) - (t|0)) + (t|0);
            return (n|0) * 2.3283064365386963e-10;
        }
    }
    return function() {
        var m = Mash(), a = m(" "), b = m(" "), c = m(" "), x = 1, y;
        seed = seed.toString(), a -= m(seed), b -= m(seed), c -= m(seed);
        a < 0 && a++, b < 0 && b++, c < 0 && c++;
        return function() {
            var y = x * 2.3283064365386963e-10 + a * 2091639; a = b, b = c;
            return c = y - (x = y|0);
        };
    }();
}

function isMobileView() { return window.innerWidth <= 800 }

var Spoiler = function (options){
    this.data = options.data;    
    this.mainElement = options.element;
    this.enableControl = options.enableControl != null ? options.enableControl : true;
    this.enableLinks = options.enableLinks != null ? options.enableLinks : true;
    this.showVersion = options.showVersion != null ? options.showVersion : false;
    
    this.resetSeed();
    this.initializeStructure();
    this.initializeCardElements();
    this.initializeSorting();
    this.redraw();   

    var spoiler = this;
    $(window).resize(function() {
        spoiler.redraw();
    });
};

Spoiler.prototype.resetSeed = function() {    
    var seed = parseInt(this.getUrlVars().seed) || new Date().getTime();
    console.log("seed", seed)
    this.alea = Alea(seed);
}

Spoiler.prototype.getUrlVars = function () {
    var vars = [];
    var locationWithoutHash = window.location.href.split('#')[0];
    var hashes = locationWithoutHash.slice(locationWithoutHash.indexOf('?') + 1).split('&');
    for (let i = 0; i < hashes.length; i++) {
      let hash = hashes[i].split('=');
      vars.push(decodeURIComponent(hash[0]));
      vars[hash[0]] = decodeURIComponent(hash[1]);
    }
    return vars;
}

Spoiler.prototype.initializeStructure = function () {
	if(this.enableControl)
	{
		this.mainElement.html(
	        //"<div class='spoler-cards-no'>Number of cards: " + this.data.length + "</div>" +
            "<div class='spoiler-filters-and-sorts'>" +
                (!isMobileView() ? "<div class='spoiler-mode'>" +
	        "      Display: <a href='javascript:void(0)' onclick='spoiler.setMode(\"spoiler\")' mode='spoiler' class='activeSort' title='Display all card information'>Spoiler</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.setMode(\"images\")' mode='images' title='Display card images only'>Images</a>" +
            "   </div>" : "" ) +
	        "   <div class='spoiler-sort'>" +
	        "      Sort by: <a href='javascript:void(0)' onclick='spoiler.sort(\"color\")' sort='color' class='activeSort' title='Sort by color, then name'>Color</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.sort(\"rarity\")' sort='rarity' title='Sort by rarity, then color and name'>Rarity</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.sort(\"cmc\")' sort='cmc' title='Sort by converted mana cost, then color and name'>CMC</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.sort(\"name\")' sort='name' title='Sort by name'>Name</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.sort(\"random\")' sort='random' title='Sort randomly'>Randomly</a>" +
	        "   </div>" +
	        "   <div class='spoiler-filters spoiler-filters-rarity'>" +
	        "      Rarity filter: <a href='javascript:void(0)' onclick='spoiler.filterByRarity(\"M\")' filter='M' title='Mythic rares only'>M</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByRarity(\"R\")' filter='R' title='Rares only'>R</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByRarity(\"U\")' filter='U' title='Uncommons only'>U</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByRarity(\"C\")' filter='C' title='Commons only'>C</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByRarity(\"S\")' filter='S' title='Special only'>S</a>" +
	        "   </div>" +
	        "   <div class='spoiler-filters spoiler-filters-color'>" +
	        "      Color filter: <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"W\")' filter='W' title='White cards only'><span class='icon-wrapper'><i class='mtg white'></i></span></a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"U\")' filter='U' title='Blue cards only'><span class='icon-wrapper'><i class='mtg blue'></i></span></a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"B\")' filter='B' title='Black cards only'><span class='icon-wrapper'><i class='mtg black'></i></span></a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"R\")' filter='R' title='Red cards only'><span class='icon-wrapper'><i class='mtg red'></i></span></a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"G\")' filter='G' title='Green cards only'><span class='icon-wrapper'><i class='mtg green'></i></span></a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"M\")' filter='M' title='Multicolor cards only'><span class='icon-wrapper'><i class='mtg hybrid-wu'></i></span></a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"1\")' filter='1' title='Colorless cards only'><span class='icon-wrapper'><i class='mtg mana-1'></i></span></a>" +
	        "   </div>" +
	        "   <div class='spoiler-filters spoiler-filters-type'>" +
	        "      Type filter: <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Artifact\")' filter='Artifact' title='Artifacts only'>Artifact</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Creature\")' filter='Creature' title='Creatures only'>Creature</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Enchantment\")' filter='Enchantment' title='Enchantments only'>Enchantment</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Instant\")' filter='Instant' title='Instants only'>Instant</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Land\")' filter='Land' title='Lands only'>Land</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Planeswalker\")' filter='Planeswalker' title='Planeswalkers only'>Planeswalker</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Sorcery\")' filter='Sorcery' title='Sorceries only'>Sorcery</a>" +
	        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Token\")' filter='Token' title='Tokens only'>Token</a>" +
	        "   </div>" +
	        "</div>" +
	        "<br />" +
	        "<hr class='card-separator' />" +
	        "<div class='spoiler-cards'></div>");
	}
	else {
		this.mainElement.html("<div class='spoiler-cards'></div>");
	}

    this.cardsElement = $(".spoiler-cards", this.mainElement);
}

Spoiler.prototype.initializeCardElements = function () {
    this.imageCardElements = new Array();
    this.spoilerCardElements = new Array();
    
    this.mode = "spoiler";
    
    for (var i = 0; i < this.data.length; i++) {
        var card = this.data[i];

        this.imageCardElements[card.cardId] = $("" +
        	"<a href='" + card.url + "'><img src='" + card.artUrl + "' alt='" + card.name + "' class='card-image card-image-" + card.shape + "' /></a>");
        
        this.spoilerCardElements[card.cardId] = $("" +
            "<table class='card-detail shape-" + card.shape + "'>" +
            "   <tr>" +
            "       <td class='card-image-day card-front' rowspan='15'><div class='lazyload' data-original='" + card.artUrl + "'></td>" +
            (card.shape == "flip" || card.shape == "double" ? "<td class='card-image-night card-back' rowspan='15'><div class='lazyload' data-original='" + card.artUrl + "'></td>" : "") +
            "       <td class='card-main-row card-front'><div class='card-name'><a href='" + card.url + "'>" + card.name + "</a> " + (this.showVersion ? "<span class='card-set-version'>(" + card.setVersionLink + ")</span>" : "") +"</div>" + (card.manaCost != null ?"<div class='card-cost'>" + card.manaCost + "</div></td>" : "</td>")+            
            "   </tr>" +
            "   <tr>" +
            "       <td class='card-type-row card-front'><div class='card-types'>" + card.types + "</div><div class='card-rarity card-rarity-" + card.rarity.toLowerCase() +"'>" + card.rarityName + "</div></td>" +
            "   </tr>" +
            "   <tr>" +
            "       <td class='card-rules-row card-front'><div class='card-rules'>" + card.rulesText + "</div></td>" +
            "   </tr>" +
            (card.flavorText != null ? "   <tr>" +
            "       <td class='card-flavor-row card-front'><div class='card-flavor'>" + card.flavorText + "</div></td>" +
            "   </tr>" : "") +
            (card.ptString != null ? "   <tr>" +
            "       <td class='card-pt-row card-front'><div class='card-pt'>" + card.ptString + "</div></td>" +
            "   </tr>" : "") +
            (card.name2 != "" ?
                "   <tr>" +
                "       <td class='card-separator-row'><hr /></td>" +
                "   </tr>" +
                "   <tr>" +
                "       <td class='card-main-row card-back'><div class='card-name'><a href='" + card.url + "'>" + card.name2 + "</a></div>" + (card.manaCost2 != null ?"<div class='card-cost'>" + card.manaCost2 + "</div></td>" : "</td>")+
                "   </tr>" +
                "   <tr>" +
                "       <td class='card-type-row card-back'><div class='card-types'>" + card.types2 + "</div><div class='card-rarity card-rarity-" + card.rarity.toLowerCase() +"'>" + card.rarityName + "</div></td>" +
                "   </tr>" +
                "   <tr>" +
                "       <td class='card-rules-row card-back'><div class='card-rules'>" + card.rulesText2 + "</div></td>" +
                "   </tr>" +
                (card.flavorText2 != null ? "   <tr>" +
                "       <td class='card-flavor-row card-back'><div class='card-flavor'>" + card.flavorText2 + "</div></td>" +
                "   </tr>" : "") +
                (card.ptString2 != null ? "   <tr>" +
                "       <td class='card-pt-row card-back'><div class='card-pt'>" + card.ptString2 + "</div></td>" +
                "   </tr>" : "")
            : "") +
            (this.enableLinks ?
	            "   <tr>" +
	            "       <td class='card-filler-row'>" +
	            "			<a href='javascript:void(0)' onclick='spoiler.copyAsBBCode(\"" + card.name + "\")'>Copy as forum code</a>" + 
	    		"			<a href='javascript:void(0)' onclick='spoiler.copyUrl(\"" + card.name + "\")'>Copy permalink</a>" +
	    		"			<a href='javascript:void(0)' onclick='spoiler.copyArtUrl(\"" + card.name + "\")'>Copy image URL</a>" +
	    		"		</td>" +
	            "   </tr>"
            : "") +
            "</table><hr class='card-separator' />"

        );
        
        if(!this.enableLinks){
        	$('a', this.spoilerCardElements[card.cardId]).each(function(){
                $(this).replaceWith( this.childNodes );
            });
        }
    }
}

Spoiler.prototype.initializeSorting = function () {
	if(this.enableControl){
		this.order = "color";
	}    
	else {
		this.order = "sequence";
	}

	var modifierFunction = function(x) {
    	var modifier = 0;
    	if(x.types.indexOf("Basic") !== -1 && x.types.indexOf("Land") !== -1) modifier = 50;
    	if(x.types.indexOf("Token") !== -1 || x.types.indexOf("Emblem") !== -1) modifier = 100;
    	return modifier;
	}
	
    this.sortFunctions = {
        "color": function(spoiler, a, b) {
            var rate = function(x) {
            	var modifier = modifierFunction(x);
            	if (x.colors.length == 0 && x.rarity == 'B') return 8 + modifier;
            	else if (x.colors.length == 0 && x.types.indexOf("Artifact") == -1) return 7 + modifier;
                else if (x.colors.length == 0 && x.types.indexOf("Artifact") != -1) return 6 + modifier;
                else if (x.colors.length > 1) return 5 + modifier;
                else if (x.colors[0] == 'G') return 4 + modifier;
                else if (x.colors[0] == 'R') return 3 + modifier;
                else if (x.colors[0] == 'B') return 2 + modifier;
                else if (x.colors[0] == 'U') return 1 + modifier;
                else if (x.colors[0] == 'W') return 0 + modifier;
                else throw "Not reached " + x.colors[0];
            }

            var ratingA = rate(a);
            var ratingB = rate(b);

            if (ratingA == ratingB) return a.name.localeCompare(b.name);
            else return ratingA - ratingB;
        },
        "rarity": function (spoiler, a, b) {
            var rate = function (x) {
            	var modifier = modifierFunction(x);
                if (x.rarity == 'T') return 7 + modifier;
                else if (x.rarity == 'B') return 6 + modifier;
                else if (x.rarity == 'S') return 5 + modifier;
                else if (x.rarity == 'C') return 4 + modifier;
                else if (x.rarity == 'U') return 3 + modifier;
                else if (x.rarity == 'R') return 2 + modifier;
                else if (x.rarity == 'M') return 1 + modifier;
                else throw "Not reached \"" + x.rarity + "\"";
            }

            var ratingA = rate(a);
            var ratingB = rate(b);


            if (ratingA == ratingB) return spoiler.sortFunctions["color"](spoiler, a, b);
            else return ratingA - ratingB;
        },
        "cmc": function (spoiler, a, b) {
            var ratingA = a.cmc != null ? (a.rarity != 'B' ? a.cmc : -2) : -1;
            var ratingB = b.cmc != null ? (b.rarity != 'B' ? b.cmc : -2) : -1;

            if (ratingA == ratingB) return spoiler.sortFunctions["color"](spoiler, a, b);
            else return ratingA - ratingB;
        },
        "name": function (spoiler, a, b) {
            return a.name.localeCompare(b.name);
        },
        "sequence": function (spoiler, a, b) {
            return a.id - b.id;
        },
        "random": function (spoiler, a, b) {
            return spoiler.alea() - spoiler.alea();
        },
    };

}

Spoiler.prototype.initializeFilters = function() {
    this.rarityFilter = null;
    this.typeFilter = null;
    this.colorFilter = null;
}

Spoiler.prototype.sort = function (sortFunction) {
    this.order = sortFunction;
    this.redraw();
    $('.activeSort', $(".spoiler-sort", this.mainElement)).removeClass('activeSort');
    $("[sort='" + this.order + "']", $(".spoiler-sort", this.mainElement)).addClass('activeSort');
}

Spoiler.prototype.filterByRarity = function (filterValue) {
    if (filterValue == this.rarityFilter) {
        this.rarityFilter = null;
    } else {
        this.rarityFilter = filterValue;
    }

    this.redraw();

    $('.activeSort', $(".spoiler-filters-rarity", this.mainElement)).removeClass('activeSort');
    $("[filter='" + this.rarityFilter + "']", $(".spoiler-filters-rarity", this.mainElement)).addClass('activeSort');
}

Spoiler.prototype.filterByType = function (filterValue) {
    if (filterValue == this.typeFilter) {
        this.typeFilter = null;
    } else {
        this.typeFilter = filterValue;
    }

    this.redraw();

    $('.activeSort', $(".spoiler-filters-type", this.mainElement)).removeClass('activeSort');
    $("[filter='" + this.typeFilter + "']", $(".spoiler-filters-type", this.mainElement)).addClass('activeSort');
}

Spoiler.prototype.filterByColor = function (filterValue) {
    if (filterValue == this.colorFilter) {
        this.colorFilter = null;
    } else {
        this.colorFilter = filterValue;
    }

    this.redraw();

    $('.activeSort', $(".spoiler-filters-color", this.mainElement)).removeClass('activeSort');
    $("[filter='" + this.colorFilter + "']", $(".spoiler-filters-color", this.mainElement)).addClass('activeSort');
}

Spoiler.prototype.setMode = function (mode) {
    this.mode = mode;

    this.redraw();

    $('.activeSort', $(".spoiler-mode", this.mainElement)).removeClass('activeSort');
    $("[mode='" + this.mode + "']", $(".spoiler-mode", this.mainElement)).addClass('activeSort');
}

Spoiler.prototype.getCardByName = function (cardName) {
    var card;
    for (var i = 0; i < this.data.length; i++) {
        if (this.data[i].name == cardName) return this.data[i];
    }

    return null;
}

Spoiler.prototype.copyArtUrl = function(cardName) {
	var card = this.getCardByName(cardName);
    showCopyable(card.artUrl);
    return false;
};


Spoiler.prototype.copyUrl = function(cardName) {
	var card = this.getCardByName(cardName);
    showCopyable(card.url);
    return false;
};

//In-built replace is dumb and replaces only one occurence
Spoiler.prototype.smartReplace = function (haystack, needle, replace) {
    return haystack.replace(new RegExp(needle, 'g'), replace);
}

Spoiler.prototype.copyAsBBCode = function (cardName) {
	var card = this.getCardByName(cardName);

    showCopyable(card.bbCode);
}

Spoiler.prototype.copyAsBBLink = function (cardName) {
    var bbCode = (this.localUrl != null ? "[url=" + this.localUrl + "#" + cardName + "]" + cardName + "[/url]" : cardName);
    showCopyable(bbCode);
}

Spoiler.prototype.redraw = function () {
    console.log("redraw mobile: ", isMobileView());

    this.cardsElement.empty();

    var spoiler = this;

    this.resetSeed();

    // Presort the data so that random sort has predicatable initial state
    this.data.sort(function(a, b) { return a.name.localeCompare(b.name); });

    this.data.sort(function (a, b) {
        return spoiler.sortFunctions[spoiler.order](spoiler, a, b);
    });

    for (var i = 0; i < this.data.length; i++) {
        var card = this.data[i];

        var include = true;

        if (this.rarityFilter != null) {
            if (this.rarityFilter != card.rarity) {
                include = false;
            }
        }

        if (this.typeFilter != null) {
            if (card.types.indexOf(this.typeFilter) == -1 && card.types2.indexOf(this.typeFilter) == -1) {
                include = false;
            }
        }

        if (this.colorFilter != null) {
            if (this.colorFilter == "1") {
                if (card.colors.length > 0) include = false;
            }
            else if (this.colorFilter == "M") {
                if (card.colors.length < 2) include = false;
            } else {
                if (card.colors.indexOf(this.colorFilter) == -1) {
                    include = false;
                }
            }
        }
        
        if (include) {
        	if(this.mode == "spoiler" && !isMobileView()){
        		this.cardsElement.append(this.spoilerCardElements[card.cardId]);
        	}            
        	else {
        		this.cardsElement.append(this.imageCardElements[card.cardId]);
        	}
        }        
    }
    
    this.lazyload = $(".lazyload", this.mainElement).lazyload({
    	//effect : "fadeIn",
        skip_invisible : true
	});
    
    $(window).scroll();
    
    //if(this.lazyload) this.lazyload.update();
    //$(window).trigger('resize');
}