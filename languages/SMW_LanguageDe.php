<?php
/**
 * @author Markus Krötzsch
 * 
 * Main translations:
 * "property" --> "Attribut"
 * "type" --> "Datentyp"
 * "special properties" --> "Besondere Attribute"
 * "query" --> "Anfrage"
 * "subquery" --> Teilanfrage
 * "printout statement" --> Ausgabeanweisung
 */


global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageDe extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => 'Bearbeitungshilfe für Attribute',
	'smw_viewasrdf' => 'RDF-Feed',
	'smw_finallistconjunct' => ' und', //used in "A, B, and C"
	'smw_factbox_head' => 'Fakten zu $1',
	'smw_isspecprop' => 'Dieses Attribut ist ein Spezialattribut in diesem Wiki.',
	'smw_isknowntype' => 'Dieser Datentyp gehört zu den Standardtypen in diesem Wiki.',
	'smw_isaliastype' => 'Dieser Datentyp ist ein Alias für den Typ “$1”.',
	'smw_isnotype' => 'Der Datentyp “$1” ist kein Standardtyp in diesem Wiki, und hat auch keine ausreichende Definition auf seiner Seite.',
	/*URIs that should not be used in objects in cases where users can provide URIs */
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'URIs aus dem Bereich „$1“ sind an dieser Stelle leider nicht verfügbar.',
	/*Messages and strings for inline queries*/
	'smw_iq_disabled' => "Semantische Anfragen sind in diesem Wiki zur Zeit nicht möglich.",
	'smw_iq_moreresults' => '&hellip; weitere Ergebnisse',
	'smw_iq_nojs' => 'Der Inhalt dieses Elementes kann mit einem Browser mit JavaScript-Unterstützung betrachtet werden.',
	'smw_iq_altresults' => 'Suchergebnisse als Liste anzeigen.', // available link when JS is disabled
	/*Messages and strings for ontology resued (import) */
	'smw_unknown_importns' => 'Für den Namensraum „$1“ sind leider keine Importfunktionen verfügbar.',
	'smw_nonright_importtype' => 'Das Element „$1“ kann nur für Seiten im Namensraum „$2“ verwendet werden.',
	'smw_wrong_importtype' => 'Das Element „$1“ kann nicht für Seiten im Namensraum „$2“ verwendet werden.',
	'smw_no_importelement' => 'Das Element „$1“ steht leider nicht zum Importieren zur Verfügung.',
	/*Messages and strings for basic datatype processing*/
	'smw_decseparator' => ',',
	'smw_kiloseparator' => '.',
	'smw_notitle' => '“$1” cannot be used as a page name in this wiki.', // TODO Translate
	'smw_unknowntype' => 'Dem Attribut wurde der unbekannte Datentyp „$1“ zugewiesen.',
	'smw_manytypes' => 'Dem Attribut wurden mehrere Datentypen zugewiesen.',
	'smw_emptystring' => 'Leere Zeichenfolgen werden nicht akzeptiert.',
	'smw_maxstring' => 'Die Zeichenkette „$1“ ist für diese Website zu lang.',
	'smw_notinenum' => '„$1“ gehört nicht zu den möglichen Werten dieses Attributs ($2).',
	'smw_noboolean' => '„$1“ ist kein Boolescher Wert (wahr/falsch).',
	'smw_true_words' => 'wahr,ja',	// comma-separated synonyms for boolean TRUE besides 'true' and '1'
	'smw_false_words' => 'falsch,nein',	// comma-separated synonyms for boolean FALSE besides 'false' and '0'
	'smw_nofloat' => '„$1“ ist keine Zahl.',
	'smw_infinite' => 'Die Zahl $1 ist zu lang.',
	'smw_infinite_unit' => 'Die Umrechnung in Einheit $1 ist nicht möglich: die Zahl ist zu lang.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'dieses Attribut unterstützt keine Umrechnung von Einheiten',
	'smw_unsupportedprefix' => 'Vorangestellte Zeichen bei Dezimalzahlen („$1“) werden nicht unterstützt.',
	'smw_unsupportedunit' => 'Umrechnung der Einheit „$1“ nicht unterstützt.',
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => 'Keine Zahl vor dem “$1”-Zeichen gefunden.', // $1 is something like ° TODO Translate
	'smw_bad_latlong' => 'Länge und Breite dürfen nur einmal und mit gültigen Werten angegeben werden.', // TODO Translate
	'smw_label_latitude' => 'Breite:',
	'smw_label_longitude' => 'Länge:',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'O',
	'smw_abb_south' => 'S',
	'smw_abb_west' => 'W',
	/* some links for online maps; can be translated to different language versions of services, but need not*/
	'smw_service_online_maps' => " Landkarten|http://tools.wikimedia.de/~magnus/geo/geohack.php?language=de&params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	/*Messages for datetime parsing */
	'smw_nodatetime' => 'Das Datum „$1“ wurde nicht verstanden. Die Unterstützung von Kalenderdaten ist zur Zeit noch experimentell.',
	// Errors and notices related to queries
	'smw_toomanyclosing' => 'In der Anfrage kommen zu viele „$1“ vor.',
	'smw_noclosingbrackets' => 'Ein Vorkommen von „[&#x005B;“ in der Anfrage wurde nicht durch ein entsprechendes „]]“ abgeschlossen.',
	'smw_misplacedsymbol' => 'Das Symbol „$1“ wurde an einer Stelle verwendet, wo es keinen Sinn macht.',
	'smw_unexpectedpart' => 'Der Teil „$1“ der Anfrage wurde nicht verstanden. Die Ergebnisse sind eventuell nicht wie erwartet.',
	'smw_emptysubquery' => 'Keine Bedingung in Teilanfrage.',
	'smw_misplacedsubquery' => 'Eine Teilanfrage wurde an einer Stelle verwendet, an der keine Teilanfragen vorkommen dürfen.',
	'smw_valuesubquery' => 'Teilanfragen werden für Werte des Attributs „$1“ werden nicht unterstützt.',
	'smw_overprintoutlimit' => 'Die Anfrage enhält zu viele Ausgabeanweisungen.',
	'smw_badprintout' => 'Eine Ausgabeanweisung wurde nicht verstanden.',
	'smw_badtitle' => 'Leider ist „$1“ als Seitentitel nicht zulässig.',
	'smw_badqueryatom' => 'Ein Teil „[&#x005B;&hellip;]]“ der Anfrage wurde nicht verstanden.',
	'smw_propvalueproblem' => 'Der Wert des Attributs „$1“ wurde nicht verstanden.',
	'smw_nodisjunctions' => 'Disjunktionen (ODER) in Anfragen sind in diesem Wiki nicht zulässig und ein Teil der Anfrage muss daher ignoriert werden ($1).',
	'smw_querytoolarge' => 'Die folgenden Anfragebedingungne konnten wegen den in diesem Wiki gültigen Beschränkungen für größe und Tiefe von Anfragen nicht berücksichtigt werden: $1.'
);

protected $m_UserMessages = array(
	'smw_devel_warning' => 'Diese Funktion befindet sich zur Zeit in Entwicklung und ist eventuell noch nicht voll einsatzfähig. Eventuell ist es ratsam, den Inhalt des Wikis vor der Benutzung dieser Funktion zu sichern.',
	// Messages for article pages of types, relations, and attributes
	'smw_type_header' => 'Attribute mit dem Datentyp „$1“',
	'smw_typearticlecount' => 'Es werden $1 Attribute mit diesem Datentyp angezeigt.',
	'smw_attribute_header' => 'Seiten mit dem Attribut „$1“',
	'smw_attributearticlecount' => '<p>Es werden $1 Seiten angezeigt, die dieses Attribut verwenden.</p>',
	/*Messages for Export RDF Special*/
	'exportrdf' => 'Seite als RDF exportieren', //name of this special
	'smw_exportrdf_docu' => '<p>Hier können Informationen über einzelne Seiten im RDF-Format abgerufen werden. Bitte geben Sie die Namen der gewünschten Seiten <i>zeilenweise</i> ein.</p>',
	'smw_exportrdf_recursive' => 'Exportiere auch alle relevanten Seiten rekursiv. Diese Einstellung kann zu sehr großen Ergebnissen führen!',
	'smw_exportrdf_backlinks' => 'Exportiere auch alle Seiten, die auf exportierte Seiten verweisen. Erzeugt RDF, das leichter durchsucht werden kann.',
	'smw_exportrdf_lastdate' => 'Exportiere keine Seiten, die seit dem angegebenen Zeitpunkt unverändert geblieben sind.',
	// Messages for Properties Special
	'properties' => 'Attribute',
	'smw_properties_docu' => 'In diesem Wiki gibt es die folgenden Attribute:',
	'smw_property_template' => '$1 mit Datentyp $2 ($3)', // <propname> of type <type> (<count>)
	'smw_propertylackspage' => 'Alle Attribute sollten durch eine Seite beschrieben werden!',
	'smw_propertylackstype' => 'Für dieses Attribut wurde kein Datentyp angegeben ($1 wird vorläufig als Typ angenommen).',
	'smw_propertyhardlyused' => 'Dieses Attribut wird im Wiki kaum verwendet!',
	// Messages for Unused Properties Special
	'unusedproperties' => 'Verwaiste Attribute',
	'smw_unusedproperties_docu' => 'Die folgenden Attributseiten existieren, obwohl sie nicht verwendet werden.',
	'smw_unusedproperty_template' => '$1 mit Datentyp $2', // <propname> of type <type>
	// Messages for Wanted Properties Special
	'wantedproperties' => 'Gewünschte Attribute',
	'smw_wantedproperties_docu' => 'Folgende Attribute haben bisher keine erläuterende Seite, obwohl sie bereits für die Beschreibung anderer Seiten verwendet werden.',
	'smw_wantedproperty_template' => '$1 ($2 Vorkommen)', // <propname> (<count> uses)
	/* Messages for the refresh button */
	'tooltip-purge' => 'Alle Anfrageergebnisse und Vorlagen auf dieser Seite auf den neuesten Stand bringen.',
	'purge' => 'aktualisieren',
	/*Messages for Import Ontology Special*/
	'ontologyimport' => 'Importiere Ontologie',
	'smw_oi_docu' => 'Diese Spezialseite erlaubt es, Informationen aus einer externen Ontologie zu importieren. Die Ontologie sollte in einem vereinfachten RDF-Format vorliegen. Weitere Informationen sind in der englischsprachigen <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">Dokumentation zum Ontologieimport</a> zu finden.',
	'smw_oi_action' => 'Importieren',
	'smw_oi_return' => 'Zurück zum <a href="$1">Ontologieimport</a>.',
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
	'types' => 'Datentypen',
	'smw_types_docu' => 'Die folgenden Datentypen können Attributen zugewiesen werden. Jeder Datentyp hat eine eigene Seite, auf der genauere Informationen eingetragen werden können.',
	'smw_typeunits' => 'Maßeinheiten des Datentyps “$1”: $2',
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => 'Statistik über semantische Daten',
	'smw_semstats_text' => 'In diesem Wiki wurden <b>$1</b> Werte für insgesamt <b>$2</b> verschiedene <a href="$3">Attribute</a> eingegeben. <b>$4</b> Attribute haben eine eigene Seite und der gewünschte Datentyp ist für <b>$5</b> von diesen angegeben worden. Einige der existierenden Attribute können <a href="$6">verwaiste Attribute</a> sein. Attribute, für die noch eine Seite angelegt werden sollte, sind in der <a href="$7">Liste der gewünschten Attribute</a> aufgeführt.',
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Fehlerhafte Attribute',
	'smw_fattributes' => 'Die unten aufgeführten Seiten enthalten fehlerhafte Attribute. Die Anzahl der fehlerhaften Attribute ist in den Klammern angegeben.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'URI-Auflöser',
	'smw_uri_doc' => '<p>Der URI-Auflöser setzt die Empfehlungen »<a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>« um. Er sorgt dafür, dass Menschen nicht zu Webseiten werden.</p>',
	/*Messages for ask Special*/
	'ask' => 'Semantische Suche',
	'smw_ask_doculink' => 'Semantische Suche',
	'smw_ask_sortby' => 'Sortiere nach Spalte (optional)',
	'smw_ask_ascorder' => 'Aufsteigend',
	'smw_ask_descorder' => 'Absteigend',
	'smw_ask_submit' => 'Finde Ergebnisse',
	'smw_ask_editquery' => '[Anfrage bearbeiten]',
	'smw_ask_hidequery' => 'Anfrage ausblenden',
	'smw_ask_help' => 'Hilfe',
	'smw_ask_queryhead' => 'Anfrage',
	'smw_ask_printhead' => 'Zusätzliche Ausgaben (optional)',
	// Messages for the search by property special
	'searchbyproperty' => 'Suche mittels Attribut',
	'smw_sbv_docu' => '<p>Diese Spezialseite findet alle Seiten, die einen bestimmten Wert für das angegebene Attribut haben.</p>',
	'smw_sbv_noproperty' => '<p>Bitte den Namen eines Attributs eingeben</p>',
	'smw_sbv_novalue' => '<p>Bitte den gewünschten Wert eingeben oder alle Werte für das Attribut $1 ansehen.</p>',
	'smw_sbv_displayresult' => 'Eine Liste aller Seiten, die ein Attribut $1 mit dem Wert $2 haben.',
	'smw_sbv_property' => 'Attribut',
	'smw_sbv_value' => 'Wert',
	'smw_sbv_submit' => 'Finde Ergebnisse',
	// Messages for the browsing system
	'browse' => 'Wiki browsen',
	'smw_browse_article' => 'Bitte geben Sie den Titel einer Seite ein.',
	'smw_browse_go' => 'Los',
	'smw_browse_more' => '&hellip;',
	// Messages for the page property special
	'pageproperty' => 'Attributswerte einer Seite',
	'smw_pp_docu' => 'Suche nach allen Werten, die ein bestimmtes Attribut für die angegebene Seite hat.',
	'smw_pp_from' => 'Seite',
	'smw_pp_type' => 'Attribut',
	'smw_pp_submit' => 'Ergebnisse anzeigen',
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'Zurück',
	'smw_result_next' => 'Vorwärts',
	'smw_result_results' => 'Ergebnisse',
	'smw_result_noresults' => 'Keine Ergebnisse gefunden.'
);

protected $m_DatatypeLabels = array(
	'_wpg' => 'Seite', // name of page datatype
	'_str' => 'Zeichenkette',  // name of the string type
	'_txt' => 'Text',  // name of the text type
	//'_boo' => 'Wahrheitswert',  // name of the boolean type
	'_num' => 'Zahl', // name for the datatype of numbers
	'_geo' => 'Geografische Koordinaten', // name of the geocoord type
	'_tem' => 'Temperatur',  // name of the temperature type
	'_dat' => 'Datum',  // name of the datetime (calendar) type
	'_ema' => 'Email',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'URI-Annotation'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	'Ganze Zahl'            => '_num',
	'Dezimalzahl'           => '_num',
	'Aufzählung'            => '_str',
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
	SMW_SP_HAS_TYPE  => 'Datentyp',
	SMW_SP_HAS_URI   => 'Gleichwertige URI',
	SMW_SP_SUBPROPERTY_OF => 'Unterattribut von',
	SMW_SP_DISPLAY_UNITS => 'Einheiten',
	SMW_SP_IMPORTED_FROM => 'Importiert aus',
	SMW_SP_CONVERSION_FACTOR => 'Entspricht',
	SMW_SP_SERVICE_LINK => 'Bietet Service',
	SMW_SP_POSSIBLE_VALUE => 'Erlaubt Wert'
);

protected $m_SpecialPropertyAliases = array(
	'Hat Datentyp'     => SMW_SP_HAS_TYPE,
	'Ausgabeeinheit'   => SMW_SP_DISPLAY_UNITS,
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
	SMW_NS_RELATION       => "Relation",
	SMW_NS_RELATION_TALK  => "Relation_Diskussion",
	SMW_NS_PROPERTY       => "Attribut",
	SMW_NS_PROPERTY_TALK  => "Attribut_Diskussion",
	SMW_NS_TYPE           => "Datentyp",
	SMW_NS_TYPE_TALK      => "Datentyp_Diskussion"
);

protected $m_NamespaceAliases = array(
	// support English aliases for namespaces
	//'Relation'      => SMW_NS_RELATION,
	'Relation_talk' => SMW_NS_RELATION_TALK,
	'Property'      => SMW_NS_PROPERTY,
	'Property_talk' => SMW_NS_PROPERTY_TALK,
	'Type'          => SMW_NS_TYPE,
	'Type_talk'     => SMW_NS_TYPE_TALK
);

}


