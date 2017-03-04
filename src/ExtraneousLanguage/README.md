`ExtraneousLanguage` provides "extraneous" language functions independent
of MediaWiki to support language options required by Semantic MediaWiki and its
registration system.

## JSON format

The location of the files is determined by the `smwgExtraneousLanguageFileDir` setting.

### Field definitions

* `@...` fields leading with `@` are identified as comments fields
* `fallbackLanguage`defines a fallback language tag
* `dataTypeLabels`
* `dataTypeAliases`
* `propertyLabels`
* `propertyAliases`
* `namespaces`
* `namespaceAliases`
* `dateFormatsByPrecision` format used in connection with a specific precision and includes:
  * `SMW_PREC_Y` Year
  * `SMW_PREC_YMD` Year, Month, and Day
  * `SMW_PREC_YMDT` Year, Month, Day, and Time
  * `SMW_PREC_YMDTZ` Year, Month, Day, Time and Timezone
* `dateFormats` to a define a rule set of how to resolve preferred date formats following:
  * `SMW_MDY` Month-Day-Year
  * `SMW_DMY` Day-Month-Year
  * `SMW_YMD` Year-Month-Day
  * `SMW_YDM` Year-Day-Month
  * `SMW_MY` Month-Year
  * `SMW_YM` Year-Month
  * `SMW_Y` Year
  * `SMW_YEAR` an entered digit can be a year
  * `SMW_DAY` an entered digit can be a day
  * `SMW_MONTH` an entered digit can be a month
  * `SMW_DAY_MONTH_YEAR` an entered digit can be a day, month or year
  * `SMW_DAY_YEAR` an entered digit can be either a day or a year
* `months` twelve strings naming the months and short strings briefly naming the month
* `days` follows ISO-8601 numeric representation, starting with Monday together with the corresponding short name

### Example

<pre>
{
	"fallbackLanguage": false,
	"dataTypeLabels":{
		"_wpg": "Page"
	},
	"dataTypeAliases":{
		"Page": "_wpg"
	},
	"propertyLabels":{
		"_TYPE": "Has type"
	},
	"propertyAliases": {
		"Has type": "_TYPE"
	},
	"namespaces":{
		"SMW_NS_PROPERTY": "Property"
	},
	"namespaceAliases": {
		"Property": "SMW_NS_PROPERTY"
	},
	"dateFormatsByPrecision": {
		"SMW_PREC_YMDTZ": "H:i:s T, j F Y"
	},
	"dateFormats": [
		[
			"SMW_Y"
		]
	],
	"months": [
		[
			"January",
			"Jan"
		]
	]
	"days":[
		[
			"Monday",
			"Mon"
		]
	]
}
</pre>

## Technical notes

* `ExtraneousLanguage` interface for the language functions
  * `LanguageContents` to provide the raw content from a corresponding language file
    * `JsonLanguageContentsFileReader` providing access to the contents of a `JSON` file
    * `LanguageFallbackFinder` is responsible for resolving a fallback language

### Related settings

* [`smwgHistoricTypeNamespace`](https://www.semantic-mediawiki.org/wiki/Help:$smwgHistoricTypeNamespace)
* [`smwgExtraneousLanguageFileDir`](https://www.semantic-mediawiki.org/wiki/Help:$smwgExtraneousLanguageFileDir)
