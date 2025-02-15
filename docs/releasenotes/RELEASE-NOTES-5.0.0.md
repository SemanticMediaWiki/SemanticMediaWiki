# Semantic MediaWiki 5.0.0

Released on TBD.

## Summary

This release mainly brings support for recent versions of MediaWiki and PHP.
Anyone using MediaWiki 1.41 or above or PHP 8.1 or above is recommended to upgrade.

## Compatibility

* Added support for MediaWiki 1.42
* Improved compatibility with MediaWiki 1.43
* Improved compatibility with PHP 8.1 and above
* Dropped support for MediaWiki older than 1.39
* Dropped support for PHP older than 8.0

For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Breaking changes

- [#6021](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6021) ChangePropagationDispatchJob: Don't presume job will be run on same server

  The param 'dataFile' and 'checkSum' have been dropped in ChangePropagationDispatchJob. No longer is a temp file created, instead the contents is supplied
  in the 'data' param.

## Upgrading

There is no need to run the "update.php" maintenance script or any of the rebuild data scripts.

## Contributors

* translatewiki.net
* Marko Ilic ([gesinn.it](https://gesinn.it))
* Sébastien Beyou
* Alexander Gesinn ([gesinn.it](https://gesinn.it))
* Jeroen De Dauw ([Professional Wiki](https://professional.wiki/))
* Karsten Hoffmeyer ([Professional Wiki](https://professional.wiki/))
* Robert Vogel
* Simon Stier
* Yvar
* alistair3149
* Alexander Mashin
* Ferdinand Bachmann
* Youri vd Bogert
* dependabot[bot]
* jaideraf
* thomas-topway-it
