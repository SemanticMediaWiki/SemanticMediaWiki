<?php
/**
 * @author Markus Krötzsch
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageEn extends SMW_Language {

protected $smwContentMessages = array(
	'smw_edithelp' => 'Editing help on relations and attributes',
	'smw_helppage' => 'Relation',
	'smw_viewasrdf' => 'RDF feed',
	'smw_finallistconjunct' => ', and', //used in "A, B, and C"
	'smw_factbox_head' => 'Facts about $1',
	'smw_att_head' => 'Attribute values',
	'smw_rel_head' => 'Relations to other pages',
	'smw_spec_head' => 'Special properties',
	// URIs that should not be used in objects in cases where users can provide URIs
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Sorry, URIs from the range “$1” are not available in this place.',
	// Messages and strings for inline queries
	'smw_iq_disabled' => "<span class='smwwarning'>Sorry. Inline queries have been disabled for this wiki.</span>",
	'smw_iq_moreresults' => '&hellip; further results',
	'smw_iq_nojs' => 'Use a JavaScript-enabled browser to view this element, or directly <a href="$1">browse the result list</a>.',
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => 'Import functions are not avalable for namespace “$1”.',
	'smw_nonright_importtype' => '$1 can only be used for pages with namespace “$2”.',
	'smw_wrong_importtype' => '$1 can not be used for pages in the namespace “$2”.',
	'smw_no_importelement' => 'Element “$1” not available for import.',
	// Messages and strings for basic datatype processing
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_unknowntype' => 'Unsupported type “$1” defined for attribute.',
	'smw_noattribspecial' => 'Special property “$1” is not an attribute (use “::” instead of “:=”).',
	'smw_notype' => 'No type defined for attribute.',
	'smw_manytypes' => 'More than one type defined for attribute.',
	'smw_emptystring' => 'Empty strings are not accepted.',
	'smw_maxstring' => 'String representation $1 is too long for this site.',
	'smw_nopossiblevalues' => 'Possible values for this attribute are not enumerated.',
	'smw_notinenum' => '“$1” is not in the list of possible values ($2) for this attribute.',
	'smw_noboolean' => '“$1” is not recognized as a boolean (true/false) value.',
	'smw_true_words' => 't,yes,y',	// comma-separated synonyms for boolean TRUE besides 'true' and '1'
	'smw_false_words' => 'f,no,n',	// comma-separated synonyms for boolean FALSE besides 'false' and '0'
	'smw_nointeger' => '“$1” is no integer number.',
	'smw_nofloat' => '“$1” is no floating point number.',
	'smw_infinite' => 'Numbers as long as “$1” are not supported on this site.',
	'smw_infinite_unit' => 'Conversion into unit “$1” resulted in a number that is too long for this site.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'this attribute supports no unit conversion',
	'smw_unsupportedprefix' => 'Prefixes for numbers (“$1”) are not supported.',
	'smw_unsupportedunit' => 'Unit conversion for unit “$1” not supported.',
	// Messages for geo coordinates parsing
	'smw_err_latitude' => 'Values for latitude (N, S) must be within 0 and 90, and “$1” does not fulfill this condition.',
	'smw_err_longitude' => 'Values for longitude (E, W) must be within 0 and 180, and “$1” does not fulfill this condition.',
	'smw_err_noDirection' => 'Something is wrong with the given value “$1”.',
	'smw_err_parsingLatLong' => 'Something is wrong with the given value “$1” &ndash; we expect a value like “1°2′3.4′′ W” at this place.',
	'smw_err_wrongSyntax' => 'Something is wrong with the given value “$1” &ndash; we expect a value like “1°2′3.4′′ W, 5°6′7.8′′ N” at this place.',
	'smw_err_sepSyntax' => 'The given value “$1” seems to be right, but values for latitude and longitude should be seperated by “,” or “;”.',
	'smw_err_notBothGiven' => 'Please specify a valid value for both longitude (E, W) <it>and</it> latitude (N, S) &ndash; at least one is missing.',
	// additionals ...
	'smw_label_latitude' => 'Latitude:',
	'smw_label_longitude' => 'Longitude:',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'E',
	'smw_abb_south' => 'S',
	'smw_abb_west' => 'W',
	// some links for online maps; can be translated to different language versions of services, but need not
	'smw_service_online_maps' => " find&nbsp;maps|http://tools.wikimedia.de/~magnus/geo/geohack.php?params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	// Messages for datetime parsing
	'smw_nodatetime' => 'The date “$1” was not understood (support for dates is still experimental).'
);


protected $smwUserMessages = array(
	'smw_devel_warning' => 'This feature is currently under development, and might not be fully functional. Backup your data before using it.',
	// Messages for pages of types, relations, and attributes
	'smw_type_header' => 'Attributes of type “$1”',
	'smw_typearticlecount' => 'Showing $1 attributes using this type.',
	'smw_attribute_header' => 'Pages using the attribute “$1”',
	'smw_attributearticlecount' => '<p>Showing $1 pages using this attribute.</p>',
	'smw_relation_header' => 'Pages using the relation “$1”',
	'smw_relationarticlecount' => '<p>Showing $1 pages using this relation.</p>',
	// Messages for Export RDF Special
	'exportrdf' => 'Export pages to RDF', //name of this special
	'smw_exportrdf_docu' => '<p>This page allows you to obtain data from a page in RDF format. To export pages, enter the titles in the text box below, one title per line.</p>',
	'smw_exportrdf_recursive' => 'Recursively export all related pages. Note that the result could be large!',
	'smw_exportrdf_backlinks' => 'Also export all pages that refer to the exported pages. Generates browsable RDF.',
	'smw_exportrdf_lastdate' => 'Do not export pages that were not changed since the given point in time',
	// Messages for Search Triple Special
	'searchtriple' => 'Simple semantic search', //name of this special
	'smw_searchtriple_docu' => "<p>Fill in either the upper or lower row of the input form to search for relations or attributes, respectively. Some of the fields can be left empty to obtain more results. However, if an attribute value is given, the attribute name must be specified as well. As usual, attribute values can be entered with a unit of measurement.</p>\n\n<p>Be aware that you must press the right button to obtain results. Just pressing <i>Return</i> might not trigger the search you wanted.</p>",
	'smw_searchtriple_subject' => 'Subject page:',
	'smw_searchtriple_relation' => 'Relation name:',
	'smw_searchtriple_attribute' => 'Attribute name:',
	'smw_searchtriple_object' => 'Object page:',
	'smw_searchtriple_attvalue' => 'Attribute value:',
	'smw_searchtriple_searchrel' => 'Search Relations',
	'smw_searchtriple_searchatt' => 'Search Attributes',
	'smw_searchtriple_resultrel' => 'Search results (relations)',
	'smw_searchtriple_resultatt' => 'Search results (attributes)',
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
	'smw_types_docu' => 'The following is a list of all datatypes that can be assigned to attributes. Each datatype has a page where additional information can be provided.',
	'smw_types_units' => 'Standard unit: $1; supported units: $2',
	'smw_types_builtin' => 'Built-in types',
	/*Messages for ExtendedStatistics Special*/
	'extendedstatistics' => 'Extended Statistics',
	'smw_extstats_general' => 'General Statistics',
	'smw_extstats_totalp' => 'Total number of pages:',
	'smw_extstats_totalv' => 'Total number of views:',
	'smw_extstats_totalpe' => 'Total number of page edits:',
	'smw_extstats_totali' => 'Total number of images:',
	'smw_extstats_totalu' => 'Total number of users:',
	'smw_extstats_totalr' => 'Total number of relations:',
	'smw_extstats_totalri' => 'Total number of relation instances:',
	'smw_extstats_totalra' => 'Average number of instances per relation:',
	'smw_extstats_totalpr' => 'Total number of pages about relations:',
	'smw_extstats_totala' => 'Total number of attributes:',
	'smw_extstats_totalai' => 'Total number of attribute instances:',
	'smw_extstats_totalaa' => 'Average number of instances per attribute:',
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Flawed Attributes',
	'smw_fattributes' => 'The pages listed below have an incorrectly defined attribute. The number of incorrect attributes is given in the brackets.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'URI Resolver',
	'smw_uri_doc' => '<p>The URI resolver implements the <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. It takes care that humans don\'t turn into websites.</p>',
	// Messages for ask Special
	'ask' => 'Semantic search',
	'smw_ask_docu' => '<p>Search by entering a query into the search field below. Further information is given on the <a href="$1">help page for semantic search</a>.</p>',
	'smw_ask_doculink' => 'Semantic search',
	'smw_ask_sortby' => 'Sort by column',
	'smw_ask_ascorder' => 'Ascending',
	'smw_ask_descorder' => 'Descending',
	'smw_ask_submit' => 'Find results',
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

protected $smwDatatypeLabels = array(
	'smw_wikipage' => 'Page', // name of page datatype
	'smw_string' => 'String',  // name of the string type
	'smw_text' => 'Text',  // name of the text type
	'smw_enum' => 'Enumeration',  // name of the enum type
	'smw_bool' => 'Boolean',  // name of the boolean type
	'smw_int' => 'Integer',  // name of the int type
	'smw_float' => 'Float',  // name of the floating point type
	'smw_length' => 'Length',  // name of the length type
	'smw_area' => 'Area',  // name of the area type
	'smw_geolength' => 'Geographic length',  // OBSOLETE name of the geolength type
	'smw_geoarea' => 'Geographic area',  // OBSOLETE name of the geoarea type
	'smw_geocoordinate' => 'Geographic coordinate', // name of the geocoord type
	'smw_mass' => 'Mass',  // name of the mass type
	'smw_time' => 'Time',  // name of the time (duration) type
	'smw_temperature' => 'Temperature',  // name of the temperature type
	'smw_datetime' => 'Date',  // name of the datetime (calendar) type
	'smw_email' => 'Email',  // name of the email (URI) type
	'smw_url' => 'URL',  // name of the URL type (string datatype property)
	'smw_uri' => 'URI',  // name of the URI type (object property)
	'smw_annouri' => 'Annotation URI'  // name of the annotation URI type (annotation property)
);

protected $smwSpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Has type',
	SMW_SP_HAS_URI   => 'Equivalent URI',
	SMW_SP_SUBPROPERTY_OF => 'Subproperty of',
	SMW_SP_MAIN_DISPLAY_UNIT => 'Main display unit',
	SMW_SP_DISPLAY_UNIT => 'Display unit',
	SMW_SP_IMPORTED_FROM => 'Imported from',
	SMW_SP_CONVERSION_FACTOR => 'Corresponds to',
	SMW_SP_CONVERSION_FACTOR_SI => 'Corresponds to SI',
	SMW_SP_SERVICE_LINK => 'Provides service',
	SMW_SP_POSSIBLE_VALUE => 'Allows value'
);

	/**
	 * Function that returns the namespace identifiers.
	 */
	public function getNamespaceArray() {
		return array(
			SMW_NS_RELATION       => 'Relation',
			SMW_NS_RELATION_TALK  => 'Relation_talk',
			SMW_NS_PROPERTY       => 'Property',
			SMW_NS_PROPERTY_TALK  => 'Property_talk',
			SMW_NS_TYPE           => 'Type',
			SMW_NS_TYPE_TALK      => 'Type_talk'
		);
	}
}


