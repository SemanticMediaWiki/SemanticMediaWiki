<?php
/**
 * @author Markus Krötzsch
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageEn extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => 'Editing help on properties',
	'smw_viewasrdf' => 'RDF feed',
	'smw_finallistconjunct' => ', and', //used in "A, B, and C"
	'smw_factbox_head' => 'Facts about $1',
	'smw_isspecprop' => 'This property is a special property in this wiki.',
	'smw_isknowntype' => 'This type is among the standard datatypes of this wiki.',
	'smw_isaliastype' => 'This type is an alias for the datatype “$1”.',
	'smw_isnotype' => 'This type “$1” is not a standard datatype in the wiki, and has not been given a user definition either.',
	// URIs that should not be used in objects in cases where users can provide URIs
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Sorry, URIs of the form “$1” are not allowed.',
	// Link to RSS feeds
	'smw_rss_link' => 'RSS',
	// Messages and strings for inline queries
	'smw_iq_disabled' => "Sorry. Semantic queries have been disabled for this wiki.",
	'smw_iq_moreresults' => '&hellip; further results',
	'smw_iq_nojs' => 'Please use a JavaScript-enabled browser to view this element.',
	'smw_iq_altresults' => 'Browse the result list directly.', // available link when JS is disabled
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => 'Import functions are not avalable for namespace “$1”.',
	'smw_nonright_importtype' => '$1 can only be used for pages with namespace “$2”.',
	'smw_wrong_importtype' => '$1 can not be used for pages in the namespace “$2”.',
	'smw_no_importelement' => 'Element “$1” not available for import.',
	// Messages and strings for basic datatype processing
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_notitle' => '“$1” cannot be used as a page name in this wiki.',
	'smw_unknowntype' => 'Unsupported type “$1” defined for property.',
	'smw_manytypes' => 'More than one type defined for property.',
	'smw_emptystring' => 'Empty strings are not accepted.',
	'smw_maxstring' => 'String representation $1 is too long for this site.',
	'smw_notinenum' => '“$1” is not in the list of possible values ($2) for this property.',
	'smw_noboolean' => '“$1” is not recognized as a Boolean (true/false) value.',
	'smw_true_words' => 'true,t,yes,y', // comma-separated synonyms for Boolean TRUE besides '1', primary value first
	'smw_false_words' => 'false,f,no,n', // comma-separated synonyms for Boolean FALSE besides '0', primary value first
	'smw_nofloat' => '“$1” is no number.',
	'smw_infinite' => 'Numbers as large as “$1” are not supported on this site.',
	'smw_infinite_unit' => 'Conversion into unit “$1” resulted in a number that is too large for this site.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'this property supports no unit conversion',
	'smw_unsupportedprefix' => 'Prefixes for numbers (“$1”) are not supported.',
	'smw_unsupportedunit' => 'Unit conversion for unit “$1” not supported.',
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => 'No number found before the symbol “$1”.', // $1 is something like °
	'smw_bad_latlong' => 'Latitude and longitude must be given only once, and with valid coordinates.',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'E',
	'smw_abb_south' => 'S',
	'smw_abb_west' => 'W',
	'smw_label_latitude' => 'Latitude:',
	'smw_label_longitude' => 'Longitude:',
	// some links for online maps; can be translated to different language versions of services, but need not
	'smw_service_online_maps' => " Find&nbsp;online&nbsp;maps|http://tools.wikimedia.de/~magnus/geo/geohack.php?params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	// Messages for datetime parsing
	'smw_nodatetime' => 'The date “$1” was not understood (support for dates is still experimental).',
	// Errors and notices related to queries
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
	// Messages for pages of types and properties
	'smw_type_header' => 'Properties of type “$1”',
	'smw_typearticlecount' => 'Showing $1 properties using this type.',
	'smw_attribute_header' => 'Pages using the property “$1”',
	'smw_attributearticlecount' => '<p>Showing $1 pages using this property.</p>',
	// Messages used in RSS feeds
	'smw_rss_description' => '$1 RSS feed',
	// Messages for Export RDF Special
	'exportrdf' => 'Export pages to RDF', //name of this special
	'smw_exportrdf_docu' => '<p>This page allows you to obtain data from a page in RDF format. To export pages, enter the titles in the text box below, one title per line.</p>',
	'smw_exportrdf_recursive' => 'Recursively export all related pages. Note that the result could be large!',
	'smw_exportrdf_backlinks' => 'Also export all pages that refer to the exported pages. Generates browsable RDF.',
	'smw_exportrdf_lastdate' => 'Do not export pages that were not changed since the given point in time.',
	// Messages for Properties Special
	'properties' => 'Properties',
	'smw_properties_docu' => 'The following properties are used in the wiki.',
	'smw_property_template' => '$1 of type $2 ($3)', // <propname> of type <type> (<count>)
	'smw_propertylackspage' => 'All properties should be described by a page!',
	'smw_propertylackstype' => 'No type was specified for this property (assuming type $1 for now).',
	'smw_propertyhardlyused' => 'This property is hardly used within the wiki!',
	// Messages for Unused Properties Special
	'unusedproperties' => 'Unused Properties',
	'smw_unusedproperties_docu' => 'The following properties exist although no other page makes use of them.',
	'smw_unusedproperty_template' => '$1 of type $2', // <propname> of type <type>
	// Messages for Wanted Properties Special
	'wantedproperties' => 'Wanted Properties',
	'smw_wantedproperties_docu' => 'The following properties are used in the wiki but do not yet have a page for describing them.',
	'smw_wantedproperty_template' => '$1 ($2 uses)', // <propname> (<count> uses)
	// Messages for the refresh button
	'tooltip-purge' => 'Click here to refresh all queries and templates on this page',
	'purge' => 'Refresh',
	// Messages for Import Ontology Special
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
	// Messages for (data)Types Special
	'types' => 'Types',
	'smw_types_docu' => 'The following is a list of all datatypes that can be assigned to properties. Each datatype has a page where additional information can be provided.',
	'smw_typeunits' => 'Units of measurement of type “$1”: $2',
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => 'Semantic Statistics',
	'smw_semstats_text' => 'This wiki contains <b>$1</b> property values for a total of <b>$2</b> different <a href="$3">properties</a>. <b>$4</b> properties have an own page, and the intended datatype is specified for <b>$5</b> of those. Some of the existing properties might by <a href="$6">unused properties</a>. Properties that still lack a page are found on the <a href="$7">list of wanted properties</a>.',
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Flawed Properties',
	'smw_fattributes' => 'The pages listed below have an incorrectly defined property. The number of incorrect properties is given in the brackets.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'URI Resolver',
	'smw_uri_doc' => '<p>The URI resolver implements the <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. It takes care that humans don\'t turn into websites.</p>',
	// Messages for ask Special
	'ask' => 'Semantic search',
	'smw_ask_doculink' => 'Semantic search',
	'smw_ask_sortby' => 'Sort by column (optional)',
	'smw_ask_ascorder' => 'Ascending',
	'smw_ask_descorder' => 'Descending',
	'smw_ask_submit' => 'Find results',
	'smw_ask_editquery' => '[Edit query]',
	'smw_ask_hidequery' => 'Hide query',
	'smw_ask_help' => 'Querying help',
	'smw_ask_queryhead' => 'Query',
	'smw_ask_printhead' => 'Additional printouts (optional)',
	// Messages for the search by property special
	'searchbyproperty' => 'Search by property',
	'smw_sbv_docu' => '<p>Search for all pages that have a given property and value.</p>',
	'smw_sbv_noproperty' => '<p>Please enter a property.</p>',
	'smw_sbv_novalue' => '<p>Please enter a valid value for the property, or view all property values for “$1.”</p>',
	'smw_sbv_displayresult' => 'A list of all pages that have property “$1” with value “$2”',
	'smw_sbv_property' => 'Property',
	'smw_sbv_value' => 'Value',
	'smw_sbv_submit' => 'Find results',
	// Messages for the browsing special
	'browse' => 'Browse wiki',
	'smw_browse_article' => 'Enter the name of the page to start browsing from.',
	'smw_browse_go' => 'Go',
	'smw_browse_more' => '&hellip;',
	// Messages for the page property special
	'pageproperty' => 'Page property search',
	'smw_pp_docu' => 'Search for all the fillers of a property on a given page. Please enter both a page and a property.',
	'smw_pp_from' => 'From page',
	'smw_pp_type' => 'Property',
	'smw_pp_submit' => 'Find results',
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'Previous',
	'smw_result_next' => 'Next',
	'smw_result_results' => 'Results',
	'smw_result_noresults' => 'Sorry, no results.'
);

protected $m_DatatypeLabels = array(
	'_wpg' => 'Page', // name of page datatype
	'_str' => 'String',  // name of the string type
	'_txt' => 'Text',  // name of the text type
	'_boo' => 'Boolean',  // name of the boolean type
	'_num' => 'Number',  // name for the datatype of numbers
	'_geo' => 'Geographic coordinate', // name of the geocoord type
	'_tem' => 'Temperature',  // name of the temperature type
	'_dat' => 'Date',  // name of the datetime (calendar) type
	'_ema' => 'Email',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'Annotation URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'         => '_uri',
	'Float'       => '_num',
	'Integer'     => '_num',
	'Enumeration' => '_str'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Has type',
	SMW_SP_HAS_URI   => 'Equivalent URI',
	SMW_SP_SUBPROPERTY_OF => 'Subproperty of',
	SMW_SP_DISPLAY_UNITS => 'Display units',
	SMW_SP_IMPORTED_FROM => 'Imported from',
	SMW_SP_CONVERSION_FACTOR => 'Corresponds to',
	SMW_SP_SERVICE_LINK => 'Provides service',
	SMW_SP_POSSIBLE_VALUE => 'Allows value'
);

protected $m_SpecialPropertyAliases = array(
	'Display unit' => SMW_SP_DISPLAY_UNITS
);

protected $m_Namespaces = array(
	SMW_NS_RELATION       => 'Relation',
	SMW_NS_RELATION_TALK  => 'Relation_talk',
	SMW_NS_PROPERTY       => 'Property',
	SMW_NS_PROPERTY_TALK  => 'Property_talk',
	SMW_NS_TYPE           => 'Type',
	SMW_NS_TYPE_TALK      => 'Type_talk'
);

}


