<?php
/**
 * @author Siebrand Mazeland
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageNl extends SMW_Language {

protected $smwContentMessages = array(
	'smw_edithelp' => 'Bewerkingshulp bij eigenschappen',
	'smw_helppage' => 'Relatie',
	'smw_viewasrdf' => 'RDF-feed',
	'smw_finallistconjunct' => ', en', //used in "A, B, and C"
	'smw_factbox_head' => 'Feiten over $1',
	'smw_spec_head' => 'Speciale eigenschappen',
	// URIs that should not be used in objects in cases where users can provide URIs
	'smw_baduri' => 'Sorry, URI\'s uit de reeks “$1” zijn hier niet beschikbaar.',
	// Messages and strings for inline queries
	'smw_iq_disabled' => "<span class='smwwarning'>Sorry. Zoekopdrachten binnen tekst zijn uitgeschakeld in deze wiki.</span>",
	'smw_iq_moreresults' => '&hellip; overige resultaten',
	'smw_iq_nojs' => 'Gebruiker een browser waarin JavaScript is ingeschakeld om dit element te zien, of <a href="$1">bekijk de resultatenlijst</a>.',
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => 'Importfuncties zijn niet beschikbaar voor de naamruimte “$1”.',
	'smw_nonright_importtype' => '$1 kan alleen gebruikt worden voor pagina\'s in de naamruimte “$2”.',
	'smw_wrong_importtype' => '$1 kan niet gebruikt worden in pagina\'s in de naamruimte “$2”.',
	'smw_no_importelement' => 'Element “$1” is niet beschikbaar voor import.',
	// Messages and strings for basic datatype processing
	'smw_decseparator' => ',',
	'smw_kiloseparator' => '.',
	'smw_unknowntype' => 'Type “$1” is niet beschikbaar voor de gedefinieerde eigenschap.',
	'smw_manytypes' => 'Meer dan één type gedefinieerd voor eigenschap.',
	'smw_emptystring' => 'Lege strings zijn niet toegestaan.',
	'smw_maxstring' => 'Stringrepresentatie $1 is te lang voor deze site.',
	'smw_nopossiblevalues' => 'Mogelijke waarden voor deze eigenschap worden niet geenumereerd.',
	'smw_notinenum' => '“$1” komt niet voor in de lijst met mogelijke waarden ($2) voor deze eigenschap.',
	'smw_noboolean' => '“$1” is niet herkend als een booleaanse waarde (waar/onwaar).',
	'smw_true_words' => 'w,ja,j',	// comma-separated synonyms for boolean TRUE besides 'true' and '1'
	'smw_false_words' => 'o,nee,n',	// comma-separated synonyms for boolean FALSE besides 'false' and '0'
	'smw_nointeger' => '“$1” is geen integer getal.',
	'smw_nofloat' => '“$1” is geen getal met drijvende komma.',
	'smw_infinite' => 'Getallen zo groot als “$1” zijn niet ondersteund door deze site.',
	'smw_infinite_unit' => 'Conversie naar eenheid “$1” resulteerde in een getal dat te groot is voor deze site.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'this property supports no unit conversion',
	'smw_unsupportedprefix' => 'Voorvoegsels voor getallen (“$1”) worden niet ondersteund.',
	'smw_unsupportedunit' => 'Eenheidconversie voor eenheid “$1” is niet ondersteund.',
	// Messages for geo coordinates parsing
	'smw_err_latitude' => 'Waarden voor breedte (N, Z) moeten tussen 0 en 90 liggen, en “$1” voldoet niet aan deze voorwaarde.',
	'smw_err_longitude' => 'Waarden voor lengte (O, W) moeten tussen 0 en 180 liggen, en “$1” voldoet niet aan deze voorwaarde.',
	'smw_err_noDirection' => 'Er is iets misgegaan met de opgegeven waarde “$1”.',
	'smw_err_parsingLatLong' => 'Er is iets misgegaan met de opgegeven waarde “$1” &ndash; er werd iets verwacht als “1°2′3.4′′ W” op deze plaats.',
	'smw_err_wrongSyntax' => 'Er is iets mis met de opgegeven waarde “$1” &ndash; er werd iets verwacht als “1°2′3.4′′ W, 5°6′7.8′′ N” op deze plaats.',
	'smw_err_sepSyntax' => 'De opgegeven waarde “$1” lijkt in orde, maar de waarden voor breedte en lengte moeten gescheiden worden door “,” of “;”.',
	'smw_err_notBothGiven' => 'Geef alstublieft een geldige waarde op voor zowel lengte (O, W) <it>als</it> breedte (N, Z) &ndash; er mist er tenminste één.',
	// additionals ...
	'smw_label_latitude' => 'Breedte:',
	'smw_label_longitude' => 'Lengte:',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'O',
	'smw_abb_south' => 'Z',
	'smw_abb_west' => 'W',
	// Messages for datetime parsing
	'smw_nodatetime' => 'De datum “$1” werd niet begrepen (ondersteuning voor datums is nog experimenteel).',
	// Errors and notices related to queries
	'smw_toomanyclosing' => '“$1” lijkt te vaak voor te komen in de zoekopdracht.',
	'smw_noclosingbrackets' => 'In uw zoekopdracht is het gebruik van “[&#x005B;” niet gesloten door een bijbehorende “]]”.',
	'smw_misplacedsymbol' => 'Het symbool “$1” is gebruikt op een plaats waar het niet gebruikt hoort te worden.',
	'smw_unexpectedpart' => 'Het deel “$1” van de zoekopdracht is niet begrepen. De resultaten kunnen afwijken van de verwachting.',
	'smw_emtpysubquery' => 'Er is een subzoekopdracht met een onjuiste conditie.',
	'smw_misplacedsubquery' => 'Er is een subzoekopdracht gebruikt op een plaats waar subzoekopdrachten niet gebruikt mogen worden.',
	'smw_valuesubquery' => 'Subzoekopdrachten worden niet ondersteund voor waarden van eigenschap “$1”.',
	'smw_overprintoutlimit' => 'De zoekopdracht bevat te veel printoutverzoeken.',
	'smw_badprintout' => 'Er is een print statement in de zoekopdracht onjuist geformuleerd.',
	'smw_badtitle' => 'Sorry, maar “$1” is geen geldige paginanaam.',
	'smw_badqueryatom' => 'Een onderdeel “[#x005B;&hellip]]” van de zoekopdrtacht is niet begrepen.',
	'smw_propvalueproblem' => 'De waarde van eigenschap “$1” is niet begrepen.',
	'smw_nodisjunctions' => 'Scheidingen in zoekopdrachten worden niet ondersteund in deze wiki en er is een deel van de zoekopdracht genegeerd ($1).',
	'smw_querytoolarge' => 'De volgende zoekopdrachtcondities zijn niet in acht genomen vanwege beperkingen in de grootte of diepte van zoekopdrachten in deze wiki: $1.'
);


protected $smwUserMessages = array(
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
	'smw_propertyspecial' => 'This is a special property with a reserved meaning in the wiki.', // TODO: translate
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
	'smw_types_units' => 'Standaardeenheid: $1; ondersteunde eenheden: $2',
	'smw_types_builtin' => 'Ingebouwde typen',
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
	'smw_ask_docu' => '<p>Zoek door een zoekopdracht in te geven in het onderstaande invoerveld. Veredere informatie staat op de <a href="$1">helppagina voor semantisch zoeken</a>.</p>',
	'smw_ask_doculink' => 'Semantisch zoeken',
	'smw_ask_sortby' => 'Sort op kolom',
	'smw_ask_ascorder' => 'Oplopend',
	'smw_ask_descorder' => 'Aflopend',
	'smw_ask_submit' => 'Zoek resultaten',
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
	'smw_browse_article' => 'Voer de naam in van de pagina waar u met browsen wilt beginnen.',
	'smw_browse_go' => 'OK',
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

protected $smwDatatypeLabels = array(
	'smw_wikipage' => 'Pagina', // name of page datatype
	'smw_string' => 'String',  // name of the string type
	'smw_text' => 'Tekst',  // name of the text type
	'smw_enum' => 'Opsomming',  // name of the enum type
	'smw_bool' => 'Booleans',  // name of the boolean type
	'smw_int' => 'Integer',  // name of the int type
	'smw_float' => 'Float',  // name of the floating point type
	'smw_geocoordinate' => 'Geographische coordinaat', // name of the geocoord type
	'smw_temperature' => 'Temperatuur',  // name of the temperature type
	'smw_datetime' => 'Datum',  // name of the datetime (calendar) type
	'smw_email' => 'E-mail',  // name of the email (URI) type
	'smw_url' => 'URL',  // name of the URL type (string datatype property)
	'smw_uri' => 'URI',  // name of the URI type (object property)
	'smw_annouri' => 'Annotatie URI'  // name of the annotation URI type (annotation property)
);

protected $smwSpecialProperties = array( //TODO: translate
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Has type',
	SMW_SP_HAS_URI   => 'Equivalent URI',
	SMW_SP_SUBPROPERTY_OF => 'Subproperty of',
	SMW_SP_MAIN_DISPLAY_UNIT => 'Main display unit',
	SMW_SP_DISPLAY_UNIT => 'Display unit',
	SMW_SP_IMPORTED_FROM => 'Imported from',
	SMW_SP_CONVERSION_FACTOR => 'Corresponds to',
	SMW_SP_SERVICE_LINK => 'Provides service',
	SMW_SP_POSSIBLE_VALUE => 'Allows value'
);

	/**
	 * Function that returns the namespace identifiers.
	 */
	public function getNamespaceArray() { //TODO: translate
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
