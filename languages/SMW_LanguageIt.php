<?php
/**
 * @author Davide Eynard, David Laniado
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageIt extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => 'Aiuto sulla modifica delle propriet&agrave;', 
	'smw_viewasrdf' => 'Feed RDF ',
	'smw_finallistconjunct' => ' e', //used in "A, B, and C"
	'smw_factbox_head' => 'Fatti riguardanti $1',
	'smw_isspecprop' => 'Questa propriet&agrave; &egrave; una propriet&agrave; speciale all\'interno di questo wiki.',
	'smw_isknowntype' => 'Questo tipo &egrave; fra i tipi di dato standard di questo wiki',
	'smw_isaliastype' => 'Questo tipo &egrave; un alias per il tipo di dato “$1”.',
	'smw_isnotype' => 'Il tipo “$1” non &egrave; un tipo di dato standard nel wiki, n&eacute; &egrave; stato ancora definito dall\'utente.',
	// URIs that should not be used in objects in cases where users can provide URIs
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => 'Spiacenti. Gli URI del tipo “$1” non sono consentiti.',
	// Link to RSS feeds
	'smw_rss_link' => 'RSS',
	// Messages and strings for inline queries
	'smw_iq_disabled' => 'Spiacenti. Le query semantiche sono state disabilitate per questo wiki.',
	'smw_iq_moreresults' => '&hellip; risultati successivi',
	'smw_iq_nojs' => 'Per favore, usate un browser che supporti Javascript per visualizzare questo elemento.',
	'smw_iq_altresults' => 'Visualizza direttamente l\'elenco dei risultati.', // available link when JS is disabled
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => 'Le funzioni di importazione non sono disponibili per il namespace “$1”.',
	'smw_nonright_importtype' => '$1 pu&ograve; essere utilizzato solo per pagine con namespace “$2”.',
	'smw_wrong_importtype' => '$1 non pu&ograve; essere utilizzate per pagine nel namespace “$2”.',
	'smw_no_importelement' => 'L\'elemento “$1” non &egrave; disponibile per l\'importazione.',
	// Messages and strings for basic datatype processing
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_notitle' => '“$1” non pu&ograve; essere utilizzato come nome di una pagina all\'interno di questo wiki.',
	'smw_unknowntype' => '&Egrave; stato definito un tipo non supportato “$1” per la propriet&agrave;.',
	'smw_manytypes' => '&Egrave; stato definito pi&ugrave; di un tipo per la propriet&agrave;.',
	'smw_emptystring' => 'Le stringhe vuote non sono accettate.',
	'smw_maxstring' => 'La stringa $1 &egrave; troppo lunga per {{SITENAME}}.',
	'smw_notinenum' => '“$1” non &egrave; nella lista dei valori possibili ($2) per questa propriet&agrave;.',
	'smw_noboolean' => '“$1” non &egrave; riconosciuto come valore Booleano (vero/falso).',
	'smw_true_words' => 'vero,v,si,s,true,t,yes,y', // comma-separated synonyms for Boolean TRUE besides '1', primary value first
	'smw_false_words' => 'falso,f,no,n,false', // comma-separated synonyms for Boolean FALSE besides '0', primary value first
	'smw_nofloat' => '“$1” non &egrave; un numero.',
	'smw_infinite' => 'I numeri grandi come “$1” non sono supportati su {{SITENAME}}.',
	'smw_infinite_unit' => 'La conversione nell\'unit&agrave; di misura “$1” ha generato un numero che &egrave; troppo grande per {{SITENAME}}.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'this property supports no unit conversion',
	'smw_unsupportedprefix' => 'I prefissi per i numeri (“$1”) non sono supportati.',
	'smw_unsupportedunit' => 'La conversione per l\'unit&agrave; di misura “$1” non &egrave; supportata.',
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => 'Non &egrave; stato trovato nessun numero prima del simbolo “$1”.', // $1 is something like °
	'smw_bad_latlong' => 'Latitudine e longitudine devono essere inserite solo una volta, e con coordinate valide.',
	'smw_abb_north' => 'N',
	'smw_abb_east' => 'E',
	'smw_abb_south' => 'S',
	'smw_abb_west' => 'O',
	'smw_label_latitude' => 'Latitudine:',
	'smw_label_longitude' => 'Longitudine:',
	// some links for online maps; can be translated to different language versions of services, but need not
	'smw_service_online_maps' => " Find&nbsp;online&nbsp;maps|http://tools.wikimedia.de/~magnus/geo/geohack.php?params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	// Messages for datetime parsing
	'smw_nodatetime' => 'Non &egrave; stato possibile comprendere la data “$1” (il supporto per le date &egrave; ancora sperimentale).',
	// Errors and notices related to queries
	'smw_toomanyclosing' => 'Sembrano esserci troppe ripetizioni di “$1” all\'interno della query.',
	'smw_noclosingbrackets' => 'Alcune "[&#x005B;” all\'interno della query non sono state chiuse con le corrispondenti “]]”.',
	'smw_misplacedsymbol' => 'Il simbolo “$1” &grave; stato usato in un punto in cui &egrave; inutile.',
	'smw_unexpectedpart' => 'Non &egrave; stato possibile comprendere la parte “$1” della query. Il risultato potrebbe essere diverso da quello atteso.',
	'smw_emptysubquery' => 'Qualche subquery ha una condizione non valida.',
	'smw_misplacedsubquery' => 'Qualche subquery &egrave; stata utilizzata in una posizione in cui non era consentito.',
	'smw_valuesubquery' => 'Le subquery non sono supportate per i valori della propriet&agrave; “$1”.',
	'smw_overprintoutlimit' => 'La query contiene troppe richieste di printout.',
	'smw_badprintout' => 'Comando print malformato all\'interno della query.',
	'smw_badtitle' => 'Spiacenti, “$1” non &egrave; un titolo valido.',
	'smw_badqueryatom' => 'Non &egrave; stato possibile comprendere parte “[&#x005B;&hellip;]]” della query.',
	'smw_propvalueproblem' => 'Non &egrave; stato possibile comprendere il valore della propriet&agrave; “$1”.',
	'smw_nodisjunctions' => 'La disgiunzione all\'interno delle query non &egrave; supportata in questo wiki, quindi parte della query &egrave; stata ignorata ($1).',
	'smw_querytoolarge' => 'Le seguenti condizioni all\'interno della query non sono state considerate a causa delle restrizioni di dimensione o profondit&agrave; delle query impostate per questo wiki: $1.'
);


protected $m_UserMessages = array(
	'smw_devel_warning' => 'Questa funzione &egrave; attualmente in fase di sviluppo e potrebbe non essere completamente funzionante: si consiglia di eseguire un backup dei dati prima di usarla.',
	// Messages for pages of types and properties
	'smw_type_header' => 'Propriet&agrave; del tipo “$1”',
	'smw_typearticlecount' => 'Visualizzazione di $1 propriet&agrave; che usano questo tipo.', 
	'smw_attribute_header' => 'Pagine che usano la propriet&agrave; “$1”',
	'smw_attributearticlecount' => '<p>Visualizzazione di $1 pagine che usano questa propriet&agrave;.</p>', 
	// Messages used in RSS feeds
	'smw_rss_description' => '$1 RSS feed',
	// Messages for Export RDF Special
	'exportrdf' => 'Esporta le pagine in RDF', //name of this special
	'smw_exportrdf_docu' => '<p>Questa pagina consente di ottenere dati da una pagina in formato RDF. Per esportare delle pagine, inseritene i titoli nella casella di testo sottostante, un titolo per riga.</p>',
	'smw_exportrdf_recursive' => 'Esporta ricorsivamente tutte le pagine correlate. Nota: il risultato potrebbe essere molto grande!',
	'smw_exportrdf_backlinks' => 'Esporta anche le pagine che si riferiscono a quelle esportate. Genera un RDF navigabile.',
	'smw_exportrdf_lastdate' => 'Non esportare le pagine che non hanno sub&igrave;to modifiche dal momento specificato.',
	// Messages for Properties Special
	'properties' => 'Propriet&agrave;',
	'smw_properties_docu' => 'Le seguenti propriet&agrave; sono utilizzate all\'interno del wiki.',
	'smw_property_template' => '$1 di tipo $2 ($3)', // <propname> of type <type> (<count>)
	'smw_propertylackspage' => 'Tutte le propriet&agrave; dovrebbero essere descritte da una pagina!',
	'smw_propertylackstype' => 'Non &egrave; stato specificato nessun tipo per questa propriet&agrave; (per il momento si suppone sia di tipo $1).',
	'smw_propertyhardlyused' => 'Questa propriet&agrave; non &egrave; quasi mai usata nel wiki!',
	// Messages for Unused Properties Special
	'unusedproperties' => 'Propiet&agrave; non utilizzate',
	'smw_unusedproperties_docu' => 'Le seguenti propriet&agrave; esistono nonostante nessun\'altra pagina ne faccia uso.',
	'smw_unusedproperty_template' => '$1 di tipo $2', // <propname> of type <type>	
	// Messages for Wanted Properties Special
	'wantedproperties' => 'Propriet&agrave; senza descrizione',
	'smw_wantedproperties_docu' => 'Le seguenti propriet&agrave; sono usate nel wiki ma non hanno ancora una pagina che le descriva.',
	'smw_wantedproperty_template' => '$1 ($2 usi)', // <propname> (<count> uses)
	// Messages for the refresh button
	'tooltip-purge' => 'Clicca qui per riaggiornare tutte le query e i template di questa pagina',
	'purge' => 'Aggiorna',
	// Messages for Import Ontology Special
	'ontologyimport' => 'Importa ontologia',
	'smw_oi_docu' => 'Questa pagina speciale permette di importare ontologie. Le ontologie devono seguire un certo formato, specificato nella <a href="http://semantic-mediawiki.org/index.php/Help:Ontology_import">pagina di aiuto per l\'importazione di ontologie (in inglese)</a>.',
	'smw_oi_action' => 'Importa',
	'smw_oi_return' => 'Ritorna a <a href="$1">Special:OntologyImport</a>.',
	'smw_oi_noontology' => 'Nessuna ontologia fornita, o non &egrave; stato possibile caricare l\'ontologia.',
	'smw_oi_select' => 'Per favore selezionare le asserzioni da importare, e poi cliccare il tasto di importazione.',
	'smw_oi_textforall' => 'Testo di intestazione da aggiungere a tutti gli import (pu&ograve; essere vuoto):',
	'smw_oi_selectall' => 'Seleziona o deseleziona tutte le asserzioni',
	'smw_oi_statementsabout' => 'Asserzioni su',
	'smw_oi_mapto' => 'Mappa entit&agrave; con',
	'smw_oi_comment' => 'Aggiungere il testo seguente:',
	'smw_oi_thisissubcategoryof' => 'Sottoclasse di',
	'smw_oi_thishascategory' => '&Egrave; parte di',
	'smw_oi_importedfromontology' => 'Importa da ontologia',
	// Messages for (data)Types Special
	'types' => 'Tipi',
	'smw_types_docu' => 'La seguente &egrave; una lista di tutti i tipi di dati che possono essere assegnati alle propiet&agrave;. Ogni tipo di dato ha una pagina dove si possono trovare informazioni aggiuntive.',
	'smw_typeunits' => 'Unit&agrave; di misura di tipo “$1”: $2',
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => 'Statistiche Semantiche',
	'smw_semstats_text' => 'Questo wiki contiene <b>$1</b> valori di propriet&agrave; per un totale di <b>$2</b> differenti <a href="$3">propriet&agrave;</a>. <b>$4</b> propriet&agrave; hanno una propria pagina, e il tipo di dato inteso &egrave; specificato per <b>$5</b> di queste. Alcune delle propriet&agrave; esistenti possono essere <a href="$6">propriet&agrave; non utilizzate</a>.  Le propriet&agrave; che ancora non hanno una pagina si possono trovare nella <a href="$7">lista delle propriet&agrave; senza descrizione</a>.',
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Propriet&agrave; scorrette',
	'smw_fattributes' => 'Le pagine elencate di seguito hanno una propriet&agrave; definita in modo non corretto. Il numero di propriet&agrave; incorrette &egrave; indicato fra parentesi.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'Risolutore di URI',
	'smw_uri_doc' => '<p>Il risolutore di URI implementa il <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. Fa in modo che gli esseri umani non diventino siti Web.',
	// Messages for ask Special
	'ask' => 'Ricerca semantica',
	'smw_ask_doculink' => 'Ricerca semantica',
	'smw_ask_sortby' => 'Ordina per colonna (opzionale)',
	'smw_ask_ascorder' => 'Crescente',
	'smw_ask_descorder' => 'Decrescente',
	'smw_ask_submit' => 'Trova risultati',
	'smw_ask_editquery' => '[Modifica query]',
	'smw_ask_hidequery' => 'Nascondi query',
	'smw_ask_help' => 'Help sulle query',
	'smw_ask_queryhead' => 'Query',
	'smw_ask_printhead' => 'Output aggiuntivi (opzionali)',
	// Messages for the search by property special
	'searchbyproperty' => 'Cerca per propriet&agrave;',
	'smw_sbv_docu' => '<p>Cerca tutte le pagine che hanno propriet&agrave; e valore specificati.</p>',
	'smw_sbv_noproperty' => '<p>Per favore inserire una propriet&agrave;.</p>',
	'smw_sbv_novalue' => '<p>Per favore inserire un valore valido per la propriet&agrave;, o visualizzare tutti i valori di propriet&agrave; per “$1.”</p>',
	'smw_sbv_displayresult' => 'Lista di tutte le pagine che hanno propriet&agrave; “$1” con valore “$2”',
	'smw_sbv_property' => 'Propriet&agrave;',
	'smw_sbv_value' => 'Valore',
	'smw_sbv_submit' => 'Trova risultati',
	// Messages for the browsing special
	'browse' => 'Esplora il wiki',
	'smw_browse_article' => 'Inserire il nome della pagina da cui iniziare l\'esplorazione',
	'smw_browse_go' => 'Vai',
	'smw_browse_more' => '&hellip;',
	// Messages for the page property special
	'pageproperty' => 'Ricerca propriet&agrave; della pagina',
	'smw_pp_docu' => 'Cerca tutti i valori che soddisfano una propriet&agrave; su una data pagina. Inserire sia la pagina sia la propriet&agrave;',
	'smw_pp_from' => 'Da pagina',
	'smw_pp_type' => 'Propriet&agrave;',
	'smw_pp_submit' => 'Trova risultati',
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => 'Precedente',
	'smw_result_next' => 'Successivo',
	'smw_result_results' => 'Risultati',
	'smw_result_noresults' => 'Spiacenti, nessun risultato.'
);

protected $m_DatatypeLabels = array(
	'_wpg' => 'Pagina',  // name of page datatypee
	'_str' => 'Stringa',  //name of the string type
	'_txt' => 'Testo',   // name of the text type
	'_boo' => 'Booleano',  // name of the boolean type
	'_num' => 'Numero',  // name for the datatype of numbers
	'_geo' => 'Coordinate geografiche',  // name of the geocoord type
	'_tem' => 'Temperatura',  // name of the temperature type
	'_dat' => 'Data',  // name of the datetime (calendar) type
	'_ema' => 'Email',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'Annotation URI' // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'         => '_uri',
	'Float'       => '_num',
	'Integer'     => '_num',
	'Intero'      => '_num',
	'Enumeration' => '_str',
	'Enumerazione'=> '_str'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Ha tipo', //'Has type',
	SMW_SP_HAS_URI   => 'URI equivalente', //'Equivalent URI',
	SMW_SP_SUBPROPERTY_OF => 'Sottopropriet&agrave; di', // 'Subproperty of',
	SMW_SP_DISPLAY_UNITS => 'Display units', //TODO
	SMW_SP_IMPORTED_FROM => 'Importato da', // 'Imported from',
	SMW_SP_CONVERSION_FACTOR => 'Corrisponde a ', // 'Corresponds to',
	SMW_SP_SERVICE_LINK => 'Fornisce servizio', // 'Provides service',
	SMW_SP_POSSIBLE_VALUE => 'Ammette valore', //'Allows value'
);

protected $m_SpecialPropertyAliases = array(
	'Display unit' => SMW_SP_DISPLAY_UNITS
);

protected $m_Namespaces = array(
	SMW_NS_RELATION       => 'Relation',
	SMW_NS_RELATION_TALK  => 'Relation_talk',
	SMW_NS_PROPERTY       => 'Property',
	SMW_NS_PROPERTY_TALK  => 'Property_talk',
	SMW_NS_TYPE           => 'Type',
	SMW_NS_TYPE_TALK      => 'Type_talk'
);

}


