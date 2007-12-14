<?php
/**
 * @author Markus Krötzsch 翻譯:張致信(Translation: Roc Michael Email:roc.no1@gmail.com) 2007-10-20
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageZh_tw extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => '與關聯(relations)及屬性(attributes)有關的編輯協助',  //(Editing help on relations and attributes)
	'smw_viewasrdf' => 'RDF feed',
	'smw_finallistconjunct' => '， 和',    //(, and) used in "A, B, and C"
	'smw_factbox_head' => '關於$1 的小檔案', //(Facts about $1)
	'smw_isspecprop' => '在此wiki系統內，此一性質為一種特殊性質', //(This property is a special property in this wiki.)
	'smw_isknowntype' => '此一型態係為這個wiki系統內的標準的資料型態之一',//(This type is among the standard datatypes of this wiki.)
	'smw_isaliastype' => '此一型態係為資料型態“$1”的別稱',//(This type is an alias for the datatype “$1”.)
	'smw_isnotype' => '在此wiki系統內，此一“$1”型態並非是一項標準的資料型態，並且尚未被用戶賦予其定義',
	//(This type “$1” is not a standard datatype in the wiki, and has not been given a user definition either.) URIs that should not be used in objects in cases where users can provide URIs
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => '抱歉，在此處無法取得從“$1”範圍的，URIs. (譯註原文為：Sorry, URIs from the range “$1” are not available in this place.)', 	  
	// Messages and strings for inline queries
	'smw_iq_disabled' => "抱歉，線上查詢在此wiki已被設定為無效", //"Sorry. Semantic queries have been disabled for this wiki."
	'smw_iq_moreresults' => '&hellip; 進階查詢',	//'&hellip; further results'
	'smw_iq_nojs' => '請使用內建JavaScript的瀏覽器以瀏覽此元素.',	//'Use a JavaScript-enabled browser to view this element.' // TODO: check translation (Markus pruned it ;)
	'smw_iq_altresults' => 'Browse the result list directly.', // available link when JS is disabled // TODO: translate
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => '匯入功能對“$1”的名字空間無效”.', 	//'Import functions are not avalable for namespace “$1”.
	'smw_nonright_importtype' => '$1僅能用於名字空間為“$2”的頁面。',	//'$1 can only be used for pages with namespace “$2”.'
	'smw_wrong_importtype' => '$1無法用於名字空間為“$2”的頁面。',	//'$1 can not be used for pages in the namespace “$2”.'
	'smw_no_importelement' => '無法匯入“$1”元素',	//'Element “$1” not available for import.'
	// Messages and strings for basic datatype processing
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_notitle' => '在此wiki內，是無法用“$1”來當作頁面名稱的',	//'“$1” cannot be used as a page name in this wiki.'
	'smw_unknowntype' => '不支援為性質所定義的“$1”形態。',	//'Unsupported type “$1” defined for property.'
	'smw_manytypes' => '定義此性質的型態已超過了一種以上。',	//'More than one type defined for property.'
	'smw_emptystring' => '不接受空白字串。',	//'Empty strings are not accepted.'
	'smw_maxstring' => '對本站而言，$1所代表的字串太長了。(譯註原文為：String representation $1 is too long for this site.)',	//'String representation $1 is too long for this site.'
	'smw_notinenum' => '“$1” 並非在此屬性有可能的值 ($2)的清單之中',	// '“$1” is not in the list of possible values ($2) for this property.'
	'smw_noboolean' => '“$1”無法被視為布林值(true/false)。',	//'“$1” is not recognized as a boolean (true/false) value.'
	'smw_true_words' => '是,t,yes,y,true',	// comma-separated synonyms for boolean TRUE besides '1'
	'smw_false_words' => '否,f,no,n,false',	// comma-separated synonyms for boolean FALSE besides '0'
	'smw_nofloat' => '“$1” 並非為是數字',	// '“$1” is no number.'
	'smw_infinite' => '在此站內並不支援像是“$1”如此龐大的數目字。',	//'Numbers as large as “$1” are not supported on this site.'
	'smw_infinite_unit' => '對此站而言轉換“$1”單位所產生的數目字過於龐大。',	// 'Conversion into unit “$1” resulted in a number that is too large for this site.'
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'this property supports no unit conversion',
	'smw_unsupportedprefix' => '數字(“$1”) 的字首目前尚未被支援',	//'Prefixes for numbers (“$1”) are not supported.'
	'smw_unsupportedunit' => '單位轉換無法適用於“$1”此一單位',	//'Unit conversion for unit “$1” not supported.'
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => '在“$1”此一單位之前並無數目字', //'No number found before the symbol “$1”.'	// $1 is something like °
	'smw_bad_latlong' => '緯度和經度只能以有效的座標值標示一次',	//'Latitude and longitude must be given only once, and with valid coordinates.'
	'smw_abb_north' => 'N',    //北
	'smw_abb_east' => 'E',     //東
	'smw_abb_south' => 'S',    //南
	'smw_abb_west' => 'W',     //西
	'smw_label_latitude' => '緯度：',	// 'Latitude:'
	'smw_label_longitude' => '經度：',	//'Longitude:'
	// some links for online maps; can be translated to different language versions of services, but need not
	'smw_service_online_maps' => " find&nbsp;maps|http://tools.wikimedia.de/~magnus/geo/geohack.php?params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",	//" find&nbsp;maps|http://tools.wikimedia.de/~magnus/geo/geohack.php?params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6"
	// Messages for datetime parsing
	'smw_nodatetime' => '日期值“$1”無法被識別，對日期值的支援目前尚屬實驗性質。',	//'The date “$1” was not understood (support for dates is still experimental).'
	// Errors and notices related to queries
	'smw_toomanyclosing' => '在此查詢中“$1”顯然出現太多次了',	// 'There appear to be too many occurrences of “$1” in the query.'
	'smw_noclosingbrackets' => '在您的查詢中“[&#x005B;” 並未以對應的“]]”來予以封閉',	// 'Some use of “[&#x005B;” in your query was not closed by a matching “]]”.'
	'smw_misplacedsymbol' => '“$1”此一符號用於某項無用之處',	//'The symbol “$1” was used in a place where it is not useful.'
	'smw_unexpectedpart' => '查詢的“$1”部份無法被識別，可能會出現預料之外的結果',	// 'The part “$1” of the query was not understood. Results might not be as expected.'
	'smw_emptysubquery' => '某些子查詢並不具備有效的查詢條件',	//'Some subquery has no valid condition.'
	'smw_misplacedsubquery' => '某些子查詢被用在不宜於使用子查詢之處',	//'Some subquery was used in a place where no subqueries are allowed.'
	'smw_valuesubquery' => '“$1”質性的值並不適用於子查詢',	//'Subqueries not supported for values of property “$1”.',	//'Subqueries not supported for values of property “$1”.'
	'smw_overprintoutlimit' => '此查詢含有太多的列印要求(譯註原文為：The query contains too many printout requests.)',	
	'smw_badprintout' => '在此查詢中，有些列印敘述已被弄錯了(譯註原文為：Some print statement in the query was misshaped.)',
	'smw_badtitle' => '抱歉！“$1” 並非是有效的頁面名稱',	//'Sorry, but “$1” is no valid page title.',
	'smw_badqueryatom' => '在此查詢中，部份的“[#x005B;&hellip]]”無法被識別。(譯註原文為：Some part “[#x005B;&hellip]]” of the query was not understood.)',	//'Some part “[#x005B;&hellip]]” of the query was not understood.',
	'smw_propvalueproblem' => '質性“$1”的值無法被識別',	//'The value of property “$1” was not understood.',
	'smw_nodisjunctions' => '在此wiki系統內分開查詢是不被支援的，並有部份查詢已被遺漏 ($1)。(譯註原文為：Disjunctions in queries are not supported in this wiki and part of the query was dropped ($1).)',
	'smw_querytoolarge' => '基於此wiki系統對查詢的規模及在深度方面的限制，以下的查詢條件無法被接受：$1',	//The following query conditions could not be considered due to the wikis restrictions in query size or depth: $1.
);


protected $m_UserMessages = array(
	'smw_devel_warning' => '此元件尚於開發中，也許無法完成發揮功效，在使用它之前，請先備份您的資料',	//'This feature is currently under development, and might not be fully functional. Backup your data before using it.',
	// Messages for pages of types and properties
	'smw_type_header' => '“$1”型態的性質',	//'Properties of type “$1”',
	'smw_typearticlecount' => '以此型態顯示 $1 性質',	//'Showing $1 properties using this type.',
	'smw_attribute_header' => '使用性質“$1”的頁面',	//'Pages using the property “$1”',
	'smw_attributearticlecount' => '<p>以此性質顯示$1頁面.</p>',	//'<p>Showing $1 pages using this property.</p>',
	// Messages for Export RDF Special
	'exportrdf' => '輸出頁面至RDF 。',	//'Export pages to RDF', //name of this special
	'smw_exportrdf_docu' => '<p>此一頁面可讓您獲取RDF格式頁面的資料，要輸出頁面，請在下方的文字框內鍵入頁面的抬頭，一項一行。</p>',	//'<p>This page allows you to obtain data from a page in RDF format. To export pages, enter the titles in the text box below, one title per line.</p>',
	'smw_exportrdf_recursive' => '逐項輸出所有的相關的頁面，請注意輸出的結果可能頗為龐大。',	//'Recursively export all related pages. Note that the result could be large!',
	'smw_exportrdf_backlinks' => '並且輸出與輸出頁面有關的頁面，產生可供人閱讀的RDF。(browsable RDF)',	//'Also export all pages that refer to the exported pages. Generates browsable RDF.',
	'smw_exportrdf_lastdate' => '無須輸出那些在所設之時間點以後就未再被更動過的頁面',	//'Do not export pages that were not changed since the given point in time.',
	// Messages for Properties Special
	'properties' => '性質',	//'Properties',
	'smw_properties_docu' => '以下的性質已被用於此wiki內',	//'The following properties are used in the wiki.',
	'smw_property_template' => ' 型態 $2 ($3)的$1 (譯註原文為：$1 of type $2 ($3) )',	//'$1 of type $2 ($3)', // <propname> of type <type> (<count>)
	'smw_propertylackspage' => '所有的性質應以某一頁面加以描述。(譯註原文為：All properties should be described by a page!)',	//'All properties should be described by a page!',
	'smw_propertylackstype' => '此一性質尚未被指定形態，先暫定為$1型態。(譯註原文為：No type was specified for this property (assuming type $1 for now).)',	//'No type was specified for this property (assuming type $1 for now).',
	'smw_propertyhardlyused' => '此一性質難以用於此wiki內',	//'This property is hardly used within the wiki!',
	// Messages for Unused Properties Special
	'unusedproperties' => '未使用的性質',	//'Unused Properties',
	'smw_unusedproperties_docu' => '下方的性質雖已存在，但無其他的頁面使用它們。',	//'The following properties exist although no other page makes use of them.',
	'smw_unusedproperty_template' => '$2型態的$1',	//'$1 of type $2', // <propname> of type <type>
	// Messages for Wanted Properties Special
	'wantedproperties' => '待建立的性質',	//'Wanted Properties',
	'smw_wantedproperties_docu' => '下方的性質雖已用於此wiki內，但卻未事先以任何頁面去定義它們。',	//'The following properties are used in the wiki but do not yet have a page for describing them.',
	'smw_wantedproperty_template' => '$1 (已用於$2處)',	//'$1 ($2 uses)', // <propname> (<count> uses)
	// Messages for the refresh button
	'tooltip-purge' => '按此處以更新此頁全部的查詢項目及樣板。',	//'Click here to refresh all queries and templates on this page',
	'purge' => '更新',	//'Refresh',
	// Messages for Import Ontology Special
	'ontologyimport' => '輸入知識本體(ontology)',	//'Import ontology',
	'smw_oi_docu' => '此特殊頁可用以輸入知識本體(ontology)，此知識本體(ontology)必須依循特定的格式，此特定格式在<a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">知識本體(ontology)的匯入求助頁面。</a>',	//'This special page allows to import ontologies. The ontologies have to follow a certain format, specified at the <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">ontology import help page</a>.',
	'smw_oi_action' => '輸入',	//'Import',
	'smw_oi_return' => '返回<a href="$1">Special:OntologyImport</a>',	//'Return to <a href="$1">Special:OntologyImport</a>.',
	'smw_oi_noontology' => '無知識本體(ontology)可提供或者無法載入知識本體',	//'No ontology supplied, or could not load ontology.',
	'smw_oi_select' => '請選擇某敘述以輸入然後按輸入鍵',	//'Please select the statements to import, and then click the import button.',
	'smw_oi_textforall' => '用以添加於所有輸入的標題文字(也許是空白)：',	//'Header text to add to all imports (may be empty):',
	'smw_oi_selectall' => '選取或放棄選取全部的敘述',	//'Select or unselect all statements',
	'smw_oi_statementsabout' => '相關描述',	//'Statements about',
	'smw_oi_mapto' => '對映本質(entity)至',	//'Map entity to',
	'smw_oi_comment' => '添加以下的文字：',	//'Add the following text:',
	'smw_oi_thisissubcategoryof' => '所屬的次分類',	//'A subcategory of',
	'smw_oi_thishascategory' => '此部分附屬於(Is part of)',	//'Is part of',
	'smw_oi_importedfromontology' => '從知識本體(ontology)輸入',	//'Import from ontology',
	// Messages for (data)Types Special
	'types' => '型態',	//'Types',
	'smw_types_docu' => '以下為所有資料型態的清單，資料型態可用於指定性質，每項資料型態皆有提供附加資訊的頁面。',	//'The following is a list of all datatypes that can be assigned to properties. Each datatype has a page where additional information can be provided.',
	'smw_typeunits' => '“$1”型態的量測單位：$2',	//'Units of measurement of type “$1”: $2',
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => '語意統計(Semantic Statistics)',	//'Semantic Statistics',
	'smw_semstats_text' => '此wiki含有<b>$1</b>性質的值以用於總計<b>$2</b> 不同於 <a href="$3">性質</a>。 <b>$4</b>性質有著專屬的專面，且預期所需的資料型態因著<b>$5</b>，而已被指定了，有些現有的性質也許為<a href="$6">未使用的性質</a>。您可在 <a href="$7">待建立的性質清單</a>中，找到那些尚未建立專屬頁面的性質。(譯註原文為：This wiki contains <b>$1</b> property values for a total of <b>$2</b> different <a href="$3">properties</a>. <b>$4</b> properties have an own page, and the intended datatype is specified for <b>$5</b> of those. Some of the existing properties might by <a href="$6">unused properties</a>. Properties that still lack a page are found on the <a href="$7">list of wanted properties</a>.This wiki contains <b>$1</b> property values for a total of <b>$2</b> different <a href="$3">properties</a>. <b>$4</b> properties have an own page, and the intended datatype is specified for <b>$5</b> of those. Some of the existing properties might by <a href="$6">unused properties</a>. Properties that still lack a page are found on the <a href="$7">list of wanted properties</a>.)',
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => '錯誤的性質',	//'Flawed Properties',
	'smw_fattributes' => '在下方處被列出的頁面有著一項非正確定義的屬性，非正確的屬性的數量置於中括號內',	//'The pages listed below have an incorrectly defined property. The number of incorrect properties is given in the brackets.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'URI Resolver',	//'URI Resolver',
	'smw_uri_doc' => '<p>The URI resolver implements the <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. It takes care that humans don\'t turn into websites.</p>',	//'<p>The URI resolver implements the <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. It takes care that humans don\'t turn into websites.</p>',
	// Messages for ask Special
	'ask' => '語意搜尋',	//'Semantic search',
	'smw_ask_doculink' => '語意搜尋',	//'Semantic search',
	'smw_ask_sortby' => '依欄位排序',       //(Sort by column)
	'smw_ask_ascorder' => '升冪',        //(Ascending)
	'smw_ask_descorder' => '降冪',       //(Descending)
	'smw_ask_submit' => '搜尋的結果',       //(Find results)	
	'smw_ask_editquery' => '[Edit query]', // TODO: translate
	'smw_ask_hidequery' => 'Hide query', // TODO: translate
	'smw_ask_help' => 'Querying help', // TODO: translate
	'smw_ask_queryhead' => 'Query', // TODO: translate
	'smw_ask_printhead' => 'Additional printouts (optional)', // TODO: translate
	// Messages for the search by property special
	'searchbyproperty' => '依性質搜尋',	//'Search by property',
	'smw_sbv_docu' => '<p>依所指定的性質及其值來搜尋頁面</p>',	//'<p>Search for all pages that have a given property and value.</p>',
	'smw_sbv_noproperty' => '請輸入某項性質',	//'<p>Please enter a property.</p>',
	'smw_sbv_novalue' => '<p>請為該性質輸入一項有效值，或請查閱“$1.”的全部的性質的值</p>',	//'<p>Please enter a valid value for the property, or view all property values for “$1.”</p>',
	'smw_sbv_displayresult' => '所有“$1”性質項目中，皆帶有“$2”值的頁面清單',	//'A list of all pages that have property “$1” with value “$2”',
	'smw_sbv_property' => '性質',	//'Property',
	'smw_sbv_value' => '值',	//'Value',
	'smw_sbv_submit' => '搜尋的結果',	//'Find results',
	// Messages for the browsing special
	'browse' => '瀏覽wiki',	//'Browse wiki',
	'smw_browse_article' => '在開始瀏覽的表單中輸入頁面名稱',	//'Enter the name of the page to start browsing from.',
	'smw_browse_go' => '前往',	//'Go',
	'smw_browse_more' => '&hellip;',	//'&hellip;',
	// Messages for the page property special
	'pageproperty' => '頁面性質搜尋',	//'Page property search',
	'smw_pp_docu' => '搜尋某一頁面全部性質的過濾條件，請同時輸入頁面及性質',	//'Search for all the fillers of a property on a given page. Please enter both a page and a property.',
	'smw_pp_from' => '開始頁面(From page)',
	'smw_pp_type' => '性質',	//'Property',
	'smw_pp_submit' => '搜尋的結果',	//'Find results',
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => '前一頁',        //(Previous)
	'smw_result_next' => '下一頁',        //(Next)
	'smw_result_results' => '結果',      //(Results)
	'smw_result_noresults' => '抱歉，無您所要的結果。'    //(Sorry, no results.)	
);

protected $m_DatatypeLabels = array(
	'_wpg' => '頁面',	//'Page', // name of page datatype
	'_str' => '字串',	//'String',  // name of the string type
	'_txt' => '文字',	//'Text',  // name of the text type
	'_boo' => '布林',	//'Boolean',  // name of the boolean type
	'_num' => '數字',	//'Number',  // name for the datatype of numbers
	'_geo' => '地理學的座標',	//'Geographic coordinate', // name of the geocoord type
	'_tem' => '溫度',	//'Temperature',  // name of the temperature type
	'_dat' => '日期',	//'Date',  // name of the datetime (calendar) type
	'_ema' => 'Email',	//'Email',  // name of the email type
	'_uri' => 'URL',	//'URL',  // name of the URL type
	'_anu' => 'URI的註解',	//'Annotation URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'浮點數'       => '_num',	//'_num',
	'整數'         => '_num' ,	//'_num',
	 '列舉'        => '_str',	//'_str'
	// SMW0.7 compatibility:
	'Float'       => '_num',
	'Integer'     => '_num',
	'Enumeration' => '_str',
	'URI'         => '_uri',
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
	SMW_SP_HAS_TYPE  => '設有型態',	//'Has type',
	SMW_SP_HAS_URI   => '對應的URI',	//'Equivalent URI',
	SMW_SP_SUBPROPERTY_OF => '所屬的子性質',	//'Subproperty of',
    SMW_SP_DISPLAY_UNITS => '顯示單位',      //Display unit
    SMW_SP_IMPORTED_FROM => '輸入來源',     //Imported from
    SMW_SP_CONVERSION_FACTOR => '符合於',  //Corresponds to
    SMW_SP_SERVICE_LINK => '提供服務',      //Provides service
    SMW_SP_POSSIBLE_VALUE => '允許值'      //Allows value
);


protected $m_SpecialPropertyAliases = array(
	'Display unit' => SMW_SP_DISPLAY_UNITS,
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
	SMW_NS_RELATION       => '關聯',	//'Relation',
	SMW_NS_RELATION_TALK  => '關聯_talk',	//'Relation_talk',
	SMW_NS_PROPERTY       => '性質',	//'Property',
	SMW_NS_PROPERTY_TALK  => '性質_talk',	//'Property_talk',
	SMW_NS_TYPE           => '型態',	//'Type',
	SMW_NS_TYPE_TALK      => '型態_talk',	//'Type_talk'
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





