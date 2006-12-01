<?php
/**
 * @author helix84
 */

class SMW_LanguageEn {

/* private */ var $smwContentMessages = array(
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
	'smw_unknown_importns' => '[prepáčte, funkcie importu nie sú dostupné pre menný priestor "$1".]',
	'smw_nonright_importtype' => '[Prepáčte, $1 je možné použiť iba pre stránky z menného priestoru "$2"]',
	'smw_wrong_importtype' => '[Prepáčte, $1 nie je možné použiť pre stránky z menného priestoru "$2"]',
	'smw_no_importelement' => '[Prepáčte, prvok "$1" nie je dostupný na import.]',
	/*Messages and strings for basic datatype processing*/
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_unknowntype' => '[Ops! Pre atribút je definovaný nepodporovaný typ "$1"]',
	'smw_noattribspecial' => '[Ops! Zvláštna vlastnosť "$1" nie je atribút (použite "::" namiesto ":=")]',
	'smw_notype' => '[Ops! Pre atribút nebol definovaný typ]',
	'smw_manytypes' => '[Ops! Pre atribút bol definovaný viac ako jeden typ]',
	'smw_emptystring' => '[Ops! Prázdne reťazcie nie sú akceptované]',
	'smw_maxstring' => '[Prepáčte, reprezentácia reťazca $1 je pre túro stránku príliš dlhá.]',
	'smw_nointeger' => '[Ops! "$1" nie je celé číslo (integer)]',
	'smw_nofloat' => '[Ops! "$1" nie je číslo s plávajúcou desatinnou čiarkou]',
	'smw_infinite' => '[Prepáčte, čísla také dlhé ako $1 nie sú na tejto stránke podporované.]',
	'smw_infinite_unit' => '[Prepáčte, konverzia na jednotky $1 dala ako výsledok číslo, ktoré je pre túto stránku príliš dlhé.]',
	'smw_unexpectedunit' => 'tento atribút nepodporuje konverziu jednotiek',
	'smw_unsupportedunit' => 'konverzia jednotiek "$1" nie je podporované',
	/*Messages for geo coordinates parsing*/
	'smw_err_latitude' => 'Hodnoty zemepisnej šírky (S, J) musia byť v rozmedzí 0 a 90. "$1" nespĺňa túto podmienku!',
	'smw_err_longitude' => 'Hodnoty zemepisnej dĺžky (V, Z) musia byť v rozmedzí 0 a 180. "$1" nespĺňa túto podmienku!',
	'smw_err_noDirection' => '[Ops! Niečo je zle na danej hodnote "$1"]',
	'smw_err_parsingLatLong' => '[Ops! Niečo je zle na danej hodnote "$1". Na tomto mieste očakávame hodnotu ako "1°2′3.4′′ Z"!]',
	'smw_err_wrongSyntax' => '[Ops! Niečo je zle na danej hodnote  "$1". Na tomto mieste očakávame hodnotu ako "1°2′3.4′′ Z, 5°6′7.8′′ S"!]',
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
	'smw_service_online_maps' => " find&nbsp;maps|http://kvaleberg.com/extensions/mapsources/?params=\$1_\$3_\$5_\$7_\$2_\$4_\$6_\$8_region:EN_type:city\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	/*Messages for datetime parsing */
	'smw_nodatetime' => '[Ops! Nevedel som interpretovať dátum "$1". Ale podpora dátumov je stále v experimentálno štádiu.]'
);


/* private */ var $smwUserMessages = array(
	'smw_devel_warning' => 'Táto vlastnosť je momentálne vo vývoji a nemusí byť celkom funkčná. Predtým, než ju použijete si zálohujte dáta.',
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
	/*Messages for Relations Special*/
	'relations' => 'Relations',
	'smw_relations_docu' => 'Nasledujúce vzťahy existujú na wiki.',
	/*Messages for Attributes Special*/
	'attributes' => 'Attributes',
	'smw_attributes_docu' => 'Nasledujúce atribúty existujú na wiki.',
	'smw_attr_type_join' => ' s $1',
	/*Messages for Unused Relations Special*/
	'unusedrelations' => 'Nepoužité vzťahy',
	'smw_unusedrelations_docu' => 'Nasledujúce stránky vzťahov existujú, hoci žiadne iné stránky ich nevyužvajú.',
	/*Messages for Unused Attributes Special*/
	'unusedattributes' => 'Nepoužité atribúty',
	'smw_unusedattributes_docu' => 'Nasledujúce stránky atribútov existujú, hoci žiadne iné stránky ich nevyužvajú.',
	/*Messages for ask Special*/
	'ask' => 'Sémantické vyhľadávanie',
	'smw_ask_docu' => '<p>Prehľadávajte wiki zadaním dotazu do vyhľadávacieho poľa dolu. Ďalšie informácie sú ovedené na <a href="$1">stránke pomocníka pre sémantické vyhľadávanie</a>.</p>',
	'smw_ask_doculink' => 'Sémantické vyhľadávanie',
	'smw_ask_prev' => 'Späť',
	'smw_ask_next' => 'Ďalej',
	'smw_ask_výsledky' => 'Výsledky',
	'smw_ask_novýsledky' => 'Prepáčte, žiadne výsledky.',
	'smw_ask_sortby' => 'Zoradiť podľa stĺpca',
	'smw_ask_ascorder' => 'Vzostupne',
	'smw_ask_descorder' => 'Zostupne',
	'smw_ask_submit' => 'Nájdi výsledky',
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
	'smw_types_builtin' => 'Vstavané typy'
);

/* private */ var $smwDatatypeLabels = array(
	'smw_string' => 'Reťazec',  // name of the string type
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

/* private */ var $smwSpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Má typ',
	SMW_SP_HAS_URI   => 'Ekvivalent URI',
	SMW_SP_IS_SUBRELATION_OF   => 'Je podvvzťahom',
	SMW_SP_IS_SUBATTRIBUTE_OF   => 'Je podatribútom',
	SMW_SP_MAIN_DISPLAY_UNIT => 'Hlavná zobrazovacia jednotka',
	SMW_SP_DISPLAY_UNIT => 'Zobrazovacia jednotka',
	SMW_SP_IMPORTED_FROM => 'Importovaný z',
	SMW_SP_CONVERSION_FACTOR => 'Zodpovedá',
	SMW_SP_SERVICE_LINK => 'Poskytuje službu'
);


	/**
	 * Function that returns the namespace identifiers.
	 */
	function getNamespaceArray() {
		return array(
			SMW_NS_RELATION       => 'Vzťah',
			SMW_NS_RELATION_TALK  => 'Diskusia o vzťahu',
			SMW_NS_ATTRIBUTE      => 'Atribút',
			SMW_NS_ATTRIBUTE_TALK => 'Diskusia o atribúte',
			SMW_NS_TYPE           => 'Typ',
			SMW_NS_TYPE_TALK      => 'Diskusia o type'
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
<?php
/**
 * @author helix84
 */

class SMW_LanguageEn {

/* private */ var $smwContentMessages = array(
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
	'smw_unknown_importns' => '[prepáčte, funkcie importu nie sú dostupné pre menný priestor "$1".]',
	'smw_nonright_importtype' => '[Prepáčte, $1 je možné použiť iba pre stránky z menného priestoru "$2"]',
	'smw_wrong_importtype' => '[Prepáčte, $1 nie je možné použiť pre stránky z menného priestoru "$2"]',
	'smw_no_importelement' => '[Prepáčte, prvok "$1" nie je dostupný na import.]',
	/*Messages and strings for basic datatype processing*/
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_unknowntype' => '[Ops! Pre atribút je definovaný nepodporovaný typ "$1"]',
	'smw_noattribspecial' => '[Ops! Zvláštna vlastnosť "$1" nie je atribút (použite "::" namiesto ":=")]',
	'smw_notype' => '[Ops! Pre atribút nebol definovaný typ]',
	'smw_manytypes' => '[Ops! Pre atribút bol definovaný viac ako jeden typ]',
	'smw_emptystring' => '[Ops! Prázdne reťazcie nie sú akceptované]',
	'smw_maxstring' => '[Prepáčte, reprezentácia reťazca $1 je pre túro stránku príliš dlhá.]',
	'smw_nointeger' => '[Ops! "$1" nie je celé číslo (integer)]',
	'smw_nofloat' => '[Ops! "$1" nie je číslo s plávajúcou desatinnou čiarkou]',
	'smw_infinite' => '[Prepáčte, čísla také dlhé ako $1 nie sú na tejto stránke podporované.]',
	'smw_infinite_unit' => '[Prepáčte, konverzia na jednotky $1 dala ako výsledok číslo, ktoré je pre túto stránku príliš dlhé.]',
	'smw_unexpectedunit' => 'tento atribút nepodporuje konverziu jednotiek',
	'smw_unsupportedunit' => 'konverzia jednotiek "$1" nie je podporované',
	/*Messages for geo coordinates parsing*/
	'smw_err_latitude' => 'Hodnoty zemepisnej šírky (S, J) musia byť v rozmedzí 0 a 90. "$1" nespĺňa túto podmienku!',
	'smw_err_longitude' => 'Hodnoty zemepisnej dĺžky (V, Z) musia byť v rozmedzí 0 a 180. "$1" nespĺňa túto podmienku!',
	'smw_err_noDirection' => '[Ops! Niečo je zle na danej hodnote "$1"]',
	'smw_err_parsingLatLong' => '[Ops! Niečo je zle na danej hodnote "$1". Na tomto mieste očakávame hodnotu ako "1°2′3.4′′ Z"!]',
	'smw_err_wrongSyntax' => '[Ops! Niečo je zle na danej hodnote  "$1". Na tomto mieste očakávame hodnotu ako "1°2′3.4′′ Z, 5°6′7.8′′ S"!]',
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
	'smw_service_online_maps' => " find&nbsp;maps|http://kvaleberg.com/extensions/mapsources/?params=\$1_\$3_\$5_\$7_\$2_\$4_\$6_\$8_region:EN_type:city\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	/*Messages for datetime parsing */
	'smw_nodatetime' => '[Ops! Nevedel som interpretovať dátum "$1". Ale podpora dátumov je stále v experimentálno štádiu.]'
);


/* private */ var $smwUserMessages = array(
	'smw_devel_warning' => 'Táto vlastnosť je momentálne vo vývoji a nemusí byť celkom funkčná. Predtým, než ju použijete si zálohujte dáta.',
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
	/*Messages for Relations Special*/
	'relations' => 'Relations',
	'smw_relations_docu' => 'Nasledujúce vzťahy existujú na wiki.',
	/*Messages for Attributes Special*/
	'attributes' => 'Attributes',
	'smw_attributes_docu' => 'Nasledujúce atribúty existujú na wiki.',
	'smw_attr_type_join' => ' s $1',
	/*Messages for Unused Relations Special*/
	'unusedrelations' => 'Nepoužité vzťahy',
	'smw_unusedrelations_docu' => 'Nasledujúce stránky vzťahov existujú, hoci žiadne iné stránky ich nevyužvajú.',
	/*Messages for Unused Attributes Special*/
	'unusedattributes' => 'Nepoužité atribúty',
	'smw_unusedattributes_docu' => 'Nasledujúce stránky atribútov existujú, hoci žiadne iné stránky ich nevyužvajú.',
	/*Messages for ask Special*/
	'ask' => 'Sémantické vyhľadávanie',
	'smw_ask_docu' => '<p>Prehľadávajte wiki zadaním dotazu do vyhľadávacieho poľa dolu. Ďalšie informácie sú ovedené na <a href="$1">stránke pomocníka pre sémantické vyhľadávanie</a>.</p>',
	'smw_ask_doculink' => 'Sémantické vyhľadávanie',
	'smw_ask_prev' => 'Späť',
	'smw_ask_next' => 'Ďalej',
	'smw_ask_výsledky' => 'Výsledky',
	'smw_ask_novýsledky' => 'Prepáčte, žiadne výsledky.',
	'smw_ask_sortby' => 'Zoradiť podľa stĺpca',
	'smw_ask_ascorder' => 'Vzostupne',
	'smw_ask_descorder' => 'Zostupne',
	'smw_ask_submit' => 'Nájdi výsledky',
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
	'smw_types_builtin' => 'Vstavané typy'
);

/* private */ var $smwDatatypeLabels = array(
	'smw_string' => 'Reťazec',  // name of the string type
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

/* private */ var $smwSpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Má typ',
	SMW_SP_HAS_URI   => 'Ekvivalent URI',
	SMW_SP_IS_SUBRELATION_OF   => 'Je podvvzťahom',
	SMW_SP_IS_SUBATTRIBUTE_OF   => 'Je podatribútom',
	SMW_SP_MAIN_DISPLAY_UNIT => 'Hlavná zobrazovacia jednotka',
	SMW_SP_DISPLAY_UNIT => 'Zobrazovacia jednotka',
	SMW_SP_IMPORTED_FROM => 'Importovaný z',
	SMW_SP_CONVERSION_FACTOR => 'Zodpovedá',
	SMW_SP_SERVICE_LINK => 'Poskytuje službu'
);


	/**
	 * Function that returns the namespace identifiers.
	 */
	function getNamespaceArray() {
		return array(
			SMW_NS_RELATION       => 'Vzťah',
			SMW_NS_RELATION_TALK  => 'Diskusia o vzťahu',
			SMW_NS_ATTRIBUTE      => 'Atribút',
			SMW_NS_ATTRIBUTE_TALK => 'Diskusia o atribúte',
			SMW_NS_TYPE           => 'Typ',
			SMW_NS_TYPE_TALK      => 'Diskusia o type'
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
