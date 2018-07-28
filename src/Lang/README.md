It provides "extraneous" language functions independent of MediaWiki that ate required by Semantic MediaWiki and its registration system.

## JSON format

The location of the content files is determined by the [`$smwgExtraneousLanguageFileDir`](https://www.semantic-mediawiki.org/wiki/Help:$smwgExtraneousLanguageFileDir) setting.

### Field definitions

- `fallback_language`defines a fallback language tag
- `datatype`
  - `labels` datatype labels
  - `aliases` datatype aliases
- `property`
  - `labels` predefined property labels
  - `aliases` predefined property aliases
- `namespace`
  - `labels` namespace names
  - `aliases` namespace aliases
- `date`
  - `format` to a define a rule set of how to resolve preferred date formats for dates with 1, 2, and 3 components. It is defined as an array where the constants define the order of the interpretation.
    - `SMW_MDY` Month-Day-Year
    - `SMW_DMY` Day-Month-Year
    - `SMW_YMD` Year-Month-Day
    - `SMW_YDM` Year-Day-Month
    - `SMW_MY` Month-Year
    - `SMW_YM` Year-Month
    - `SMW_Y` Year
    - `SMW_YEAR` an entered digit can be a year
    - `SMW_DAY` an entered digit can be a day
    - `SMW_MONTH` an entered digit can be a month
    - `SMW_DAY_MONTH_YEAR` an entered digit can be a day, month or year
    - `SMW_DAY_YEAR` an entered digit can be either a day or a year
  - `precision` used to define the rules of formatting for a specific precision:
    - `SMW_PREC_Y` Year
    - `SMW_PREC_YMD` Year, Month, and Day
    - `SMW_PREC_YMDT` Year, Month, Day, and Time
    - `SMW_PREC_YMDTZ` Year, Month, Day, Time and Timezone
  - `months` twelve strings naming the months and short strings briefly naming the month
  - `days` follows ISO-8601 numeric representation, starting with Monday together with the corresponding short name
- `@...` fields leading with `@` are identified as comment fields

### Example

<pre>
{
	"fallback_language": false,
	"datatype": {
		"labels":{
			"_wpg": "Page"
		},
		"aliases":{
			"Page": "_wpg"
		}
	},
	"property": {
		"labels":{
			"_TYPE": "Has type"
		},
		"aliases": {
			"Has type": "_TYPE"
		}
	},
	"namespaces": {
		"labels":{
			"SMW_NS_PROPERTY": "Property"
		},
		"aliases": {
			"Property": "SMW_NS_PROPERTY"
		}
	},
	"date":{
		"precision": {
			"SMW_PREC_YMDTZ": "H:i:s T, j F Y"
		},
		"format": [
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
}
</pre>

## Technical notes

<pre>
SMW\Lang
├─ Lang 			# interface to the language functions
├─ JsonContentsFileReader	# access the contents of a `JSON` file
└─ FallbackFinder		# resolving a fallback language
</pre>
