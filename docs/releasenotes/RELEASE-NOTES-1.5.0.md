# Semantic MediaWiki 1.5.0

Released on March 7, 2010.

* The former multi-valued/n-ary properties are now supported by a dedicated
  datatype Record. Wikis that use the old style of these properties need to
  update affected property pages as described at
  http://semantic-mediawiki.org/wiki/SMW_1.5.0
* The Type:Geographic_coordinates has been moved from SMW to the SemanticMaps
  extension. Wikis that use this datatype need to install this extension to get
  it back.
* The "like" comparator ~ in inline queries is now enabled by default. To use
  the former setting, use $smwgQComparators = '<|>|!'; in your LocalSettings.php.
* A new datatype, "Telephone number", has been introduced for validating phone
  numbers based on RFC 3966. Only global phone numbers are accepted, and no
  vanity numbers (those containing letters) are allowed. Use Type:String if
  you want to store more general strings as telephone numbers.
* Support for additional parameters for ?printouts in inline queries, specified
  by |+parameter=value after the printout. Currently supported are
  *  limit: set the maximum number of values for this printout
  *	 order: order values ascending or descending
  *  align: right/center/left alignment for printouts in tables
  *  index: directly address one value of a multi-valued (n-ary) property
* Support for inverse properties: use "-" in front of property names anywhere to
  refer to the inverse direction of a property. Works in browsing interfaces,
  queries, and query output directives. In queries only for properties of Type:Page.
* New configuration options $smwgUseCategoryHierarchy and $smwgCategoriesAsInstances
  to configure how MediaWiki categories should be interpreted by SMW.
* Compatibility with new MediaWiki skin "Vector" and with MediaWiki 1.16.
* Removed support for backwards compatibility to SMW <1.0 ($smwgSMWBetaCompatible).
* The page Special:Ask was overhauled to include inputs for entering values
  for the selected format's parameters, and to use Ajax where possible
* To that end, each format's query printer now includes a getParameters()
  function that supplies the name and attributes of each the format's parameters
* "before" and "after" hooks for updateData() and deleteSubject() were added
* A hook was added for deleteSemanticData() as well
