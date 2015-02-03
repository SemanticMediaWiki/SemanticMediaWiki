# Semantic MediaWiki 2.2

This is not a release yet.

## New features

* #770 Added `--no-cache`/`--debug` option to the `rebuildData.php` script (refs #749, #766)
* #756 Added template support for the `#set` parser

## Bug fixes

* 

## Internal changes
* #373 Update `jquery.jstorage.js` (0.3.2 => 0.4.11)
* #494 Changes to the `SQLStore\QueryEngine` interface
* #711 Fetching annotations made by an `#ask` transcluded template 
* #725 Moved psr-4 complaint classes into the top level 'src' folder
* #740 Added `serialization/serialization:~3.2` component dependency
* #771 Added `doctrine/dbal:~2.5` component dependency
* #772 Added `onoi/message-reporter:~1.0` component dependency
* #777 Moved all concept related code into a separate `ConceptCache` class
