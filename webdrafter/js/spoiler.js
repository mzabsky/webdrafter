var Spoiler = function (element, data, imageUrl, localUrl){
    this.data = data;    
    this.mainElement = element;
    this.imageUrl = imageUrl;
    this.localUrl = localUrl;
    this.initializeStructure();
    this.initializeCardElements();
    this.initializeSorting();
    this.redraw();
};

Spoiler.prototype.initializeStructure = function () {
    this.mainElement.html(
        //"<div class='spoler-cards-no'>Number of cards: " + this.data.length + "</div>" +
        "<div class='spoiler-filters-and-sorts'>" +
        "   <div class='spoiler-mode'>" +
        "      Display: <a href='javascript:void(0)' onclick='spoiler.setMode(\"spoiler\")' mode='spoiler' class='activeSort'>Spoiler</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.setMode(\"images\")' mode='images'>Images</a>" +
        "   </div>" +
        "   <div class='spoiler-sort'>" +
        "      Sort by: <a href='javascript:void(0)' onclick='spoiler.sort(\"color\")' sort='color' class='activeSort'>Color</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.sort(\"rarity\")' sort='rarity'>Rarity</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.sort(\"cmc\")' sort='cmc'>CMC</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.sort(\"name\")' sort='name'>Name</a>" +
        "   </div>" +
        "   <div class='spoiler-filters spoiler-filters-rarity'>" +
        "      Rarity filter: <a href='javascript:void(0)' onclick='spoiler.filterByRarity(\"M\")' filter='M'>M</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByRarity(\"R\")' filter='R'>R</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByRarity(\"U\")' filter='U'>U</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByRarity(\"C\")' filter='C'>C</a>" +
        "   </div>" +
        "   <div class='spoiler-filters spoiler-filters-color'>" +
        "      Color filter: <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"W\")' filter='W'><span class='icon-wrapper'><i class='mtg white'></i></span></a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"U\")' filter='U'><span class='icon-wrapper'><i class='mtg blue'></i></span></a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"B\")' filter='B'><span class='icon-wrapper'><i class='mtg black'></i></span></a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"R\")' filter='R'><span class='icon-wrapper'><i class='mtg red'></i></span></a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"G\")' filter='G'><span class='icon-wrapper'><i class='mtg green'></i></span></a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"M\")' filter='M'><span class='icon-wrapper'><i class='mtg hybrid-wu'></i></span></a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByColor(\"1\")' filter='1'><span class='icon-wrapper'><i class='mtg mana-1'></i></span></a>" +
        "   </div>" +
        "   <div class='spoiler-filters spoiler-filters-type'>" +
        "      Type filter: <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Artifact\")' filter='Artifact'>Artifact</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Creature\")' filter='Creature'>Creature</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Enchantment\")' filter='Enchantment'>Enchantment</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Instant\")' filter='Instant'>Instant</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Land\")' filter='Land'>Land</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Planeswalker\")' filter='Planeswalker'>Planeswalker</a>" +
        "      <a href='javascript:void(0)' onclick='spoiler.filterByType(\"Sorcery\")' filter='Sorcery'>Sorcery</a>" +
        "   </div>" +
        "</div>" +
        "<hr class='card-separator' />" +
        "<div class='spoiler-cards'></div>"
    );

    this.cardsElement = $(".spoiler-cards", this.mainElement);
}

Spoiler.prototype.initializeCardElements = function () {
    this.imageCardElements = new Array();
    this.spoilerCardElements = new Array();
    
    this.mode = "spoiler";
    
    for (var i = 0; i < this.data.length; i++) {
        var card = this.data[i];

        this.imageCardElements[card.name] = $("" +
        	"<img src='" + card.artUrl + "' alt='" + card.name + "' class='card-image card-image-" + card.shape + "' />");
        
        this.spoilerCardElements[card.name] = $("" +
            "<table class='card-detail shape-" + card.shape + "'>" +
            "   <tr>" +
            "       <td class='card-image-day card-front' rowspan='15'><div style='background-image: url(\"" + card.artUrl + "\")'></td>" +
            (card.shape == "flip" || card.shape == "double" ? "<td class='card-image-night card-back' rowspan='15'><div style='background-image: url(\"" + card.artUrl + "\")'></td>" : "") +
            "       <td class='card-main-row card-front'><div class='card-name'><a href='" + card.url + "'>" + card.name + "</a></div>" + (card.manaCost != null ?"<div class='card-cost'>" + card.manaCost + "</div></td>" : "</td>")+            
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
            "   <tr>" +
            "       <td class='card-filler-row'><a href='javascript:void(0)' onclick='spoiler.copyAsBBCode(\"" + card.name + "\")'>Copy as forum code</a>" + (this.localUrl != null ? " <a href='javascript:void(0)' onclick='spoiler.copyAsBBLink(\"" + card.name + "\")'>Copy forum link</a> <a href='" + this.localUrl + "#" + card.name + "' onclick='spoiler.copyUrl(\"" + card.name + "\")'>Copy URL</a>" : "") + "</td>" +
            "   </tr>" +
            "</table><hr class='card-separator' />");
    }
}

Spoiler.prototype.initializeSorting = function () {
    this.order = "color";

    this.sortFunctions = {
        "color": function(spoiler, a, b) {
            var rate = function(x) {
                if (x.colors.length == 0 && x.types.indexOf("Artifact") == -1) return 7;
                else if (x.colors.length == 0 && x.types.indexOf("Artifact") != -1) return 6;
                else if (x.colors.length > 1) return 5;
                else if (x.colors[0] == 'G') return 4;
                else if (x.colors[0] == 'R') return 3;
                else if (x.colors[0] == 'B') return 2;
                else if (x.colors[0] == 'U') return 1;
                else if (x.colors[0] == 'W') return 0;
                else throw "Not reached";
            }

            var ratingA = rate(a);
            var ratingB = rate(b);

            if (ratingA == ratingB) return a.name.localeCompare(b.name);
            else return ratingA - ratingB;
        },
        "rarity": function (spoiler, a, b) {
            var rate = function (x) {
                if (x.rarity == 'C') return 4;
                else if (x.rarity == 'U') return 3;
                else if (x.rarity == 'R') return 2;
                else if (x.rarity == 'M') return 1;
                else throw "Not reached";
            }

            var ratingA = rate(a);
            var ratingB = rate(b);


            if (ratingA == ratingB) return spoiler.sortFunctions["color"](spoiler, a, b);
            else return ratingA - ratingB;
        },
        "cmc": function (spoiler, a, b) {
            var ratingA = a.cmc != null ? a.cmc : -1;
            var ratingB = b.cmc != null ? b.cmc : -1;

            if (ratingA == ratingB) return spoiler.sortFunctions["color"](spoiler, a, b);
            else return ratingB - ratingA;
        },
        "name": function (spoiler, a, b) {
            return a.name.localeCompare(b.name);
        }
    };

}

Spoiler.prototype.initializeFilters = function() {
    this.rarityFilter = null;
    this.typeFilter = null;
    this.colorFilter = null;
}

Spoiler.prototype.showCopyable = function (str) {
    $("<div class='spoiler-copyable-dialog'><textarea>" + str + "</textarea></div>").dialog({ modal: true, resizable: true, width: 800, height: 500, title: "Copy text" });

    $(".spoiler-copyable-dialog textarea").select();
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

Spoiler.prototype.copyUrl = function(cardName) {
    this.showCopyable(this.localUrl + "#" + cardName);
    return false;
};

Spoiler.prototype.copyAsBBCode = function (cardName) {
    var card = this.getCardByName(cardName);

    var rulesText = null;
    if (card.text != null) {
        rulesText = this.smartReplace(card.text, "<i>", "[i]");
        rulesText = this.smartReplace(rulesText, "</i>", "[/i]");
        rulesText = this.smartReplace(rulesText, "{", "[mana]");
        rulesText = this.smartReplace(rulesText, "}", "[/mana]");
        rulesText = this.smartReplace(rulesText, "<br />", "\n");
        rulesText = this.smartReplace(rulesText, "<br/>", "\n");
    }

    var flavorText = null;
    if (card.flavor != null) {
        flavorText = this.smartReplace(card.flavor, "<br />", "\n");
        flavorText = this.smartReplace(flavorText, "<br/>", "\n");
    }

    var backsideText = null;
    if (card.backside != null && card.backside.text) {
        backsideText = this.smartReplace(card.backside.text, "<i>", "[i]");
        backsideText = this.smartReplace(backsideText, "</i>", "[/i]");
        backsideText = this.smartReplace(backsideText, "{", "[mana]");
        backsideText = this.smartReplace(backsideText, "}", "[/mana]");
        backsideText = this.smartReplace(backsideText, "<br />", "\n");
        backsideText = this.smartReplace(backsideText, "<br/>", "\n");
    }

    var backsideFlavorText = null;
    if (card.backside != null && card.backside.flavor != null) {
        backsideFlavorText = this.smartReplace(card.backside.flavor, "<br />", "\n");
        backsideFlavorText = this.smartReplace(backsideFlavorText, "<br/>", "\n");
    }

    var bbCode = "[quote][b]" + (this.localUrl != null ? "[url=" + this.localUrl + "#" + cardName + "]" + cardName + "[/url]" : cardName ) + "[/b] " + (card.cost != null ? "[mana]" + this.smartReplace(this.smartReplace(card.cost, "{", ""), "}", "") + "[/mana]" : "") + "\n" +
        card.types + " (" + card.rarity + ")\n" +
        (card.text != null ? rulesText + "\n" : "") +
        (card.pt != null ? card.pt + "\n" : "") +
        (card.flavor != null ? "[i]" + flavorText + "[/i]\n" : "") +
        (card.backside != null ? "//\n" +
            "[b]" + card.backside.name + "[/b]\n" +
            card.backside.types + "\n" +
            (card.backside.text != null ? backsideText + "\n" : "") +
            (card.backside.pt != null ? card.backside.pt + "\n" : "") +
            (card.backside.flavor != null ? "[i]" + backsideFlavorText + "[/i]" : "") +
            "": "") +
        "[/quote]";

    bbCode = this.smartReplace(bbCode, "\n\\[/quote\\]", "[/quote]");
    bbCode = this.smartReplace(bbCode, "\n\n", "\n");
    bbCode = this.smartReplace(bbCode, "\\[/mana\\]\\[mana\\]", "");

    this.showCopyable(bbCode);
}

Spoiler.prototype.copyAsBBLink = function (cardName) {
    var bbCode = (this.localUrl != null ? "[url=" + this.localUrl + "#" + cardName + "]" + cardName + "[/url]" : cardName);
    this.showCopyable(bbCode);
}

Spoiler.prototype.redraw = function () {
    this.cardsElement.empty();

    var spoiler = this;
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
        	if(this.mode == "spoiler"){
        		this.cardsElement.append(this.spoilerCardElements[card.name]);
        	}            
        	else {
        		this.cardsElement.append(this.imageCardElements[card.name]);
        	}
        }        
    }
}