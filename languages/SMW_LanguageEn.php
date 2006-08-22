<?php
/**
 * @author Markus Krötzsch
 */

class SMW_LanguageEn {

/* private */ var $smwContentMessages = array(
	'smw_edithelp' => 'Editing help on relations and attributes',
	'smw_helppage' => 'Relation',
	'smw_viewasrdf' => 'View as RDF',
	'smw_finallistconjunct' => ', and', //used in "A, B, and C"
	'smw_factbox_head' => 'Facts about $1 &mdash; Click <span class="smwsearchicon">+</span> to find similar pages.',
	'smw_att_head' => 'Attribute values',
	'smw_rel_head' => 'Relations to other articles',
	'smw_spec_head' => 'Special properties',
	/*URIs that should not be used in objects in cases where users can provide URIs */
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Sorry, URIs from the range "$1" are not available in this place.',
	/*Messages and strings for inline queries*/
	'smw_iq_disabled' => "<span class='smwwarning'>Sorry. Inline queries have been disabled for this wiki.</span>",
	/*Messages and strings for ontology resued (import) */
	'smw_unknown_importns' => '[Sorry, import functions are not avalable for namespace "$1".]',
	'smw_nonright_importtype' => '[Sorry, $1 can only be used for articles with namespace "$2"]',
	'smw_wrong_importtype' => '[Sorry, $1 can not be used for articles in the namespace "$2"]',
	'smw_no_importelement' => '[Sorry, element "$1" not available for import.]',
	/*Messages and strings for basic datatype processing*/
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_unknowntype' => '[Oops! Unsupported type "$1" defined for attribute]',
	'smw_noattribspecial' => '[Oops! Special property "$1" is not an attribute (use "::" instead of ":=")]',
	'smw_notype' => '[Oops! No type defined for attribute]',
	'smw_manytypes' => '[Oops! More than one type defined for attribute]',
	'smw_emptystring' => '[Oops! Empty strings are not accepted]',
	'smw_maxstring' => '[Sorry, string representation $1 is too long for this site.]',
	'smw_nointeger' => '[Oops! "$1" is no integer number]',
	'smw_nofloat' => '[Oops! "$1" is no floating point number]',
	'smw_infinite' => '[Sorry, numbers as long as $1 are not supported on this site.]',
	'smw_infinite_unit' => '[Sorry, conversion into unit $1 resulted in a number that is too long for this site.]',
	'smw_unexpectedunit' => 'this attribute supports no unit conversion',
	'smw_unsupportedunit' => 'unit conversion for unit "$1" not supported',
	/*Messages for geo coordinates parsing*/
	'smw_err_latitude' => 'Values for latitude (N, S) must be within 0 and 90. "$1" does not fulfill this condition!',
	'smw_err_longitude' => 'Values for longitude (E, W) must be within 0 and 180. "$1" does not fulfill this condition!',
	'smw_err_noDirection' => '[Oops! Something is wrong with the given value "$1"]',
	'smw_err_parsingLatLong' => '[Oops! Something is wrong with the given value "$1". We expect a value like "1°2′3.4′′ W" at this place!]',
	'smw_err_wrongSyntax' => '[Oops! Something is wrong with the given value "$1". We expect a value like "1°2′3.4′′ W, 5°6′7.8′′ N" at this place!]',
	'smw_err_sepSyntax' => 'The given value "$1" seems to be right, but values for latitude and longitude should be seperated by "," or ";".',
	'smw_err_notBothGiven' => 'You have to specify a valid value for both longitude (E, W) AND latitude (N, S)! At least one is missing!',
	/* additionals ... */
	'smw_label_latitude' => 'Latitude:',
	'smw_label_longitude' => 'Longitude:',
	'smw_findmaps' => 'find&nbsp;maps',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'E',
	'smw_abb_south' => 'S',
	'smw_abb_west' => 'W',
	/*Messages for datetime parsing */
	'smw_nodatetime' => '[Oops! The date "$1" was not understood. However, support for dates is still experimental.]'
);


/* private */ var $smwUserMessages = array(
	'smw_devel_warning' => 'This feature is currently under development, and might not be fully functional. Backup your data before using it.',
	/*Messages for Export RDF Special*/
	'exportrdf' => 'Export pages to RDF', //name of this special
	'smw_exportrdf_docu' => '<p>This page allows you to export parts of an article in RDF format. To export article pages, enter the titles in the text box below, one title per line.<p/>',
	'smw_exportrdf_recursive' => 'Recursively export all related pages. Note that the result could be large!',
	'smw_exportrdf_backlinks' => 'Also export all pages that refer to the exported pages. Generates browsable RDF.',
	/*Messages for Search Triple Special*/
	'searchtriple' => 'Simple semantic search', //name of this special
	'smw_searchtriple_docu' => "<p>Fill in either the upper or lower row of the input form to search for relations or attributes, respectively. Some of the fields can be left empty to obtain more results. However, if an attribute value is given, the attribute name must be specified as well. As usual, attribute values can be entered with a unit of measurement.</p>\n\n<p>Be aware that you must press the right button to obtain results. Just pressing <i>Return</i> might not trigger the search you wanted.</p>",
	'smw_searchtriple_subject' => 'Subject article:',
	'smw_searchtriple_relation' => 'Relation name:',
	'smw_searchtriple_attribute' => 'Attribute name:',
	'smw_searchtriple_object' => 'Object article:',
	'smw_searchtriple_attvalue' => 'Attribute value:',
	'smw_searchtriple_searchrel' => 'Search Relations',
	'smw_searchtriple_searchatt' => 'Search Attributes',
	'smw_searchtriple_resultrel' => 'Search results (relations)',
	'smw_searchtriple_resultatt' => 'Search results (attributes)',
	/*Messages for Relations Special*/
	'relations' => 'Relations',
	'smw_relations_docu' => 'The following relations exist in the wiki.',
	/*Messages for Attributes Special*/
	'attributes' => 'Attributes',
	'smw_attributes_docu' => 'The following attributes exist in the wiki.',
	'smw_attr_type_join' => ' with $1',
	/*Messages for Unused Relations Special*/
	'unusedrelations' => 'Unused relations',
	'smw_unusedrelations_docu' => 'The following relation pages exist although no other page makes use of them.',
	/*Messages for Unused Attributes Special*/
	'unusedattributes' => 'Unused attributes',
	'smw_unusedattributes_docu' => 'The following attribute pages exist although no other page makes use of them.',
	/* Messages for the refresh button */
	'tooltip-purge' => 'Click here to refresh all queries and templates on this page',
	'purge' => 'refresh',
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
	'types' => 'Types',
	'smw_types_docu' => 'The following is a list of all datatypes that can be assigned to attributes. Each datatype has an article where additional information can be provided.',
	'smw_types_units' => 'Standard unit: $1; supported units: $2',
	'smw_types_builtin' => 'Built-in types'
);

/* private */ var $smwDatatypeLabels = array(
	'smw_string' => 'String',  // name of the string type
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

/* private */ var $smwSpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Has type',
	SMW_SP_HAS_URI   => 'Equivalent URI',
	SMW_SP_IS_SUBRELATION_OF   => 'Is subrelation of',
	SMW_SP_IS_SUBATTRIBUTE_OF   => 'Is subattribute of',
	SMW_SP_MAIN_DISPLAY_UNIT => 'Main display unit',
	SMW_SP_DISPLAY_UNIT => 'Display unit',
	SMW_SP_IMPORTED_FROM => 'Imported from',
	SMW_SP_CONVERSION_FACTOR => 'Corresponds to'
);


	/**
	 * Function that returns the namespace identifiers.
	 */
	function getNamespaceArray() {
		return array(
			SMW_NS_RELATION       => 'Relation',
			SMW_NS_RELATION_TALK  => 'Relation_talk',
			SMW_NS_ATTRIBUTE      => 'Attribute',
			SMW_NS_ATTRIBUTE_TALK => 'Attribute_talk',
			SMW_NS_TYPE           => 'Type',
			SMW_NS_TYPE_TALK      => 'Type_talk'
		);
	}

	/**
	 * Function that returns the localized label for a datatype.
	 */
	function getDatatypeLabel($msgid) {
		return $this->smwDatatypeLabels[$msgid];
	}

	/**
	 * Function that returns the labels for the special relations and attributes.
	 */
	function getSpecialPropertiesArray() {
		return $this->smwSpecialProperties;
	}

	/**
	 * Function that returns all content messages (those that are stored
	 * in some article, and can thus not be translated to individual users).
	 */
	function getContentMsgArray() {
		return $this->smwContentMessages;
	}

	/**
	 * Function that returns all user messages (those that are given only to
	 * the current user, and can thus be given in the individual user language).
	 */
	function getUserMsgArray() {
		return $this->smwUserMessages;
	}
}

?>
