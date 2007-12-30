<?php
/**
 * @author Udi Oron אודי אורון
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageHe extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => 'עזרה בנושא עריכת יחסים ותכונות',
	'smw_viewasrdf' => 'RDF feed',
	'smw_finallistconjunct' => ', וגם', //used in "A, B, and C"
	'smw_factbox_head' => 'עובדות על אודות $1 &mdash; לחץ <span class="smwsearchicon">+</span> בכדי למצוא דפים דומים.',
	'smw_isspecprop' => 'This property is a special property in this wiki.', // TODO Translate
	'smw_isknowntype' => 'This type is among the standard datatypes of this wiki.', // TODO Translate
	'smw_isaliastype' => 'This type is an alias for the datatype “$1”.', // TODO Translate
	'smw_isnotype' => 'This type “$1” is not a standard datatype in the wiki, and has not been given a user definition either.', // TODO Translate
	/*URIs that should not be used in objects in cases where users can provide URIs */
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Sorry, URIs of the form "$1" are not allowed.',
	// Link to RSS feeds
	'smw_rss_link' => 'RSS', // TODO: translate (default text for linking to semantic RSS feeds)
	/*Messages and strings for inline queries*/
	'smw_iq_disabled' => "Sorry. Semantic queries have been disabled for this wiki.", // TODO: translate
	'smw_iq_moreresults' => '&hellip; תוצאות נוספות',
	'smw_iq_nojs' => 'Use a JavaScript-enabled browser to view this element.', //TODO: translate
	'smw_iq_altresults' => 'Browse the result list directly.', // available link when JS is disabled // TODO: translate
	/*Messages and strings for ontology resued (import) */
	'smw_unknown_importns' => '[Sorry, import functions are not avalable for namespace "$1".]', // TODO: translate
	'smw_nonright_importtype' => '[Sorry, $1 can only be used for pages with namespace "$2"]',
	'smw_wrong_importtype' => '[Sorry, $1 can not be used for pages in the namespace "$2"]',
	'smw_no_importelement' => '[Sorry, element "$1" not available for import.]',
	/*Messages and strings for basic datatype processing*/
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_notitle' => '“$1” cannot be used as a page name in this wiki.', // TODO Translate
	'smw_unknowntype' => '[אופס! טיפוס לא מוכר "$1" הוגדר עבור תכונה זו]',
	'smw_manytypes' => '[אופס! הוגדר יותר מטיפוס אחד לתכונה זו]',
	'smw_emptystring' => '[אופס! לא ניתן להשתמש כאן במחרוזות ריקות]',
	'smw_maxstring' => '[מצטערת, ייצוג המחרוזת כ-$1 ארוך מדי עבור אתר זה.]',
	'smw_notinenum' => '[אופס! "$1" לא נמצא בערכים האפשריים ($2) לתכונה זו]',
	'smw_noboolean' => '[אופס! "$1" אינה תכונה מטיפוס נכון-לאנכון]',
	'smw_true_words' => 't,yes,y,כן,נכון,אמת,חיובי,כ',	// comma-separated synonyms for boolean TRUE besides 'true' and '1' TODO: "true" needs to be added now, and main synonym should be first
	'smw_false_words' => 'f,no,n,לא,לא נכון,לא-נכון,שקר,שלישי,ל',	// comma-separated synonyms for boolean FALSE besides 'false' and '0' TODO: "false" needs to be added now, and main synonym should be first
	'smw_nofloat' => '[אופס! "$1" אינו מספר מטיפוס נקודה צפה]', // TODO Change "floating-point" number to just "number"
	'smw_infinite' => '[מצטרת, $1 הוא מספר גדול מדי לאתר זה .]',
	'smw_infinite_unit' => '[מצטערת, תוצאת ההמרה ליחידה $1 היא מספר גדול מדי לאתר זה.]',
	//'smw_unexpectedunit' => 'תכונה זו אינה תומכת בהמרה מטיפוס לטיפוס',
	'smw_unsupportedprefix' => 'Prefixes for numbers (“$1”) are not supported.', // TODO translate
	'smw_unsupportedunit' => 'אין תמיכה להמרת יחידות לטיפוס "$1"',
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => 'No number found before the symbol “$1”.', // $1 is something like ° TODO Translate
	'smw_bad_latlong' => 'Latitude and longitude must be given only once, and with valid coordinates.', // TODO Translate
	'smw_label_latitude' => 'קו רוחב:',
	'smw_label_longitude' => 'קו אורך:',
	'smw_abb_north' => 'צפון',
	'smw_abb_east' => 'מזרח',
	'smw_abb_south' => 'דרום',
	'smw_abb_west' => 'מערב',
	/* some links for online maps; can be translated to different language versions of services, but need not*/
	'smw_service_online_maps' => " חפש&nbsp;מפות|http://tools.wikimedia.de/~magnus/geo/geohack.php?language=he&params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	/*Messages for datetime parsing */
	'smw_nodatetime' => '[אופס! התאריך "$1" אינו מובן. מצד שני התמיכה בתאריכים היא עדיין ניסיונית.]',
	// Errors and notices related to queries // TODO: translate
	'smw_toomanyclosing' => 'There appear to be too many occurrences of “$1” in the query.',
	'smw_noclosingbrackets' => 'Some use of “[&#x005B;” in your query was not closed by a matching “]]”.',
	'smw_misplacedsymbol' => 'The symbol “$1” was used in a place where it is not useful.',
	'smw_unexpectedpart' => 'The part “$1” of the query was not understood. Results might not be as expected.',
	'smw_emptysubquery' => 'Some subquery has no valid condition.',
	'smw_misplacedsubquery' => 'Some subquery was used in a place where no subqueries are allowed.',
	'smw_valuesubquery' => 'Subqueries not supported for values of property “$1”.',
	'smw_overprintoutlimit' => 'The query contains too many printout requests.',
	'smw_badprintout' => 'Some print statement in the query was misshaped.',
	'smw_badtitle' => 'Sorry, but “$1” is no valid page title.',
	'smw_badqueryatom' => 'Some part “[&#x005B;&hellip;]]” of the query was not understood.',
	'smw_propvalueproblem' => 'The value of property “$1” was not understood.',
	'smw_nodisjunctions' => 'Disjunctions in queries are not supported in this wiki and part of the query was dropped ($1).',
	'smw_querytoolarge' => 'The following query conditions could not be considered due to the wikis restrictions in query size or depth: $1.'
);


protected $m_UserMessages = array(
	'smw_devel_warning' => 'This feature is currently under development, and might not be fully functional. Backup your data before using it.',
	// Messages for article pages of types, relations, and attributes
	'smw_type_header' => 'Attributes of type “$1”', // TODO translate
	'smw_typearticlecount' => 'Showing $1 attributes using this type.', // TODO translate
	'smw_attribute_header' => 'Pages using the attribute “$1”', // TODO translate
	'smw_attributearticlecount' => '<p>Showing $1 pages using this attribute.</p>', // TODO translate
	// Messages used in RSS feeds
	'smw_rss_description' => '$1 RSS feed', // TODO: translate, used as default semantic RSS-feed description
	/*Messages for Export RDF Special*/ // TODO: translate
	'exportrdf' => 'Export pages to RDF', //name of this special
	'smw_exportrdf_docu' => '<p>This page allows you to obtain data from a page in RDF format. To export pages, enter the titles in the text box below, one title per line.</p>',
	'smw_exportrdf_recursive' => 'Recursively export all related pages. Note that the result could be large!',
	'smw_exportrdf_backlinks' => 'Also export all pages that refer to the exported pages. Generates browsable RDF.',
	'smw_exportrdf_lastdate' => 'Do not export pages that were not changed since the given point in time.', // TODO: translate
	// Messages for Properties Special
	'properties' => 'Properties', //TODO: translate
	'smw_properties_docu' => 'The following properties are used in the wiki.', //TODO: translate
	'smw_property_template' => '$1 of type $2 ($3)', // <propname> of type <type> (<count>) //TODO: translate
	'smw_propertylackspage' => 'All properties should be described by a page!', //TODO: translate
	'smw_propertylackstype' => 'No type was specified for this property (assuming type $1 for now).', //TODO: translate
	'smw_propertyhardlyused' => 'This property is hardly used within the wiki!', //TODO: translate
	// Messages for Unused Properties Special
	'unusedproperties' => 'Unused Properties', //TODO: translate
	'smw_unusedproperties_docu' => 'The following properties exist although no other page makes use of them.', //TODO: translate
	'smw_unusedproperty_template' => '$1 of type $2', // <propname> of type <type> //TODO: translate
	// Messages for Wanted Properties Special
	'wantedproperties' => 'Wanted Properties', //TODO: translate
	'smw_wantedproperties_docu' => 'The following properties are used in the wiki but do not yet have a page for describing them.', //TODO: translate
	'smw_wantedproperty_template' => '$1 ($2 uses)', // <propname> (<count> uses) //TODO: translate
//// Note to translators:
//// The following messages in comments were kept for reference to facilitate the translation of the property messages above.
//// Delete them when no longer needed.
// 	/*Messages for Relations Special*/
// 	'relations' => 'יחסים',
// 	'smw_relations_docu' => 'היחסים הבאים מופיעים באתר.',
// 	// Messages for WantedRelations Special
// 	'wantedrelations' => 'Wanted relations', //TODO: translate
// 	'smw_wanted_relations' => 'The following relations do not have an explanatory page yet, though they are already used to describe other pages.', //TODO: translate
// 	/*Messages for Attributes Special*/
// 	'attributes' => 'תכונות',
// 	'smw_attributes_docu' => 'התכונות הבאות קיימים באתר.',
// 	'smw_attr_type_join' => ' עם $1',
// 	/*Messages for Unused Relations Special*/
// 	'unusedrelations' => 'יחסים שאינם בשימוש',
// 	'smw_unusedrelations_docu' => 'היחסים הבאים מוגדרים באתר אך לא נעשה בהם כל שימוש.',
// 	/*Messages for Unused Attributes Special*/
// 	'unusedattributes' => 'תכונות שאינן בשימוש',
// 	'smw_unusedattributes_docu' => 'התכונות הבאות מוגדרים במערכת אך לא נעשה בהם שימוש.',
	/* Messages for the refresh button */
	'tooltip-purge' => 'לחץ כאן הכדי לרענן את כל התבניות והשאילתות בדף זה',
	'purge' => 'רענן תבניות ושאילתות',
	/*Messages for Import Ontology Special*/
	'ontologyimport' => 'Import ontology',
	'smw_oi_docu' => 'This special page allows to import ontologies. The ontologies have to follow a certain format, specified at the <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">ontology import help page</a>.',
	'smw_oi_action' => 'Import',
	'smw_oi_return' => 'Return to <a href="$1">Special:OntologyImport</a>.',
	'smw_oi_noontology' => 'No ontology supplied, or could not load ontology.',
	'smw_oi_select' => 'Please select the statements to import, and then click the import button.',
	'smw_oi_textforall' => 'Header text to add to all imports (may be empty):',
	'smw_oi_selectall' => 'Select or unselect all statements',
	'smw_oi_statementsabout' => 'Statements about',
	'smw_oi_mapto' => 'Map entity to',
	'smw_oi_comment' => 'Add the following text:',
	'smw_oi_thisissubcategoryof' => 'A subcategory of',
	'smw_oi_thishascategory' => 'Is part of',
	'smw_oi_importedfromontology' => 'Import from ontology',
	/*Messages for (data)Types Special*/
	'types' => 'טיפוסים',
	'smw_types_docu' => 'ברשימה זו מופיעים כל טיפוסי המידע שתכונות יכולות להשתמש בהם . לכל טיפוס מידע יש דף המסביר על אודותיו.',
	'smw_typeunits' => 'Units of measurement of type “$1”: $2', // TODO: Translate
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => 'Semantic Statistics', // TODO: translate
	'smw_semstats_text' => 'This wiki contains <b>$1</b> property values for a total of <b>$2</b> different <a href="$3">properties</a>. <b>$4</b> properties have an own page, and the intended datatype is specified for <b>$5</b> of those. Some of the existing properties might by <a href="$6">unused properties</a>. Properties that still lack a page are found on the <a href="$7">list of wanted properties</a>.', // TODO: translate
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Flawed Attributes',
	'smw_fattributes' => 'The pages listed below have an incorrectly defined attribute. The number of incorrect attributes is given in the brackets.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'URI Resolver', //TODO: translate
	'smw_uri_doc' => '<p>The URI resolver implements the <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. It takes care that humans don\'t turn into websites.</p>', //TODO: translate
	/*Messages for ask Special*/
	'ask' => 'חיפוש סמנטי',
	'smw_ask_doculink' => 'חיפוש סמנטי',
	'smw_ask_sortby' => 'מיין לפי טור',
	'smw_ask_ascorder' => 'בסדר עולה',
	'smw_ask_descorder' => 'בסדר יורד',
	'smw_ask_submit' => 'חפש תוצאות',
	'smw_ask_editquery' => '[Edit query]', // TODO: translate
	'smw_ask_hidequery' => 'Hide query', // TODO: translate
	'smw_ask_help' => 'Querying help', // TODO: translate
	'smw_ask_queryhead' => 'Query', // TODO: translate
	'smw_ask_printhead' => 'Additional printouts (optional)', // TODO: translate
	// Messages for the search by property special TODO: translate
	'searchbyproperty' => 'Search by property', //TODO: translate
	'smw_sbv_docu' => '<p>Search for all pages that have a given property and value.</p>', //TODO: translate
	'smw_sbv_noproperty' => '<p>Please enter a property.</p>', //TODO: translate
	'smw_sbv_novalue' => '<p>Please enter a valid value for the property, or view all property values for “$1.”</p>', //TODO: translate
	'smw_sbv_displayresult' => 'A list of all pages that have property “$1” with value “$2”', //TODO: translate
	'smw_sbv_property' => 'Property', //TODO: translate
	'smw_sbv_value' => 'Value', //TODO: translate
	'smw_sbv_submit' => 'Find results', //TODO: translate
	// Messages for the browsing system
	'browse' => 'Browse wiki', //TODO: translate
	'smw_browse_article' => 'Enter the name of the page to start browsing from.', //TODO: translate
	'smw_browse_go' => 'Go', //TODO: translate
	'smw_browse_more' => '&hellip;', //TODO: translate
	// Messages for the page property special
	'pageproperty' => 'Page property search', // TODO: translate
	'smw_pp_docu' => 'Search for all the fillers of a property on a given page. Please enter both a page and a property.', // TODO: translate
	'smw_pp_from' => 'From page', // TODO: translate
	'smw_pp_type' => 'Property', // TODO: translate
	'smw_pp_submit' => 'Find results', // TODO: translate
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'הקודם',
	'smw_result_next' => 'הבא',
	'smw_result_results' => 'תוצאות',
	'smw_result_noresults' => 'מצטערת, אין תוצאות'
);

protected $m_DatatypeLabels = array(
	'_wpg' => 'Page', // name of page datatype  //TODO translate
	'_str' => 'מחרוזת',  // name of the string type
	'_txt' => 'Text',  // name of the text type (very long strings) //TODO: translate
	'_boo' => 'נכוןלאנכון',  // name of the boolean type
	'_num' => 'Number', // name for the datatype of numbers //TODO: translate
	'_geo' => 'קורדינטות גיאוגרפיות', // name of the geocoord type
	'_tem' => 'טמפרטורה',  // name of the temperature type
	'_dat' => 'תאריך',  // name of the datetime (calendar) type
	'_ema' => 'דואל',  // name of the email (URI) type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'Annotation URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'מזהה יחודי'
	             => '_uri',
	'שלם'
	             => '_num',
	'נקודהצפה'
	             => '_num',
	// support English aliases:
	'Page'                  => '_wpg',
	'String'                => '_str',
	'Text'                  => '_txt',
	'Boolean'               => '_boo',
	'Number'                => '_num',
	'Geographic coordinate' => '_geo',
	'Temperature'           => '_tem',
	'Date'                  => '_dat',
	'Email'                 => '_ema',
	'URI'                   => '_uri',
	'Annotation URI'        => '_anu'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'מטיפוס',
	SMW_SP_HAS_URI   => 'מזהה יחודי תואם',
	SMW_SP_SUBPROPERTY_OF => 'Subproperty of', // TODO: translate
	SMW_SP_DISPLAY_UNITS => 'יחידת הצגה', // TODO: should be plural now ("units"), singluar stays alias//
	SMW_SP_IMPORTED_FROM => 'יובא מ',
	SMW_SP_CONVERSION_FACTOR => 'מתורגם ל',
	SMW_SP_SERVICE_LINK => 'מספק שירות',
	SMW_SP_POSSIBLE_VALUE => 'ערכים אפשריים' //   TODO: check translation, should be singular value//
);

protected $m_SpecialPropertyAliases = array(
	'יחידת הצגה'
	                    => SMW_SP_DISPLAY_UNITS,
	// support English aliases for special properties
	'Has type'          => SMW_SP_HAS_TYPE,
	'Equivalent URI'    => SMW_SP_HAS_URI,
	'Subproperty of'    => SMW_SP_SUBPROPERTY_OF,
	'Display units'     => SMW_SP_DISPLAY_UNITS,
	'Imported from'     => SMW_SP_IMPORTED_FROM,
	'Corresponds to'    => SMW_SP_CONVERSION_FACTOR,
	'Provides service'  => SMW_SP_SERVICE_LINK,
	'Allows value'      => SMW_SP_POSSIBLE_VALUE
);


protected $m_Namespaces = array(
	SMW_NS_RELATION       => 'יחס',
	SMW_NS_RELATION_TALK  => 'שיחת_יחס',
	SMW_NS_PROPERTY       => 'תכונה',
	SMW_NS_PROPERTY_TALK  => 'שיחת_תכונה',
	SMW_NS_TYPE           => 'טיפוס',
	SMW_NS_TYPE_TALK      => 'שיחת_טיפוס'
);


protected $m_NamespaceAliases = array(
	// support English aliases for namespaces
	'Relation'      => SMW_NS_RELATION,
	'Relation_talk' => SMW_NS_RELATION_TALK,
	'Property'      => SMW_NS_PROPERTY,
	'Property_talk' => SMW_NS_PROPERTY_TALK,
	'Type'          => SMW_NS_TYPE,
	'Type_talk'     => SMW_NS_TYPE_TALK
);

}
