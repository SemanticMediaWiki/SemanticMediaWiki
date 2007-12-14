<?php
/**
 * @author Javier Calzada Prado, Carmen Jorge García-Reyes, Universidad Carlos III de Madrid, Jesús Espino García
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageEs extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => 'Ayuda a la redacción de relaciones y atributos',
	'smw_viewasrdf' => 'Ver como RDF',
	'smw_finallistconjunct' => ' y',					//utilizado en "A, B, y C"
	'smw_factbox_head' => 'Hechos relativos a à $1 — Búsqueda de páginas similares con <span class="smwsearchicon">+</span>.',
	'smw_isspecprop' => 'This property is a special property in this wiki.', // TODO Translate
	'smw_isknowntype' => 'This type is among the standard datatypes of this wiki.', // TODO Translate
	'smw_isaliastype' => 'This type is an alias for the datatype “$1”.', // TODO Translate
	'smw_isnotype' => 'This type “$1” is not a standard datatype in the wiki, and has not been given a user definition either.', // TODO Translate
	/*URIs that should not be used in objects in cases where users can provide URIs */
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",					// http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#
	'smw_baduri' => 'Lo sentimos. Las URIs del dominio $1 no están disponibles en este emplazamiento',
	/*Messages and strings for inline queries*/
	'smw_iq_disabled' => "Lo sentimos. Las búsquedas en los artículos de este wiki no están autorizadas.",
	'smw_iq_moreresults' => '&hellip; siguientes resultados',
	'smw_iq_nojs' => 'Use un navegador con JavaScript habilitado para ver este elemento.',
	'smw_iq_altresults' => 'Browse the result list directly.', // available link when JS is disabled //TODO: translate
	/*Messages and strings for ontology resued (import) */
	'smw_unknown_importns' => 'Ninguna función de importación está disponible para el espacio de nombres "$1".',
	'smw_nonright_importtype' => 'El elemento "$1" no puede ser empleado más que para los artículos del espacio de nombres "$2".',
	'smw_wrong_importtype' => 'El elemento "$1" no puede ser utilizado para los artículos del espacio de nombres dominio "$2".',
	'smw_no_importelement' => 'El elemento "$1" no está disponible para la importación.',
	/*Messages and strings for basic datatype processing*/
	'smw_decseparator' => ',',
	'smw_kiloseparator' => '.',
	'smw_notitle' => '“$1” cannot be used as a page name in this wiki.', // TODO Translate
	'smw_unknowntype' => 'El tipo de datos "$1" no soportado ha sido devuelto al atributo.',
	'smw_manytypes' => 'Demasiados tipos de datos han sido asignados al atributo.',
	'smw_emptystring' => 'No se aceptan cadenas vacías.',
	'smw_maxstring' => 'La representación de la cadena $1 es demasiado grande para este sitio.',
	'smw_notinenum' => '"$1" no esta en la lista de posibles valores ($2) para este atributo.',
	'smw_noboolean' => '"$1" no es reconocido como un valor booleano (verdadero/falso).',
	'smw_true_words' => 'verdadero,t,si,s,true',
	'smw_false_words' => 'falso,f,no,n,false',
	'smw_nofloat' => '"$1" no es un número.',
	'smw_infinite' => 'El número $1 es demasiado largo.',
	'smw_infinite_unit' => 'La conversión en la unidad $1 es imposible : el número es demasiado largo.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'Este atributo no soporta ninguna conversión de unidad',
	'smw_unsupportedprefix' => 'prefijos ("$1") no esta soportados actualmente',
	'smw_unsupportedunit' => 'La conversión de la unidad "$1" no está soportada',
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => 'No number found before the symbol “$1”.', // $1 is something like ° TODO Translate
	'smw_bad_latlong' => 'Latitude and longitude must be given only once, and with valid coordinates.', // TODO Translate
	'smw_label_latitude' => 'Latitud :',
	'smw_label_longitude' => 'Longitud :',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'E',
	'smw_abb_south' => 'S',
	'smw_abb_west' => 'O',
	/* some links for online maps; can be translated to different language versions of services, but need not*/
	'smw_service_online_maps' => " Mapas&nbsp;geográficos|http://tools.wikimedia.de/~magnus/geo/geohack.php?language=es&params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	/*Messages for datetime parsing */
	'smw_nodatetime' => 'La fecha "$1" no ha sido comprendida. El soporte de datos de calendario son todavía experimentales.',
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
	'smw_devel_warning' => 'Esta función está aún en desarrollo y quizá aun no sea operativa. Es quizá recomendable hacer una copia de seguridad del wiki antes de utilizar esta función.',
	// Messages for article pages of types, relations, and attributes
	'smw_type_header' => 'Atributos de tipo “$1”',
	'smw_typearticlecount' => 'Mostrando $1 atributos usando este tipo.',
	'smw_attribute_header' => 'Paginas usando el atributo “$1”',
	'smw_attributearticlecount' => '<p>Mostrando $1 páginas usando este atributo.</p>',
	/*Messages for Export RDF Special*/
	'exportrdf' => 'Exportar el artículo como RDF', //name of this special
	'smw_exportrdf_docu' => '<p> En esta página, las partes de contenido de un artículo pueden ser exportadas a formato RDF. Introduzca el nombre de las páginas deseadas en el cuadro de texto que se encuentra debajo, <i>un nombre por línea </i>.<p/>',
	'smw_exportrdf_recursive' => 'Exportar igualmente todas las páginas pertinentes de forma recurrente. Esta posibilidad puede conseguir un gran número de resultados !',
	'smw_exportrdf_backlinks' => 'Exportar igualmente todas las páginas que reenvían a páginas exportadas. Resulta un RDF en el que se facilita la navegación.',
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
// 	/*Messages for Relation Special*/
// 	'relations' => 'Relaciones',
// 	'smw_relations_docu' => 'En este wiki existen las siguientes relaciones:',
// 	// Messages for WantedRelations Special
// 	'wantedrelations' => 'Relaciones buscadas',
// 	'smw_wanted_relations' => 'Las relaciones siguientes no tienen una página explicativa todavía, aunque ya están siendo usadas para describir otras páginas.',
// 	/*Messages for Attributes Special*/
// 	'attributes' => 'Atributos',
// 	'smw_attributes_docu' => 'En este wiki existen los siguientes atributos:',
// 	'smw_attr_type_join' => ' &ndash; $1',
// 	/*Messages for Unused Relations Special*/
// 	'unusedrelations' => 'Relaciones huérfanas',
// 	'smw_unusedrelations_docu' => 'Existen páginas para las relaciones siguientes, pero no son utilizadas.',
// 	/*Messages for Unused Attributes Special*/
// 	'unusedattributes' => 'Atributos huérfanos',
// 	'smw_unusedattributes_docu' => 'Existen páginas para los atributos siguientes, pero no son utilizadas.',
	/* Messages for the refresh button */
	'tooltip-purge' => 'Volver a actualizar todas las búsquedas y borradores de esta página.',
	'purge' => 'Volver a actualizar',
	/*Messages for Import Ontology Special*/
	// Messages for Import Ontology Special
	'ontologyimport' => 'Importar la ontología',
	'smw_oi_docu' => 'Esta página especial permite importar datos de una ontología externa. Dicha ontología debe estar en un formato RDF simplificado. Información adicional disponible en <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">Documentación relativa a la importación de ontologías en lengua inglesa.',
	'smw_oi_action' => 'Importar',
	'smw_oi_return' => 'Volver a <a href="$1">Importar la ontología</a>.',	//Différence avec la version anglaise
	'smw_oi_noontology' => 'No ontology supplied, or could not load ontology.', // TODO Translate
	'smw_oi_select' => 'Please select the statements to import, and then click the import button.', // TODO Translate
	'smw_oi_textforall' => 'Header text to add to all imports (may be empty):', // TODO Translate
	'smw_oi_selectall' => 'Select or unselect all statements', // TODO Translate
	'smw_oi_statementsabout' => 'Statements about', // TODO Translate
	'smw_oi_mapto' => 'Map entity to', // TODO Translate
	'smw_oi_comment' => 'Add the following text:', // TODO Translate
	'smw_oi_thisissubcategoryof' => 'A subcategory of', // TODO Translate
	'smw_oi_thishascategory' => 'Is part of', // TODO Translate
	'smw_oi_importedfromontology' => 'Import from ontology', // TODO Translate
	/*Messages for (data)Types Special*/
	'types' => 'Tipos de datos',
	'smw_types_docu' => 'Los tipos de datos siguientes pueden ser asignados a los atributos. Cada tipo de datos tiene su propio artículo, en el que puede figurar información más precisa.',
	'smw_typeunits' => 'Units of measurement of type “$1”: $2', // TODO: Translate
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => 'Semantic Statistics', // TODO translate
	'smw_semstats_text' => 'This wiki contains <b>$1</b> property values for a total of <b>$2</b> different <a href="$3">properties</a>. <b>$4</b> properties have an own page, and the intended datatype is specified for <b>$5</b> of those. Some of the existing properties might by <a href="$6">unused properties</a>. Properties that still lack a page are found on the <a href="$7">list of wanted properties</a>.', // TODO translate
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Flawed Attributes',
	'smw_fattributes' => 'The pages listed below have an incorrectly defined attribute. The number of incorrect attributes is given in the brackets.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'Traductor de URI',
	'smw_uri_doc' => '<p>El traductor de URI implementa <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. Esto se preocupa de cosas que los humanos no lo hacen en los sitios web..</p>',
	/*Messages for ask Special*/
	'ask' => 'Búsqueda semántica',
	'smw_ask_doculink' => 'Búsqueda semántica',
	'smw_ask_sortby' => 'Ordenar por columna',
	'smw_ask_ascorder' => 'Ascendente',
	'smw_ask_descorder' => 'Descendente',
	'smw_ask_submit' => 'Buscar resultados',
	'smw_ask_editquery' => '[Edit query]', // TODO: translate
	'smw_ask_hidequery' => 'Hide query', // TODO: translate
	'smw_ask_help' => 'Querying help', // TODO: translate
	'smw_ask_queryhead' => 'Query', // TODO: translate
	'smw_ask_printhead' => 'Additional printouts (optional)', // TODO: translate
	// Messages for the search by property special
	'searchbyproperty' => 'Buscar por atributo',
	'smw_sbv_docu' => '<p>Buscar por todas las páginas que tiene un atributo y valor dado.</p>',
	'smw_sbv_noproperty' => '<p>Por favor introduzca un atributo.</p>',
	'smw_sbv_novalue' => '<p>Por favor introduzca un valor, o ver todos los valores de atributo para $1.</p>',
	'smw_sbv_displayresult' => 'Una lista de todas las páginas que tienen un atributo $1 con el valor $2.',
	'smw_sbv_property' => 'Atributo',
	'smw_sbv_value' => 'Valor',
	'smw_sbv_submit' => 'Buscar resultados',
	// Messages for the browsing system
	'browse' => 'Explorar artículos',
	'smw_browse_article' => 'Introduzca el nombre de la página para empezar a explorar.',
	'smw_browse_go' => 'Ir',
	'smw_browse_more' => '&hellip;',
	// Messages for the page property special
	'pageproperty' => 'Page property search', // TODO: translate
	'smw_pp_docu' => 'Search for all the fillers of a property on a given page. Please enter both a page and a property.', // TODO: translate
	'smw_pp_from' => 'From page', // TODO: translate
	'smw_pp_type' => 'Property', // TODO: translate
	'smw_pp_submit' => 'Find results', // TODO: translate
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'Anterior',
	'smw_result_next' => 'Siguiente',
	'smw_result_results' => 'Resultados',
	'smw_result_noresults' => 'Lo siento, no hay resultados.'
);

protected $m_DatatypeLabels = array(
	'_wpg' => 'Page', // name of page datatype  //TODO translate
	'_str' => 'Cadena de caracteres',  // name of the string type
	'_txt' => 'Texto',  // name of the text type (very long strings)
	'_boo' => 'Booleano',  // name of the boolean type
	'_num' => 'Número', // name for the datatype of numbers
	'_geo' => 'Coordenadas geográficas', // name of the geocoord type
	'_tem' => 'Temperatura',  // name of the temperature type
	'_dat' => 'Fecha',  // name of the datetime (calendar) type
	'_ema' => 'Dirección electrónica',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'Anotación-URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	'Número entero'         => '_num',
	'Número con coma'       => '_num',
	'Enumeración'           => '_str',
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
	'Annotation URI'        => '_anu'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Tiene tipo de datos',
	SMW_SP_HAS_URI   => 'URI equivalente',
	SMW_SP_SUBPROPERTY_OF => 'Subproperty of', // TODO: translate
	SMW_SP_DISPLAY_UNITS => 'Unidad de medida', // TODO: should be plural now ("units"), singluar stays alias
	SMW_SP_IMPORTED_FROM => 'Importado de',
	SMW_SP_CONVERSION_FACTOR => 'Corresponde a',
	SMW_SP_SERVICE_LINK => 'Provee servicio',
	SMW_SP_POSSIBLE_VALUE => 'Permite el valor'
);

protected $m_SpecialPropertyAliases = array(
	'Unidad de medida'  => SMW_SP_DISPLAY_UNITS,
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
	SMW_NS_RELATION       => "Relación",
	SMW_NS_RELATION_TALK  => "Discusión_relación",
	SMW_NS_PROPERTY       => "Atributo",
	SMW_NS_PROPERTY_TALK  => "Discusión_atributo",
	SMW_NS_TYPE           => "Tipos_de_datos",
	SMW_NS_TYPE_TALK      => "Discusión_tipos_de_datos"
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


