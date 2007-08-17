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
	'smw_att_head' => 'Hodnoty atribútov',
	'smw_rel_head' => 'Vzťahy s inými stránkami',
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
	'smw_noattribspecial' => 'Zvláštna vlastnosť "$1" nie je atribút (použite "::" namiesto ":=").',
	'smw_notype' => 'Pre atribút nebol definovaný typ.',
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
	'smw_nodatetime' => 'Nevedel som interpretovať dátum "$1". Ale podpora dátumov je stále v experimentálno štádiu.'
);


protected $smwUserMessages = array(
	'smw_devel_warning' => 'Táto vlastnosť je momentálne vo vývoji a nemusí byť celkom funkčná. Predtým, než ju použijete si zálohujte dáta.',
	// Messages for article pages of types, relations, and attributes
	'smw_type_header' => 'Attributes of type “$1”', // TODO translate
	'smw_typearticlecount' => 'Showing $1 attributes using this type.', // TODO translate
	'smw_attribute_header' => 'Pages using the attribute “$1”', // TODO translate
	'smw_attributearticlecount' => '<p>Showing $1 pages using this attribute.</p>', // TODO translate
	'smw_relation_header' => 'Pages using the relation “$1”', // TODO translate
	'smw_relationarticlecount' => '<p>Showing $1 pages using this relation.</p>', // TODO translate
	/*Messages for Export RDF Special*/
	'exportrdf' => 'Exportovať stránky do RDF', //name of this special
	'smw_exportrdf_docu' => '<p>Táto stránka vám umožňuje exportovať časti stránok do formátu RDF. Po zadaní názvov stránok do spodného textového poľa, jeden názov na riadok, môžete exportovať stránky.</p>',
	'smw_exportrdf_recursive' => 'Rekurzívne exportovať všetky súvisiace stránky. Pozor, výsledok môže byť veľmi veľký!',
	'smw_exportrdf_backlinks' => 'Tieť exportovať všetky stránky, ktoré odkazujú na exportované stránky. Vytvorí prehliadateľné RDF.',
	/*Messages for Search Triple Special*/
	'searchtriple' => 'Jednoduché sémantické vyhľadávanie', //name of this special
	'smw_searchtriple_docu' => "<p>Vyplňte buď vrchný alebo spodný riadok vstupného formulára pre hľadanie vzťahov resp. atribútov. Niektoré z polí je možné nechať nevyplnené, čím získate viac výsledkov. Ak je však zadaná hodnota atribútu, musí byť zadaný aj názov atribútu. Ako obyčajne, hodnoty atribútov je možné zadať spolu s meracou jednotkou.</p>\n\n<p>Buďte si vedomý, že je potrebné stlačiť správne tlačidlo, aby ste dostali výsledky. Iba stlačenie klávesy <i>Enter</i> nemusí spustiť vyhľadávanie, ktoré chcete.</p>",
	'smw_searchtriple_subject' => 'Stránka predmetu:',
	'smw_searchtriple_relation' => 'Názov vzťahu:',
	'smw_searchtriple_attribute' => 'Názov atribútu:',
	'smw_searchtriple_object' => 'Stránka objektu:',
	'smw_searchtriple_attvalue' => 'Hodnota atribútu:',
	'smw_searchtriple_searchrel' => 'Hľadaj vzťahy',
	'smw_searchtriple_searchatt' => 'Hľadaj atribúty',
	'smw_searchtriple_resultrel' => 'Hľadaj výsledky (vzťahy)',
	'smw_searchtriple_resultatt' => 'Hľadaj výsledky (atribúty)',
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
	/*Messages for ExtendedStatistics Special*/
	'extendedstatistics' => 'Rozšírená štatistika',
	'smw_extstats_general' => 'Všeobecná štatistika',
	'smw_extstats_totalp' => 'Celkový počet stránok:',
	'smw_extstats_totalv' => 'Celkový počet prehliadnutí:',
	'smw_extstats_totalpe' => 'Celkový počet úprav stránok:',
	'smw_extstats_totali' => 'Celkový počet obrázkov:',
	'smw_extstats_totalu' => 'Celkový počet používateľov:',
	'smw_extstats_totalr' => 'Celkový počet vzťahov:',
	'smw_extstats_totalri' => 'Celkový počet inštancií vzťahov:',
	'smw_extstats_totalra' => 'Average number inštancií na vzťah:',
	'smw_extstats_totalpr' => 'Celkový počet stránok o vzťahoch:',
	'smw_extstats_totala' => 'Celkový počet atribútov:',
	'smw_extstats_totalai' => 'Celkový počet inštancií atribútov:',
	'smw_extstats_totalaa' => 'Average number inštancií na atribút:',
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
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'Späť',
	'smw_result_next' => 'Ďalej',
	'smw_result_results' => 'Výsledky',
	'smw_result_noresult' => 'Prepáčte, žiadne výsledky.'
);

protected $smwDatatypeLabels = array(
	'smw_wikipage' => 'Page', // name of page datatype  //TODO translate
	'smw_string' => 'Reťazec',  // name of the string type
	'smw_text' => 'Text',  // name of the text type (very long strings) //TODO: translate
	'smw_enum' => 'Enumeration',  // name of the enum type TODO: translate
	'smw_bool' => 'Boolean',  // name of the boolean type TODO: translate
	'smw_int' => 'Celé číslo',  // name of the int type
	'smw_float' => 'Desatinné číslo',  // name of the floating point type
	'smw_length' => 'Dĺžka',  // name of the length type
	'smw_area' => 'Oblasť',  // name of the area type
	'smw_geolength' => 'Zemepisná dĺžka',  // OBSOLETE name of the geolength type
	'smw_geoarea' => 'Zemepisná oblasť',  // OBSOLETE name of the geoarea type
	'smw_geocoordinate' => 'Zemepisné súradnice', // name of the geocoord type
	'smw_mass' => 'hmotnosť',  // name of the mass type
	'smw_time' => 'čas',  // name of the time (duration) type
	'smw_temperature' => 'Teplota',  // name of the temperature type
	'smw_datetime' => 'Dátum',  // name of the datetime (calendar) type
	'smw_email' => 'Email',  // name of the email (URI) type
	'smw_url' => 'URL',  // name of the URL type (string datatype property)
	'smw_uri' => 'URI',  // name of the URI type (object property)
	'smw_annouri' => 'URI anotácie'  // name of the annotation URI type (annotation property)
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
	SMW_SP_CONVERSION_FACTOR_SI => 'Corresponds to SI', //TODO translate
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


