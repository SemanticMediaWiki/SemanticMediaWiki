<?php
/**
 * @author Siebrand Mazeland
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageNl extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => 'Bewerkingshulp bij eigenschappen',
	'smw_viewasrdf' => 'RDF-feed',
	'smw_finallistconjunct' => ', en', //used in "A, B, and C"
	'smw_factbox_head' => 'Feiten over $1',
	'smw_isspecprop' => 'Dit is een speciale eigenschap in de wiki.',
	'smw_isknowntype' => 'This type is among the standard datatypes of this wiki.', // TODO Translate
	'smw_isaliastype' => 'This type is an alias for the datatype “$1”.', // TODO Translate
	'smw_isnotype' => 'This type “$1” is not a standard datatype in the wiki, and has not been given a user definition either.', // TODO Translate
	// URIs that should not be used in objects in cases where users can provide URIs
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Sorry, URI\'s uit de reeks “$1” zijn hier niet beschikbaar.',
	// Messages and strings for inline queries
	'smw_iq_disabled' => "Sorry. Zoekopdrachten binnen tekst zijn uitgeschakeld in deze wiki.",
	'smw_iq_moreresults' => '&hellip; overige resultaten',
	'smw_iq_nojs' => 'Gebruiker een browser waarin JavaScript is ingeschakeld om dit element te zien.',
	'smw_iq_altresults' => 'Browse the result list directly.', // available link when JS is disabled // TODO: translate
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => 'Importfuncties zijn niet beschikbaar voor de naamruimte “$1”.',
	'smw_nonright_importtype' => '$1 kan alleen gebruikt worden voor pagina\'s in de naamruimte “$2”.',
	'smw_wrong_importtype' => '$1 kan niet gebruikt worden in pagina\'s in de naamruimte “$2”.',
	'smw_no_importelement' => 'Element “$1” is niet beschikbaar voor import.',
	// Messages and strings for basic datatype processing
	'smw_decseparator' => ',',
	'smw_kiloseparator' => '.',
	'smw_notitle' => '“$1” cannot be used as a page name in this wiki.', // TODO Translate
	'smw_unknowntype' => 'Type “$1” is niet beschikbaar voor de gedefinieerde eigenschap.',
	'smw_manytypes' => 'Meer dan één type gedefinieerd voor eigenschap.',
	'smw_emptystring' => 'Lege strings zijn niet toegestaan.',
	'smw_maxstring' => 'Stringrepresentatie $1 is te lang voor deze site.',
	'smw_notinenum' => '“$1” komt niet voor in de lijst met mogelijke waarden ($2) voor deze eigenschap.',
	'smw_noboolean' => '“$1” is niet herkend als een booleaanse waarde (waar/onwaar).',
	'smw_true_words' => 'w,ja,j',	// comma-separated synonyms for boolean TRUE besides 'true' and '1'
	'smw_false_words' => 'o,nee,n',	// comma-separated synonyms for boolean FALSE besides 'false' and '0'
	'smw_nofloat' => '“$1” is geen getal.',
	'smw_infinite' => 'Getallen zo groot als “$1” zijn niet ondersteund door deze site.',
	'smw_infinite_unit' => 'Conversie naar eenheid “$1” resulteerde in een getal dat te groot is voor deze site.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'this property supports no unit conversion',
	'smw_unsupportedprefix' => 'Voorvoegsels voor getallen (“$1”) worden niet ondersteund.',
	'smw_unsupportedunit' => 'Eenheidconversie voor eenheid “$1” is niet ondersteund.',
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => 'No number found before the symbol “$1”.', // $1 is something like ° TODO Translate
	'smw_bad_latlong' => 'Latitude and longitude must be given only once, and with valid coordinates.', // TODO Translate
	'smw_label_latitude' => 'Breedte:',
	'smw_label_longitude' => 'Lengte:',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'O',
	'smw_abb_south' => 'Z',
	'smw_abb_west' => 'W',
	// some links for online maps; can be translated to different language versions of services, but need not
	'smw_service_online_maps' => " find&nbsp;maps|http://tools.wikimedia.de/~magnus/geo/geohack.php?params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	// Messages for datetime parsing
	'smw_nodatetime' => 'De datum “$1” werd niet begrepen (ondersteuning voor datums is nog experimenteel).',
	// Errors and notices related to queries
	'smw_toomanyclosing' => '“$1” lijkt te vaak voor te komen in de zoekopdracht.',
	'smw_noclosingbrackets' => 'In uw zoekopdracht is het gebruik van “[&#x005B;” niet gesloten door een bijbehorende “]]”.',
	'smw_misplacedsymbol' => 'Het symbool “$1” is gebruikt op een plaats waar het niet gebruikt hoort te worden.',
	'smw_unexpectedpart' => 'Het deel “$1” van de zoekopdracht is niet begrepen. De resultaten kunnen afwijken van de verwachting.',
	'smw_emptysubquery' => 'Er is een subzoekopdracht met een onjuiste conditie.',
	'smw_misplacedsubquery' => 'Er is een subzoekopdracht gebruikt op een plaats waar subzoekopdrachten niet gebruikt mogen worden.',
	'smw_valuesubquery' => 'Subzoekopdrachten worden niet ondersteund voor waarden van eigenschap “$1”.',
	'smw_overprintoutlimit' => 'De zoekopdracht bevat te veel printoutverzoeken.',
	'smw_badprintout' => 'Er is een print statement in de zoekopdracht onjuist geformuleerd.',
	'smw_badtitle' => 'Sorry, maar “$1” is geen geldige paginanaam.',
	'smw_badqueryatom' => 'Een onderdeel “[&#x005B;&hellip;]]” van de zoekopdrtacht is niet begrepen.',
	'smw_propvalueproblem' => 'De waarde van eigenschap “$1” is niet begrepen.',
	'smw_nodisjunctions' => 'Scheidingen in zoekopdrachten worden niet ondersteund in deze wiki en er is een deel van de zoekopdracht genegeerd ($1).',
	'smw_querytoolarge' => 'De volgende zoekopdrachtcondities zijn niet in acht genomen vanwege beperkingen in de grootte of diepte van zoekopdrachten in deze wiki: $1.'
);

protected $m_UserMessages = array(
	'smw_devel_warning' => 'Deze functie wordt op het moment ontwikkeld en is wellicht niet volledig functioneel. Maak een back-up voordat u deze functie gebruikt.',
	// Messages for pages of types and properties
	'smw_type_header' => 'Eigenschappen voor type “$1”',
	'smw_typearticlecount' => 'Er zijn $1 eigenschappen die gebruik maken van dit type.',
	'smw_attribute_header' => 'Pagina\'s die de eigenschap “$1” gebruiken',
	'smw_attributearticlecount' => '<p>Er zijn $1 pagina\'s die deze eigenschap gebruiken.</p>',
	// Messages for Export RDF Special
	'exportrdf' => 'Export pagina\'s naar RDF', //name of this special
	'smw_exportrdf_docu' => '<p>Deze pagina maakt het mogelijk gegevens te verkrijgen van een pagina in RDF-formaat. Geef titels in het onderstaande invoerveld in om pagina\'s te exporteren. Iedere pagina op een eigen regel.</p>',
	'smw_exportrdf_recursive' => 'Exporteer alle gerelateerde pagina\'s recursief. Het resultaat kan groot zijn!',
	'smw_exportrdf_backlinks' => 'Exporteer ook alle pagina\'s die verwijzen naar de te exporteren pagina\'s. Genereert door te bladeren RDF.',
	'smw_exportrdf_lastdate' => 'Exporteer geen pagina\'s die sinds het opgegeven punt niet gewijzigd zijn.',
	// Messages for Properties Special
	'properties' => 'Eigenschappen',
	'smw_properties_docu' => 'De volgende eigenschappen worden in de wiki gebruikt.',
	'smw_property_template' => '$1 van type $2 ($3)', // <propname> of type <type> (<count>)
	'smw_propertylackspage' => 'Alle eigenschappen moeten op een pagina beschreven worden!',
	'smw_propertylackstype' => 'Er is geen type opgegeven voor deze eigenschap (type $1 wordt verondersteld).',
	'smw_propertyhardlyused' => 'Deze eigenschap wordt vrijwel niet gebruikt in de wiki!',
	// Messages for Unused Properties Special
	'unusedproperties' => 'Ongebruikte eigenschappen',
	'smw_unusedproperties_docu' => 'De volgende eigenschappen bestaan, hoewel ze niet gebruikt worden.',
	'smw_unusedproperty_template' => '$1 van type $2', // <propname> of type <type>
	// Messages for Wanted Properties Special
	'wantedproperties' => 'Gewenste eigenschappen',
	'smw_wantedproperties_docu' => 'De volgende eigenschapen worden gebruikt in de wiki, maar hebben geen pagina waarop ze worden beschreven.',
	'smw_wantedproperty_template' => '$1 ($2 keren gebruikt)', // <propname> (<count> uses)
	// Messages for the refresh button
	'tooltip-purge' => 'Klik hier om alle zoekopdrachten en sjablonen op deze pagina bij te werken',
	'purge' => 'Verversen',
	// Messages for Import Ontology Special
	'ontologyimport' => 'Importeer ontologie',
	'smw_oi_docu' => 'Via deze speciale pagina is het mogelijk een ontologie te importeren. Een ontologie moet een bepaalde opmaak hebben, die is gespecificeerd op de <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">ontologie importhelppagina</a>.',
	'smw_oi_action' => 'Importeer',
	'smw_oi_return' => 'Keer terug naar <a href="$1">Special:OntologyImport</a>.',
	'smw_oi_noontology' => 'Geen ontologie opgegeven, of de ontologie kon niet geladen worden.',
	'smw_oi_select' => 'Selecteer alstublieft de te importeren declaraties en klik dan op de knop Importeer.',
	'smw_oi_textforall' => 'Koptekst voor alle imports (mag leeg blijven):',
	'smw_oi_selectall' => 'Selecteer of deselecteer alle declaraties',
	'smw_oi_statementsabout' => 'Declaraties over',
	'smw_oi_mapto' => 'Koppel entiteit aan',
	'smw_oi_comment' => 'Voeg de volgende tekst toe:',
	'smw_oi_thisissubcategoryof' => 'Een subcategorie van',
	'smw_oi_thishascategory' => 'Is deel van',
	'smw_oi_importedfromontology' => 'Importeer van ontologie',
	// Messages for (data)Types Special
	'types' => 'Typen',
	'smw_types_docu' => 'Hieronder staat een lijst van alle datatypen die aan eigenschappen kunnen worden toegewezen. Ieder datatype heeft een pagina waar aanvullende informatie opgegeven kan worden.',
	'smw_typeunits' => 'Units of measurement of type “$1”: $2', // TODO: Translate
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => 'Semantische statistieken',
	'smw_semstats_text' => 'Deze wiki bevat <b>$1</b> eigenschapwaaren voor <b>$2</b> verschillden <a href="$3">eigenschappen</a>. <b>$4</b> properties have an own page, and the intended datatype is specified for <b>$5</b> of those. Some of the existing properties might by <a href="$6">unused properties</a>. Eigenschappen waar nog geen pagina voor is zijn te vinden op de <a href="$7">lijst met gewenste eigenschappen</a>.',
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Incomplete eigenschappen',
	'smw_fattributes' => 'De onderstaande pagina\s hebben een onjuist gedefinieerde eigenschap. Het aantal onjuiste eigenschappen staat tussen de haakjes.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'URI-resolver',
	'smw_uri_doc' => '<p>De URI-resolver implementeert de <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. Het zorgt ervoor dat mensen niet veranderen in websites.</p>',
	// Messages for ask Special
	'ask' => 'Semantisch zoeken',
	'smw_ask_doculink' => 'Semantisch zoeken',
	'smw_ask_sortby' => 'Sort op kolom',
	'smw_ask_ascorder' => 'Oplopend',
	'smw_ask_descorder' => 'Aflopend',
	'smw_ask_submit' => 'Zoek resultaten',
	'smw_ask_editquery' => '[Edit query]', // TODO: translate
	'smw_ask_hidequery' => 'Hide query', // TODO: translate
	'smw_ask_help' => 'Querying help', // TODO: translate
	'smw_ask_queryhead' => 'Query', // TODO: translate
	'smw_ask_printhead' => 'Additional printouts (optional)', // TODO: translate
	// Messages for the search by property special
	'searchbyproperty' => 'Zoek op eigenschap',
	'smw_sbv_docu' => '<p>Zoek naar alle pagina\'s die een bepaalde eigenschap en waarde hebben.</p>',
	'smw_sbv_noproperty' => '<p>Voer alstublieft een eigenschap in.</p>',
	'smw_sbv_novalue' => '<p>Voer alstublieft een geldige waarde in voor de eigenschap, of bekijk alle waarden voor eigenschap “$1.”</p>',
	'smw_sbv_displayresult' => 'Een lijst met alle pagina\'s waarop eigenschap “$1” de waarde “$2” heeft',
	'smw_sbv_property' => 'Eigenschap',
	'smw_sbv_value' => 'Waarde',
	'smw_sbv_submit' => 'Zoek resultaten',
	// Messages for the browsing special
	'browse' => 'Browse wiki',
	'smw_browse_article' => 'Voer de naam in van de pagina waar u met browsen wilt beginnen.',
	'smw_browse_go' => 'OK',
	'smw_browse_more' => '&hellip;',
	// Messages for the page property special
	'pageproperty' => 'Eigenschap pagina zoeken',
	'smw_pp_docu' => 'Zoek naar alle fillers voor een eigenschap op een gegeven pagina. Voer alstublieft zowel een pagina als een eigenschap in.',
	'smw_pp_from' => 'Van pagina',
	'smw_pp_type' => 'Eigenschap',
	'smw_pp_submit' => 'Zoek resultaten',
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'Vorige',
	'smw_result_next' => 'Volgende',
	'smw_result_results' => 'Resultaten',
	'smw_result_noresults' => 'Sorry, geen resultaten.'
);

protected $m_DatatypeLabels = array(
	'_wpg' => 'Pagina', // name of page datatype
	'_str' => 'String',  // name of the string type
	'_txt' => 'Tekst',  // name of the text type
	//'_boo' => 'Booleans',  // name of the boolean type
	'_num' => 'Number', // name for the datatype of numbers // TODO: translate
	'_geo' => 'Geographische coordinaat', // name of the geocoord type
	'_tem' => 'Temperatuur',  // name of the temperature type
	'_dat' => 'Datum',  // name of the datetime (calendar) type
	'_ema' => 'E-mail',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'Annotatie URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	'Integer'               => '_num',
	'Float'                 => '_num',
	'Opsomming'             => '_str',
	// support English aliases:
	'Page'                  => '_wpg',
	'String'                => '_str',
	'Text'                  => '_txt',
	'Number'                => '_num',
	'Geographic coordinate' => '_geo',
	'Temperature'           => '_tem',
	'Date'                  => '_dat',
	'Email'                 => '_ema',
	'Annotation URI'        => '_anu'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Heeft type',
	SMW_SP_HAS_URI   => 'Equivalent URI', // TODO: translate
	SMW_SP_SUBPROPERTY_OF => 'Subeigenschap van',
	SMW_SP_DISPLAY_UNITS => 'Display units', // TODO: translate
	SMW_SP_IMPORTED_FROM => 'Geïmporteerd van',
	SMW_SP_CONVERSION_FACTOR => 'Komt overeen met',
	SMW_SP_SERVICE_LINK => 'Verleent dienst',
	SMW_SP_POSSIBLE_VALUE => 'Geldige waarde'
);

protected $m_SpecialPropertyAliases = array(
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
	SMW_NS_RELATION       => 'Relatie',
	SMW_NS_RELATION_TALK  => 'Overleg_relatie',
	SMW_NS_PROPERTY       => 'Eigenschap',
	SMW_NS_PROPERTY_TALK  => 'Overleg_eigenschap',
	SMW_NS_TYPE           => 'Type',
	SMW_NS_TYPE_TALK      => 'Overleg_type'
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
