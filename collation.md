# Pack collation
## Custom collation
A custom file containing pre-collated packs called packs.txt can be uploaded along with the card images and the primary set file. This file has to follow these rules:
- The text file contains  one card name per line. Packs are separated by a line containing exactly "===========". So packs for a normal set would be a looping sequence of 14 card names, then a line =========== and then more card names.
- The card names always exactly match the card names in the set file. Any extra whitespace from the end of each line will be discarded (regardless of whether the set contains cards which end with whitespace).
- The card names only refer to the primary face of the card, for any multi-face card variants. This includes split cards, flip cards, DFCs etc.
- The file uses UTF8 encoding without BOM.
- All packs within the file must be the same single size, between 1 and 15.
- The file may contain up to 1000 such packs.
- The packs may contain any cards found in the set file, including tokens, basic lands and such.
- The collation may or may not use up all the cards in the set.
- The packs may contain repeated cards.

When packs of a set using custom collation are requested, they will be randomly drawn from the packs specified in the file. Sets using regular built-in collation and those using pre-collated packs can be used together in a single event (chaos draft doesn't work though, the default collation algorithm will be used there).