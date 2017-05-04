# PlaneSculptors card file format
Any fields that are not recognized are ignored by the set importer.

The card file is a JSON file containing an array of card objects:
```
[
	{ 'name': 'Card One', ... },
    { 'name': 'Card Two', ... },
    ...
]
```
The file is encoded in UTF8. All fields are case sensitive.

The file must contain no more than 1000 cards.

## Card object
The card object has following fields (M = "is mandatory"):

Field | Type | Example | M | Description 
------|------|---------|---|---------
layout | string | `'normal'` | N |  Only 'normal' is supported, for now. If ommitted, the layout is considered to be `'normal'`.
name | string | `'Aven Harrier'` | Y | Actual name of the card. Any special characters are considered as they are, italics and other HTML tags are not evaluated. Must be unique within the file. Must be non-empty. Must be no longer than 30 characters. Must not contain any unprintable characters other than simple space. Must not start or end with whitespace. 
names | string[] | `[]` | N | Reserved for DFCs.
manaCost | string | `'{2}{G}'` | N | Sequence of symbols representing the mana cost, as printed in upper right corner of the card (see below for more information on symbols). Must not contain any other character. Must be no longer than 60 characters.
cmc | int | 3 | N | Converted mana cost. Should be consistent with the `manaCost` field. Must be greater than 0 and less than or equal to 65535. If ommitted, it is considered 0.
colors | string[] | `['White', 'Red']` | N | A list of colors of the card (as considered by the game rules - should reflect mana cost, color indicators and abilities such as Devoid). The color values can be either full name of the color, starting with capital letter (`'White'`) or just a capital letter representing the color (`'W'`). No other colors than the five basic colors of magic are allowed. No color is allowed to repeat (so the array may not have more than five items). If ommitted, the card is considered colorless.
colorIdentity | string[] | `['White', 'Red']` | N | A list of colors of in the color identity of the card. Validations are the same as for the `colors` field. If ommitted, the color identity of the card is considered to be colorless.
type | string | `'Legendary creature — Human Warrior'` | Y | Full type line of the card, including supertypes and subtypes. The dash character ("—") should be used as a separator between card type and subtypes, if applicable. Must be non-empty. Must be no longer than 60 characters. Must not contain any unprintable characters other than simple space. Must not start or end with whitespace.
rarity | string | `'Mythic Rare'` | Y | Rarity of the card. Must be one of `'C'`, `'U'`, `'R'`, `'M'` or `'Common'`, `'Uncommon'`, `'Rare'`, `'Mythic Rare'`.
text | string | '{T}: Add {G} to your mana pool.' | N | Rules text of the card. May contain Magic symbols (see below) and HTML tags `'<strong>'`, `'<b>'`, `'<i>'` and `'<em>'` (no other tags are allowed, the tags are not allowed to have any attributes, all opened tags must be closed). Must be no longer than 1000 characters.
flavor | string | `“I never let reality dictate my potential.”` | 'N | Rules text of the card. May contain HTML tags `'<strong>'`, `'<b>'`, `'<i>'` and `'<em>'` (no other tags are allowed, the tags are not allowed to have any attributes, all opened tags must be closed). The meaning of italics tags (`em` and `i`) is inverted (entirety of the flavor text is considered italics, explicitly italicized are considered not italicized). Symbols in flavor text are not evaluated. Must be no longer than 1000 characters.
artist | string | `'Cris Yang'` | N | Artist name for the card. Any special characters are considered as they are, italics and other HTML tags are not evaluated. Must be no longer than 30 characters. Must not contain any unprintable characters other than simple space. Must not start or end with whitespace.
number | string or int | `'123b'` | Y | Number of the card. Must be a number that optionally ends with `a` or `b`. The number must be greater than 0 and less than or equal to 1000.
power | string or int | `'*'` | N | Power as printed on the card. Can be a number or a short expression. Must be no longer than 4 characters.
toughness | string or int | `'*'` | N | Toughness as printed on the card. Can be a number or a short expression. Must be no longer than 4 characters.
loyalty | string or int | `5` | N | Loyalty as printed on the card. Can be a number or a short expression. Must be no longer than 4 characters.
imageName | string | `'Aven Harrier.jpg'` | Y | Name of the file containing the image for the card, as uploaded along with the card file. Must be no longer than 40 characters.

## Magic symbols
Following symbols are supported:

Symbol | Example | Additional info
-------|---------|-----------------
Colored mana | {W} | Supported for all 5 colors.
Generic mana | {6} | Supported for numerals 0-20, and letters X, Y and Z.
Hybrid mana | {W/U} | Supported for all pairs of the 5 colors, in both directions.
Twobrid mana | {2/W} | Supported for all 5 colors.
Colored phyrexian mana | {H/W} | Supported for all 5 colors.
Colorless phyrexian mana | {H} |
Tap | {T} | 
Untap | {Q} |
Snow | {S} |
Colorless mana | {C} |

No other symbols are allowed.

