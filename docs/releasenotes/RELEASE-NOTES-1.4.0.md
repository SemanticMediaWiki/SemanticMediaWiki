# Semantic MediaWiki 1.4.0

See http://semantic-mediawiki.org/wiki/SMW_1.4.0

* Easier installation, upgrade, repair:
  Special:SMWAdmin now has a control for refreshing the wiki data online.
* Better Type:Date
  ** much larger range of dates, covering all of human history
  ** internationalisation, support for localised date formats in input
  ** support for single year numbers in dates
  ** incomplete dates handled properly
* Query for page modification date using Property:Modification_date
* Full integration of "special properties" such as Property:Has_type
  ** can be queried in #ask, and printed in printouts
  ** usable in all browsing interfaces
  ** display uses of properties on property page
* Improved parsing process
  ** avoid data loss in unusual circumstances
  ** much better compatibility with other parser extensions
* Shorter and cleaner code
* Extended translations
* Many bugfixes
