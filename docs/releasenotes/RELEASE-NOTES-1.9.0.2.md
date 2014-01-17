# Semantic MediaWiki 1.9.0.2

Released January 17th, 2014.

### Bug fixes

* #85 Fixed compatibility issue with PHP 5.3 on Special:SMWAdmin and added regression test
* #86 Fixed compatibility with older MediaWiki versions by supporting legacy job definitions
* #89 The resource paths will now be correct event if SMW is put on a non-standard location
* #97 Fixed strict standards notice in the SQLStore
* #99 Fixed issue occurring in the SQLStore for people using mysqli and MediaWiki 1.22 or later