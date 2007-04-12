<?php
/**
 * @author Łukasz Bolikowski
 * @version 0.1
 */

/*
 * To further translators: some key terms appear * in multiple strings.
 * If you wish to change them, please be consistent.  The following
 * translations are currently used:
 *   relation = relacja
 *   attribute = atrybut
 *   property = własność
 *   subject article = artykuł podmiotowy
 *   object article = artykuł przedmiotowy
 *   statement = zdanie
 *   conversion = konwersja
 *   search (n) = szukanie
 *   sorry, oops ~ niestety, ojej
 * These ones may need to be refined:
 *   to support = wspierać
 *   on this site = w tym miejscu
 */

class SMW_LanguagePl {

/* private */ var $smwContentMessages = array(
	'smw_edithelp' => 'Pomoc edycyjna odnośnie relacji i atrybutów',
	'smw_helppage' => 'Relacja',
	'smw_viewasrdf' => 'RDF feed', //TODO: translate or leave as is?
	'smw_finallistconjunct' => ' i', //used in "A, B, and C"
	'smw_factbox_head' => 'Fakty o $1 &mdash; Kliknij <span class="smwsearchicon">+</span> aby znaleźć podobne strony.',
	'smw_att_head' => 'Wartości atrybutów',
	'smw_rel_head' => 'Relacje do innych artykułów', //TODO: "do" or "z"?
	'smw_spec_head' => 'Własności specjalne',
	/*URIs that should not be used in objects in cases where users can provide URIs */
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Niestety, URI z przestrzeni "$1" nie są w tym miejscu dostępne.', //TODO: "przestrzeni"?
	/*Messages and strings for inline queries*/
	'smw_iq_disabled' => "<span class='smwwarning'>Niestety, w tym wiki wyłączono możliwość tworzenia zapytań w artykułach.</span>",
	'smw_iq_moreresults' => '&hellip; dalsze wyniki',
	'smw_iq_nojs' => 'Aby obejrzeć ten element, włącz w przeglądarce obsługę JavaScript, lub <a href="$1">przeglądaj listę wyników</a> bezpośrednio.',
	/*Messages and strings for ontology resued (import) */
	'smw_unknown_importns' => '[Niestety, nie ma możliwości importu z przestrzeni nazw "$1".]',
	'smw_nonright_importtype' => '[Niestety, $1 może być użyte tylko dla artykułów z przestrzeni nazw "$2"]', //TODO: "użyte", "użyty"?
	'smw_wrong_importtype' => '[Niestety, $1 nie może być użyte dla artykułów z przestrzeni nazw "$2"]',
	'smw_no_importelement' => '[Niestety, nie można zaimportować elementu "$1".]',
	/*Messages and strings for basic datatype processing*/
	'smw_decseparator' => ',',
	'smw_kiloseparator' => '.',
	'smw_unknowntype' => '[Ojej! "$1" jako typ atrybutu nie jest wspierany]', //TODO: clumsy
	'smw_noattribspecial' => '[Ojej! Własność specjalna "$1" nie jest atrybutem (użyj "::" zamiast ":=")]',
	'smw_notype' => '[Ojej! Nie zdefiniowano typu dla atrybutu]',
	'smw_manytypes' => '[Ojej! Zdefiniowano więcej niż jeden typ dla atrybutu]',
	'smw_emptystring' => '[Ojej! Puste łańcuchy znakowe są niedozwolone]',
	'smw_maxstring' => '[Niestety, reprezentacja znakowa $1 jest za długa jak na to miejsce.]', //TODO: clumsy
	'smw_nointeger' => '[Ojej! "$1" nie jest liczbą całkowitą]',
	'smw_nofloat' => '[Ojej! "$1" nie jest liczbą zmiennoprzecinkową]',
	'smw_infinite' => '[Niestety, liczby tak duże jak $1 nie są w tym miejscu wspierane.]', //TODO: clumsy
	'smw_infinite_unit' => '[Niestety, konwersja do jednostki $1 zwróciła liczbę, która jest za duża jak na to miejsce.]', //TODO: clumsy
	'smw_unexpectedunit' => 'ten atrybut nie wspiera konwersji jednostek',
	'smw_unsupportedunit' => 'konwersja dla jednostki "$1" nie jest wspierana',
	/*Messages for geo coordinates parsing*/
	'smw_err_latitude' => 'Wartości dla szerokości geograficznej (N, S) muszą być w zakresie od 0 do 90. "$1" nie spełnia tego warunku!',
	'smw_err_longitude' => 'Wartości dla długości geograficznej (E, W) muszą być w zakresie od 0 do 180. "$1" nie spełnia tego warunku!',
	'smw_err_noDirection' => '[Ojej! Coś jest nie tak z podaną wartością "$1"]',
	'smw_err_parsingLatLong' => '[Ojej! Coś jest nie tak z podaną wartością "$1". W tym miejscu oczekujemy wartości w rodzaju "1°2′3.4′′ W"!]',
	'smw_err_wrongSyntax' => '[Ojej! Coś jest nie tak z podaną wartością "$1". W tym miejscu oczekujemy wartości w rodzaju "1°2′3.4′′ W, 5°6′7.8′′ N"!]',
	'smw_err_sepSyntax' => 'Podana wartość "$1" wydaje się być poprawna, ale wartości dla długości i szerokości geograficznej powinny być oddzielone przy pomocy "," lub ";".',
	'smw_err_notBothGiven' => 'Musisz podać prawidłowe wartości zarówno dla długości (E, W) jak i szerokości (N, S)! Brakuje co najmniej jednej z nich!',
	/* additionals ... */
	'smw_label_latitude' => 'Długość:',
	'smw_label_longitude' => 'Szerokość:',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'E',
	'smw_abb_south' => 'S',
	'smw_abb_west' => 'W',
	/* some links for online maps; can be translated to different language versions of services, but need not*/
	'smw_service_online_maps' => " find&nbsp;maps|http://kvaleberg.com/extensions/mapsources/?params=\$1_\$3_\$5_\$7_\$2_\$4_\$6_\$8_region:EN_type:city\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	/*Messages for datetime parsing */
	'smw_nodatetime' => '[Ojej! Data "$1" nie została zrozumiana. Wsparcie dla dat jest jednak wciąż w fazie eksperymentalnej.]' //TODO: clumsy
);


/* private */ var $smwUserMessages = array(
	'smw_devel_warning' => 'Ta opcja jest obecnie w fazie rozwoju, może nie być w pełni funkcjonalna. Przed użyciem zabezpiecz swoje dane.', //TODO: "opcja"?
	// Messages for article pages of types, relations, and attributes
	'smw_type_header' => 'Attributes of type “$1”', // TODO translate
	'smw_typearticlecount' => 'Showing $1 attributes using this type.', // TODO translate
	'smw_attribute_header' => 'Pages using the attribute “$1”', // TODO translate
	'smw_attributearticlecount' => '<p>Showing $1 pages using this attribute.</p>', // TODO translate
	'smw_relation_header' => 'Pages using the relation “$1”', // TODO translate
	'smw_relationarticlecount' => '<p>Showing $1 pages using this relation.</p>', // TODO translate
	/*Messages for Export RDF Special*/
	'exportrdf' => 'Eksport stron do RDF', //name of this special
	'smw_exportrdf_docu' => '<p>Ta strona pozwala eksportować fragmenty artykułu w formacie RDF.  Aby wyeksportować artykuły, wpisz ich tytuły w poniższym polu tekstowym, po jednym tytule w wierszu.</p>',
	'smw_exportrdf_recursive' => 'Rekursywny eksport wszystkich powiązanych stron.  Zwróć uwagę, że wynik może być olbrzymi!', //TODO: "rekursywny"?
	'smw_exportrdf_backlinks' => 'Eksportuj także wszystkie strony, które odwołują się do eksportowanych stron.  Tworzy przeglądalny RDF.', //TODO: "przeglądalny"?
	/*Messages for Search Triple Special*/
	'searchtriple' => 'Proste szukanie semantyczne', //name of this special
	'smw_searchtriple_docu' => "<p>Wypełnij górny albo dolny wiersz formularza w celu wyszukania, odpowiednio, relacji albo atrybutów. Niektóre pola mogą pozostać puste w celu uzyskania większej liczby wyników. Jednakże, jeśli podana jest wartość atrybutu, podana musi być także jego nazwa. Jak zwykle, wartości atrybutów mogą być podawane wraz z jednostkami miary.</p>\n\n<p>Pamiętaj, że aby uzyskać wyniki, musisz kliknąć w odpowiedni przycisk. Naciśnięcie po prostu <i>Return</i> może wywołać inne szukanie niż zamierzałeś.</p>",
	'smw_searchtriple_subject' => 'Artykuł podmiotowy:',
	'smw_searchtriple_relation' => 'Nazwa relacji:',
	'smw_searchtriple_attribute' => 'Nazwa atrybutu:',
	'smw_searchtriple_object' => 'Artykuł przedmiotowy:',
	'smw_searchtriple_attvalue' => 'Wartość atrybutu:',
	'smw_searchtriple_searchrel' => 'Szukaj relacji',
	'smw_searchtriple_searchatt' => 'Szukaj atrybutów',
	'smw_searchtriple_resultrel' => 'Szukaj wyników (relacje)',
	'smw_searchtriple_resultatt' => 'Szukaj wyników (atrybuty)',
	/*Messages for Relations Special*/
	'relations' => 'Relacje',
	'smw_relations_docu' => 'W wiki istnieją następujące relacje.',
	// Messages for RelationsWithoutPage Special
	'relationswithoutpage' => 'Wanted relations', //TODO: translate
	'smw_relations_withoutpage' => 'The following relations do not have an explanatory page yet, though they are already used to describe other pages.', //TODO: translate
	/*Messages for Attributes Special*/
	'attributes' => 'Atrybuty',
	'smw_attributes_docu' => 'W wiki istnieją następujące atrybuty.',
	'smw_attr_type_join' => ' z $1',
	/*Messages for Unused Relations Special*/
	'unusedrelations' => 'Nieużywane relacje',
	'smw_unusedrelations_docu' => 'Następujące relacje posiadają własne strony, choć Żadna inna strona z nich nie korzysta.',
	/*Messages for Unused Attributes Special*/
	'unusedattributes' => 'Nieużywane atrybuty',
	'smw_unusedattributes_docu' => 'Następujące atrybuty posiadają własne strony, choć żadna inna strona z nich nie korzysta.',
	/* Messages for the refresh button */
	'tooltip-purge' => 'Kliknij tutaj, aby odświeżyć wszystkie zapytania i szablony na tej stronie',
	'purge' => 'Odśwież',
	/*Messages for Import Ontology Special*/
	'ontologyimport' => 'Importuj ontologię',
	'smw_oi_docu' => 'Ta strona specjalna pozwala na import ontologii.  Ontologie muszą być reprezentowane w odpowiednim formacie, opisanym na <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">stronie pomocy poświęconej importowi ontologii</a>.',
	'smw_oi_action' => 'Import',
	'smw_oi_return' => 'Powrót do <a href="$1">Special:OntologyImport</a>.',
	'smw_oi_noontology' => 'Nie podano ontologii, lub podana ontologia nie mogła być załadowana.',
	'smw_oi_select' => 'Wybierz zdania do importu, a następnie kliknij przycisk importu.',
	'smw_oi_textforall' => 'Nagłówek do dodania dla wszystkich importów (może być pusty):',
	'smw_oi_selectall' => 'Zaznacz lub odznacz wszystkie zdania',
	'smw_oi_statementsabout' => 'Zdania o',
	'smw_oi_mapto' => 'Mapuj encję na',
	'smw_oi_comment' => 'Dodaj następujący tekst:',
	'smw_oi_thisissubcategoryof' => 'Jest podkategorią',
	'smw_oi_thishascategory' => 'Jest częścią',
	'smw_oi_importedfromontology' => 'Import z ontologii',
	/*Messages for (data)Types Special*/
	'types' => 'Typy',
	'smw_types_docu' => 'Poniżej znajduje się lista wszystkich typów które mogą być przypisane atrybutom.  Każdy typ posiada artykuł, w którym mogą znajdować się dodatkowe informacje.',
	'smw_types_units' => 'Standardowa jednostka: $1; obsługiwane jednostki: $2',
	'smw_types_builtin' => 'Wbudowane typy',
	/*Messages for ExtendedStatistics Special*/
	'extendedstatistics' => 'Extended Statistics', //TODO:translate
	'smw_extstats_general' => 'General Statistics', //TODO:translate
	'smw_extstats_totalp' => 'Total number of pages:', //TODO:translate
	'smw_extstats_totalv' => 'Total number of views:', //TODO:translate
	'smw_extstats_totalpe' => 'Total number of page edits:', //TODO:translate
	'smw_extstats_totali' => 'Total number of images:', //TODO:translate
	'smw_extstats_totalu' => 'Total number of users:', //TODO:translate
	'smw_extstats_totalr' => 'Total number of relations:', //TODO:translate
	'smw_extstats_totalri' => 'Total number of relation instances:', //TODO:translate
	'smw_extstats_totalra' => 'Average number of instances per relation:', //TODO:translate
	'smw_extstats_totalpr' => 'Total number of pages about relations:', //TODO:translate
	'smw_extstats_totala' => 'Total number of attributes:', //TODO:translate
	'smw_extstats_totalai' => 'Total number of attribute instances:', //TODO:translate
	'smw_extstats_totalaa' => 'Average number of instances per attribute:', //TODO:translate
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Flawed Attributes',
	'smw_fattributes' => 'The pages listed below have an incorrectly defined attribute. The number of incorrect attributes is given in the brackets.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'URI Resolver', //TODO: translate
	'smw_uri_doc' => '<p>The URI resolver implements the <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. It takes care that humans don\'t turn into websites.</p>', //TODO: translate
	/*Messages for ask Special*/
	/*Messages for ask Special*/
	'ask' => 'Szukanie semantyczne',
	'smw_ask_docu' => '<p>Szukaj w wiki wpisując zapytanie w poniższe pole. Dalsze informacje znajdują się na <a href="$1">stronie pomocy poświęconej szukaniu semantycznemu</a>.</p>',
	'smw_ask_doculink' => 'Szukanie semantyczne',
	'smw_ask_sortby' => 'Sortuj po kolumnie',
	'smw_ask_ascorder' => 'Rosnąco',
	'smw_ask_descorder' => 'Malejąco',
	'smw_ask_submit' => 'Szukaj wyników',
	// Messages for search by relation Special
	'searchbyrelation' => 'Search by relation',  //TODO: translate
	'smw_tb_docu' => '<p>Search for all pages that have a certain relation to the given target page.</p>', //TODO: translate
	'smw_tb_notype' => '<p>Please enter a relation, or <a href="$2">view all links to $1.</a></p>', //TODO: translate
	'smw_tb_notarget' => '<p>Please enter a target page, or view all $1 relations.</p>', //TODO: translate
	'smw_tb_displayresult' => 'A list of all pages that have a relation $1 to the page $2.', //TODO: translate
	'smw_tb_linktype' => 'Relation', //TODO: translate
	'smw_tb_linktarget' => 'To', //TODO: translate
	'smw_tb_submit' => 'Find results', //TODO: translate
	// Messages for the search by attribute special
	'searchbyattribute' => 'Search by attribute', //TODO: translate
	'smw_sbv_docu' => '<p>Search for all pages that have a given attribute and value.</p>', //TODO: translate
	'smw_sbv_noattribute' => '<p>Please enter an attribute.</p>', //TODO: translate
	'smw_sbv_novalue' => '<p>Please enter a value, or view all attributes values for $1.</p>', //TODO: translate
	'smw_sbv_displayresult' => 'A list of all pages that have an attribute $1 with value $2.', //TODO: translate
	'smw_sbv_attribute' => 'Attribute', //TODO: translate
	'smw_sbv_value' => 'Value', //TODO: translate
	'smw_sbv_submit' => 'Find results', //TODO: translate
	// Messages for the browsing system
	'smwbrowse' => 'Browse article', //TODO: translate
	'smw_browse_article' => 'Enter the name of the article to start browsing from.', //TODO: translate
	'smw_browse_in' => 'Incoming', //TODO: translate
	'smw_browse_out' => 'Outgoing', //TODO: translate
	'smw_browse_docu' => '<p>Search for all properties of the given article.</p>', //TODO: translate
	'smw_browse_displayresult' => 'All incoming properties for the article $1. Click <span class="smwsearchicon">+</span> to explore connecting articles.', //TODO: translate
	'smw_browse_displayout' => 'All outgoing properties of the article $1. Click <span class="smwsearchicon">+</span> to explore connecting articles.', //TODO: translate
	'smw_browse_noout' => 'No outgoing properties found. Try the <a href="$1">incoming properties</a> instead.', //TODO: translate
	'smw_browse_noin' => 'No incoming properties found. Try the <a href="$1">outgoing properties</a> instead.', //TODO: translate
	'smw_browse_more' => '&#0133;', //TODO: translate
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'Poprzednie',
	'smw_result_next' => 'Następne',
	'smw_result_results' => 'Wyniki',
	'smw_result_noresults' => 'Niestety, brak wyników.'
);

/* private */ var $smwDatatypeLabels = array(
	'smw_string' => 'Łańcuch znaków',  // name of the string type
	'smw_int' => 'Liczba całkowita',  // name of the int type
	'smw_float' => 'Liczba zmiennoprzecinkowa',  // name of the floating point type
	'smw_length' => 'Długość',  // name of the length type
	'smw_area' => 'Powierzchnia',  // name of the area type
	'smw_geolength' => 'Geographic length',  // OBSOLETE name of the geolength type //TODO: translate
	'smw_geoarea' => 'Geographic area',  // OBSOLETE name of the geoarea type //TODO: translate
	'smw_geocoordinate' => 'Współrzędne geograficzne', // name of the geocoord type
	'smw_mass' => 'Masa',  // name of the mass type
	'smw_time' => 'Czas trwania',  // name of the time (duration) type
	'smw_temperature' => 'Temperatura',  // name of the temperature type
	'smw_datetime' => 'Data',  // name of the datetime (calendar) type
	'smw_email' => 'Email',  // name of the email (URI) type
	'smw_url' => 'URL',  // name of the URL type (string datatype property)
	'smw_uri' => 'URI',  // name of the URI type (object property)
	'smw_annouri' => 'Annotation URI'  // name of the annotation URI type (annotation property) //TODO: translate
);

/* private */ var $smwSpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Ma typ',
	SMW_SP_HAS_URI   => 'Równoważne URI',
	SMW_SP_IS_SUBRELATION_OF   => 'Jest podrelacją',
	SMW_SP_IS_SUBATTRIBUTE_OF   => 'Jest podatrybutem',
	SMW_SP_MAIN_DISPLAY_UNIT => 'Główna wyświetlana jednostka',
	SMW_SP_DISPLAY_UNIT => 'Wyświetlana jednostka',
	SMW_SP_IMPORTED_FROM => 'Zaimportowane z',
	SMW_SP_CONVERSION_FACTOR => 'Odpowiada',
	SMW_SP_SERVICE_LINK => 'Zapewnia usługę'
);


	/**
	 * Function that returns the namespace identifiers.
	 */
	function getNamespaceArray() {
		return array(
			SMW_NS_RELATION       => 'Relacja',
			SMW_NS_RELATION_TALK  => 'Dyskusja_relacji',
			SMW_NS_ATTRIBUTE      => 'Atrybut',
			SMW_NS_ATTRIBUTE_TALK => 'Dyskusja_atrybutu',
			SMW_NS_TYPE           => 'Typ',
			SMW_NS_TYPE_TALK      => 'Dyskusja_typu'
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
