<?php
/**
 * @author helix84
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageSk extends SMW_Language {

protected $smwContentMessages = array(
	'smw_edithelp' => 'Pomoc pri upravovaní vzťahov a atribútov',
	'smw_helppage' => 'Vzťah',
	'smw_viewasrdf' => 'RDF feed',
	'smw_finallistconjunct' => ' a', //used in "A, B, and C"
	'smw_factbox_head' => 'Skutočnosti o $1 &mdash; Kliknutím na <span class="smwsearchicon">+</span> vyhľadáte podobné stránky.',
	'smw_spec_head' => 'Zvláštne vlastnosti',
	/*URIs that should not be used in objects in cases where users can provide URIs */
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Prepáčte, URI z rozsahu "$1" na tomto mieste nie sú dostupné.',
	/*Messages and strings for inline queries*/
	'smw_iq_disabled' => "<span class='smwwarning'>Prepáčte. Inline queries have been disabled for this wiki.</span>",
	'smw_iq_morevýsledky' => '&hellip; ďalšie výsledky',
	'smw_iq_nojs' => 'Tento prvok zobrazíte použitím prehliadača podporujúcim JavaScript alebo priamo <a href="$1">prehliadajte zoznam výsledkov</a>.',
	/*Messages and strings for ontology resued (import) */
	'smw_unknown_importns' => 'Funkcie importu nie sú dostupné pre menný priestor "$1".',
	'smw_nonright_importtype' => '$1 je možné použiť iba pre stránky z menného priestoru "$2".',
	'smw_wrong_importtype' => '$1 nie je možné použiť pre stránky z menného priestoru "$2".',
	'smw_no_importelement' => 'Prvok "$1" nie je dostupný na import.',
	/*Messages and strings for basic datatype processing*/
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_unknowntype' => 'Pre atribút je definovaný nepodporovaný typ "$1".',
	'smw_manytypes' => 'Pre atribút bol definovaný viac ako jeden typ.',
	'smw_emptystring' => 'Prázdne reťazcie nie sú akceptované.',
	'smw_maxstring' => 'Reprezentácia reťazca $1 je pre túro stránku príliš dlhá.',
	'smw_nopossiblevalues' => 'Possible values for this attribute are not enumerated.',	//TODO translate
	'smw_notinenum' => '"$1" is not in the list of possible values ($2) for this attribute.',	//TODO translate
	'smw_noboolean' => '"$1" nebolo rozpoznané ako platná hodnota typy boolean (áno/nie).',
	'smw_true_words' => 'áno',	// comma-separated synonyms for boolean TRUE besides 'true' and '1'
	'smw_false_words' => 'nie',	// comma-separated synonyms for boolean FALSE besides 'false' and '0'
	'smw_nointeger' => '"$1" nie je celé číslo (integer).',
	'smw_nofloat' => '"$1" nie je číslo s plávajúcou desatinnou čiarkou.',
	'smw_infinite' => 'Čísla také dlhé ako $1 nie sú na tejto stránke podporované.',
	'smw_infinite_unit' => 'Konverzia na jednotky $1 dala ako výsledok číslo, ktoré je pre túto stránku príliš dlhé.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'tento atribút nepodporuje konverziu jednotiek',
	'smw_unsupportedprefix' => 'prefixes ("$1") are not currently supported',	 // TODO: translate
	'smw_unsupportedunit' => 'konverzia jednotiek "$1" nie je podporované',
	/*Messages for geo coordinates parsing*/
	'smw_err_latitude' => 'Hodnoty zemepisnej šírky (S, J) musia byť v rozmedzí 0 a 90. "$1" nespĺňa túto podmienku!',
	'smw_err_longitude' => 'Hodnoty zemepisnej dĺžky (V, Z) musia byť v rozmedzí 0 a 180. "$1" nespĺňa túto podmienku!',
	'smw_err_noDirection' => 'Niečo je zle na danej hodnote "$1".',
	'smw_err_parsingLatLong' => 'Niečo je zle na danej hodnote "$1". Na tomto mieste očakávame hodnotu ako "1°2′3.4′′ Z"!',
	'smw_err_wrongSyntax' => 'Niečo je zle na danej hodnote  "$1". Na tomto mieste očakávame hodnotu ako "1°2′3.4′′ Z, 5°6′7.8′′ S"!',
	'smw_err_sepSyntax' => 'Daná hodnota "$1" vyzerá byť v poriadku, akehodnoty zemepisnej šírky a dĺžky by mali byť oddelené "," alebo ";".',
	'smw_err_notBothGiven' => 'Musíte uviesť platnú hodnotu pre zemepisnú šírku (V, Z) AJ dĺžku (S, J)! Aspoň jedna z nich chýba!',
	/* additionals ... */
	'smw_label_latitude' => 'Zemepisná šírka:',
	'smw_label_longitude' => 'Zemepisná dĺžka:',
	'smw_abb_north' => 'S',
	'smw_abb_east' => 'V',
	'smw_abb_south' => 'J',
	'smw_abb_west' => 'Z',
	/* some links for online maps; can be translated to different language versions of services, but need not*/
	// TODO: translate "find maps" below, translation of word "maps" would also do.
	'smw_service_online_maps' => " find&nbsp;maps|http://tools.wikimedia.de/~magnus/geo/geohack.php?language=sk&params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	/*Messages for datetime parsing */
	'smw_nodatetime' => 'Nevedel som interpretovať dátum "$1". Ale podpora dátumov je stále v experimentálno štádiu.',
	// Errors and notices related to queries // TODO: translate
	'smw_toomanyclosing' => 'There appear to be too many occurrences of “$1” in the query.',
	'smw_noclosingbrackets' => 'Some use of “[&#x005B;” in your query was not closed by a matching “]]”.',
	'smw_misplacedsymbol' => 'The symbol “$1” was used in a place where it is not useful.',
	'smw_unexpectedpart' => 'The part “$1” of the query was not understood. Results might not be as expected.',
	'smw_emtpysubquery' => 'Some subquery has no valid condition.',
	'smw_misplacedsubquery' => 'Some subquery was used in a place where no subqueries are allowed.',
	'smw_valuesubquery' => 'Subqueries not supported for values of property “$1”.',
	'smw_overprintoutlimit' => 'The query contains too many printout requests.',
	'smw_badprintout' => 'Some print statement in the query was misshaped.',
	'smw_badtitle' => 'Sorry, but “$1” is no valid page title.',
	'smw_badqueryatom' => 'Some part “[#x005B;&hellip]]” of the query was not understood.',
	'smw_propvalueproblem' => 'The value of property “$1” was not understood.',
	'smw_nodisjunctions' => 'Disjunctions in queries are not supported in this wiki and part of the query was dropped ($1).',
	'smw_querytoolarge' => 'The following query conditions could not be considered due to the wikis restrictions in query size or depth: $1.'
);


protected $smwUserMessages = array(
	'smw_devel_warning' => 'Táto vlastnosť je momentálne vo vývoji a nemusí byť celkom funkčná. Predtým, než ju použijete si zálohujte dáta.',
	// Messages for article pages of types, relations, and attributes
	'smw_type_header' => 'Attributes of type “$1”', // TODO translate
	'smw_typearticlecount' => 'Showing $1 attributes using this type.', // TODO translate
	'smw_attribute_header' => 'Pages using the attribute “$1”', // TODO translate
	'smw_attributearticlecount' => '<p>Showing $1 pages using this attribute.</p>', // TODO translate
	/*Messages for Export RDF Special*/
	'exportrdf' => 'Exportovať stránky do RDF', //name of this special
	'smw_exportrdf_docu' => '<p>Táto stránka vám umožňuje exportovať časti stránok do formátu RDF. Po zadaní názvov stránok do spodného textového poľa, jeden názov na riadok, môžete exportovať stránky.</p>',
	'smw_exportrdf_recursive' => 'Rekurzívne exportovať všetky súvisiace stránky. Pozor, výsledok môže byť veľmi veľký!',
	'smw_exportrdf_backlinks' => 'Tieť exportovať všetky stránky, ktoré odkazujú na exportované stránky. Vytvorí prehliadateľné RDF.',
	'smw_exportrdf_lastdate' => 'Do not export pages that were not changed since the given point in time.', // TODO: translate
	// Messages for Properties Special
	'properties' => 'Properties', //TODO: translate
	'smw_properties_docu' => 'The following properties are used in the wiki.', //TODO: translate
	'smw_property_template' => '$1 of type $2 ($3)', // <propname> of type <type> (<count>) //TODO: translate
	'smw_propertylackspage' => 'All properties should be described by a page!', //TODO: translate
	'smw_propertylackstype' => 'No type was specified for this property (assuming type $1 for now).', //TODO: translate
	'smw_propertyhardlyused' => 'This property is hardly used within the wiki!', //TODO: translate
	'smw_propertyspecial' => 'This is a special property with a reserved meaning in the wiki.', // TODO: translate
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
// 	'relations' => 'Relations',
// 	'smw_relations_docu' => 'Nasledujúce vzťahy existujú na wiki.',
// 	/*Messages for Attributes Special*/
// 	'attributes' => 'Attributes',
// 	'smw_attributes_docu' => 'Nasledujúce atribúty existujú na wiki.',
// 	'smw_attr_type_join' => ' s $1',
// 	/*Messages for Unused Relations Special*/
// 	'unusedrelations' => 'Nepoužité vzťahy',
// 	'smw_unusedrelations_docu' => 'Nasledujúce stránky vzťahov existujú, hoci žiadne iné stránky ich nevyužvajú.',
// 	/*Messages for Unused Attributes Special*/
// 	'unusedattributes' => 'Nepoužité atribúty',
// 	'smw_unusedattributes_docu' => 'Nasledujúce stránky atribútov existujú, hoci žiadne iné stránky ich nevyužvajú.',
	/* Messages for the refresh button */
	'tooltip-purge' => 'Kliknutím sem obnovíte všetky dotazy a šablóny na tejto stránke',
	'purge' => 'Obnoviť',
	/*Messages for Import Ontology Special*/
	'ontologyimport' => 'Importovať ontológiu',
	'smw_oi_docu' => 'Táto špeciálna stránka umožňuje import ontológií. Ontológie musia dodržiavať istý formát, špecifkovaný na <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">stránke pomocníka pre import ontógie</a>.',
	'smw_oi_action' => 'Import',
	'smw_oi_return' => 'Návrat na <a href="$1">Special:OntologyImport</a>.',
	'smw_oi_noontology' => 'Ontológia nie je podporovaná alebo nebolo možné načítať ontológiu.',
	'smw_oi_select' => 'Prosím, vyberte výroky, ktoré sa majú importovať a porom kliknite na tlačidlo import.',
	'smw_oi_textforall' => 'Text hlavičky, ktorý sa pridá k všetkým importom (môže byť prázdny):',
	'smw_oi_selectall' => 'Vybrať alebo odobrať všetky výroky o',
	'smw_oi_statementsabout' => 'Výroky o',
	'smw_oi_mapto' => 'Mapuje entitu na',
	'smw_oi_comment' => 'Pridá nasledovný text:',
	'smw_oi_thisissubcategoryof' => 'Je podkategóriou',
	'smw_oi_thishascategory' => 'Je časťou',
	'smw_oi_importedfromontology' => 'Import z ontológie',
	/*Messages for (data)Types Special*/
	'types' => 'Typy',
	'smw_types_docu' => 'Nasleduje zoznam všetkých údajových typov, ktoré je možné priradiť atribútom. Každý údajový typ má stránku, kde je možné poskytnúť dodatočné informácie.',
	'smw_types_units' => 'Štandardné jednotky: $1; podporované jednotky: $2',
	'smw_types_builtin' => 'Vstavané typy',
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => 'Semantic Statistics', // TODO: translate
	'smw_semstats_text' => 'This wiki contains <b>$1</b> property values for a total of <b>$2</b> different <a href="$3">properties</a>. <b>$4</b> properties have an own page, and the intended datatype is specified for <b>$5</b> of those. Some of the existing properties might by <a href="$6">unused properties</a>. Properties that still lack a page are found on the <a href="$7">list of wanted properties</a>.', // TODO: translate
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Chybných atribútov',
	'smw_fattributes' => 'Nižšie uvedené stránky majú nesprávne definovaný atribút. Počet nesprávnych atribútov udáva číslo v zátvorkách.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'URI Resolver',
	'smw_uri_doc' => '<p>URI resolver sa stará o implementáciu <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG hľadanie na httpRange-14</a>. Stará sa o to, aby sa ľudia nestali webstránkami.</p>',
	/*Messages for ask Special*/
	'ask' => 'Sémantické vyhľadávanie',
	'smw_ask_docu' => '<p>Prehľadávajte wiki zadaním dotazu do vyhľadávacieho poľa dolu. Ďalšie informácie sú ovedené na <a href="$1">stránke pomocníka pre sémantické vyhľadávanie</a>.</p>',
	'smw_ask_doculink' => 'Sémantické vyhľadávanie',
	'smw_ask_sortby' => 'Zoradiť podľa stĺpca',
	'smw_ask_ascorder' => 'Vzostupne',
	'smw_ask_descorder' => 'Zostupne',
	'smw_ask_submit' => 'Nájdi výsledky',
	// Messages for the search by value special // TODO: consider re-translation (look at new English version)
	'searchbyproperty' => 'Hľadať podľa hodnoty atribútu',
	'smw_sbv_docu' => '<p>Hľadať na wiki článok, ktorý má atribút s istou hodnotou.</p>',
	'smw_sbv_noproperty' => '<p>Nebol poskytnutý atribút. Prosím, poskytnite ho vo formulári.</p>',
	'smw_sbv_novalue' => '<p>Nebola poskytnutá hodnota. Prosím, poskytnite ju vo formulári alebo zobrazte všetky atribúty typu $1</p>',
	'smw_sbv_displayresult' => 'Zoznam všetkých článkov, ktoré majú atribút $1 $2.',
	'smw_sbv_property' => 'Atribút:',
	'smw_sbv_value' => 'Hodnota:',
	'smw_sbv_submit' => 'Hľadať výsledky',
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
	'smw_result_prev' => 'Späť',
	'smw_result_next' => 'Ďalej',
	'smw_result_results' => 'Výsledky',
	'smw_result_noresult' => 'Prepáčte, žiadne výsledky.'
);

protected $m_DatatypeLabels = array(
	'_wpg' => 'Page', // name of page datatype  //TODO translate
	'_str' => 'Reťazec',  // name of the string type
	'_txt' => 'Text',  // name of the text type (very long strings) //TODO: translate
	'_enu' => 'Enumeration',  // name of the enum type TODO: translate
	'_boo' => 'Boolean',  // name of the boolean type TODO: translate
	'_int' => 'Celé číslo',  // name of the int type
	'_flt' => 'Desatinné číslo',  // name of the floating point type
	'_geo' => 'Zemepisné súradnice', // name of the geocoord type
	'_tem' => 'Teplota',  // name of the temperature type
	'_dat' => 'Dátum',  // name of the datetime (calendar) type
	'_ema' => 'Email',  // name of the email (URI) type
	'_url' => 'URL',  // name of the URL type (string datatype property)
	'_uri' => 'URI',  // name of the URI type (object property)
	'_anu' => 'URI anotácie'  // name of the annotation URI type (annotation property)
);

protected $m_DatatypeAliases = array(
	// support English aliases:
	'Page'                  => '_wpg',
	'String'                => '_str',
	'Text'                  => '_txt',
	'Integer'               => '_int',
	'Float'                 => '_flt',
	'Geographic coordinate' => '_geo',
	'Temperature'           => '_tem',
	'Date'                  => '_dat',
	'Email'                 => '_ema',
	'Annotation URI'        => '_anu'
);

protected $smwSpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Má typ',
	SMW_SP_HAS_URI   => 'Ekvivalent URI',
	SMW_SP_SUBPROPERTY_OF => 'Subproperty of', // TODO: translate
	SMW_SP_MAIN_DISPLAY_UNIT => 'Hlavná zobrazovacia jednotka',
	SMW_SP_DISPLAY_UNIT => 'Zobrazovacia jednotka',
	SMW_SP_IMPORTED_FROM => 'Importovaný z',
	SMW_SP_CONVERSION_FACTOR => 'Zodpovedá',
	SMW_SP_SERVICE_LINK => 'Poskytuje službu',
	SMW_SP_POSSIBLE_VALUE => 'Allowed value'	//TODO translate
);


	/**
	 * Function that returns the namespace identifiers.
	 */
	public function getNamespaceArray() {
		return array(
			SMW_NS_RELATION       => 'Vzťah',
			SMW_NS_RELATION_TALK  => 'Diskusia o vzťahu',
			SMW_NS_PROPERTY       => 'Atribút',
			SMW_NS_PROPERTY_TALK  => 'Diskusia o atribúte',
			SMW_NS_TYPE           => 'Typ',
			SMW_NS_TYPE_TALK      => 'Diskusia o type'
		);
	}
}


