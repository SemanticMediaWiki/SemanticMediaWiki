<?php
/**
 * @author Markus Krötzsch 翻译:张致信 本档系以电子字典译自繁体版，请自行修订(Translation: Roc Michael Email:roc.no1@gmail.com. This file is translated from Tradition Chinese by useing electronic dictionary. Please correct the file by yourself.) 2007-10-22
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageZh_cn extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => '与关联(relations)及属性(attributes)有关的编辑协助',  //(Editing help on relations and attributes)
	'smw_viewasrdf' => 'RDF feed',
	'smw_finallistconjunct' => '， 和',    //(, and) used in "A, B, and C"
	'smw_factbox_head' => '关于$1 的小文件', //(Facts about $1)
	'smw_isspecprop' => '在此wiki系统内，此一性质为一种特殊性质', //(This property is a special property in this wiki.)
	'smw_isknowntype' => '此一型态系为这个wiki系统内的标准的资料型态之一',//(This type is among the standard datatypes of this wiki.)
	'smw_isaliastype' => '此一型态系为资料型态“$1＂的别称',//(This type is an alias for the datatype “$1＂.)
	'smw_isnotype' => '在此wiki系统内，此一“$1＂型态并非是一项标准的资料型态，并且尚未被用户赋予其定义',
	//(This type “$1＂ is not a standard datatype in the wiki, and has not been given a user definition either.) URIs that should not be used in objects in cases where users can provide URIs
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => '抱歉，在此处无法取得从“$1＂范围的，URIs. (译注原文为：Sorry, URIs from the range “$1＂ are not available in this place.)',
	// Messages and strings for inline queries
	'smw_iq_disabled' => "抱歉，联机查询在此wiki已被设置为无效", //"Sorry. Semantic queries have been disabled for this wiki."
	'smw_iq_moreresults' => '&hellip; 高级查询',       //'&hellip; further results'
	'smw_iq_nojs' => '请使用内建JavaScript的浏览器以浏览此元素.',    //'Use a JavaScript-enabled browser to view this element, or directly <a href="$1">browse the result list</a>.' // TODO: check translation (Markus pruned it ;)
	'smw_iq_altresults' => 'Browse the result list directly.', // available link when JS is disabled // TODO: translate
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => '导入功能对“$1＂的名字空间无效＂.',    //'Import functions are not avalable for namespace “$1＂.
	'smw_nonright_importtype' => '$1仅能用于名字空间为“$2＂的页面。',        //'$1 can only be used for pages with namespace “$2＂.'
	'smw_wrong_importtype' => '$1无法用于名字空间为“$2＂的页面。',   //'$1 can not be used for pages in the namespace “$2＂.'
	'smw_no_importelement' => '无法导入“$1＂元素',    //'Element “$1＂ not available for import.'
	// Messages and strings for basic datatype processing
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_notitle' => '在此wiki内，是无法用“$1＂来当作页面名称的',       //'“$1＂ cannot be used as a page name in this wiki.'
	'smw_unknowntype' => '不支持为性质所定义的“$1＂形态。',  //'Unsupported type “$1＂ defined for property.'
	'smw_manytypes' => '定义此性质的型态已超过了一种以上。',    //'More than one type defined for property.'
	'smw_emptystring' => '不接受空白字串。',   //'Empty strings are not accepted.'
	'smw_maxstring' => '对本站而言，$1所代表的字串太长了。(译注原文为：String representation $1 is too long for this site.)',        //'String representation $1 is too long for this site.'
	'smw_notinenum' => '“$1＂ 并非在此属性有可能的值 ($2)的列表之中',   // '“$1＂ is not in the list of possible values ($2) for this property.'
	'smw_noboolean' => '“$1＂无法被视为布林值(true/false)。',    //'“$1＂ is not recognized as a boolean (true/false) value.'
	'smw_true_words' => 't,yes,y,是',   // comma-separated synonyms for boolean TRUE besides 'true' and '1'
	'smw_false_words' => 'f,no,n,否',   // comma-separated synonyms for boolean FALSE besides 'false' and '0'
	'smw_nofloat' => '“$1＂ 并非为是数字',    // '“$1＂ is no number.'
	'smw_infinite' => '在此站内并不支持像是“$1＂如此庞大的数目字。',       //'Numbers as large as “$1＂ are not supported on this site.'
	'smw_infinite_unit' => '对此站而言转换“$1＂单位所产生的数目字过于庞大。',        // 'Conversion into unit “$1＂ resulted in a number that is too large for this site.'
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => 'this property supports no unit conversion',
	'smw_unsupportedprefix' => '数字(“$1＂) 的字首目前尚未被支持',  //'Prefixes for numbers (“$1＂) are not supported.'
	'smw_unsupportedunit' => '单位转换无法适用于“$1＂此一单位',      //'Unit conversion for unit “$1＂ not supported.'
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => '在“$1＂此一单位之前并无数目字', //'No number found before the symbol “$1＂.'     // $1 is something like °
	'smw_bad_latlong' => '纬度和经度只能以有效的座标值标示一次', //'Latitude and longitude must be given only once, and with valid coordinates.'
	'smw_abb_north' => 'N',    //北
	'smw_abb_east' => 'E',     //东
	'smw_abb_south' => 'S',    //南
	'smw_abb_west' => 'W',     //西
	'smw_label_latitude' => '纬度：',     // 'Latitude:'
	'smw_label_longitude' => '经度：',    //'Longitude:'
	// some links for online maps; can be translated to different language versions of services, but need not
	'smw_service_online_maps' => " find&nbsp;maps|http://tools.wikimedia.de/~magnus/geo/geohack.php?params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",   //" find&nbsp;maps|http://tools.wikimedia.de/~magnus/geo/geohack.php?params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6"
	// Messages for datetime parsing
	'smw_nodatetime' => '日期值“$1＂无法被识别，对日期值的支持目前尚属实验性质。',       //'The date “$1＂ was not understood (support for dates is still experimental).'
	// Errors and notices related to queries
	'smw_toomanyclosing' => '在此查询中“$1＂显然出现太多次了',       // 'There appear to be too many occurrences of “$1＂ in the query.'
	'smw_noclosingbrackets' => '在您的查询中“[&#x005B;＂ 并未以对应的“]]＂来予以封闭',        // 'Some use of “[&#x005B;＂ in your query was not closed by a matching “]]＂.'
	'smw_misplacedsymbol' => '“$1＂此一符号用于某项无用之处',       //'The symbol “$1＂ was used in a place where it is not useful.'
	'smw_unexpectedpart' => '查询的“$1＂部份无法被识别，可能会出现预料之外的结果',     // 'The part “$1＂ of the query was not understood. Results might not be as expected.'
	'smw_emptysubquery' => '某些子查询并不具备有效的查询条件', //'Some subquery has no valid condition.'
	'smw_misplacedsubquery' => '某些子查询被用在不宜于使用子查询之处',   //'Some subquery was used in a place where no subqueries are allowed.'
	'smw_valuesubquery' => '“$1＂质性的值并不适用于子查询', //'Subqueries not supported for values of property “$1＂.',    //'Subqueries not supported for values of property “$1＂.'
	'smw_overprintoutlimit' => '此查询含有太多的打印要求(译注原文为：The query contains too many printout requests.)',   
	'smw_badprintout' => '在此查询中，有些打印叙述已被弄错了(译注原文为：Some print statement in the query was misshaped.)',
	'smw_badtitle' => '抱歉！“$1＂ 并非是有效的页面名称',    //'Sorry, but “$1＂ is no valid page title.',
	'smw_badqueryatom' => '在此查询中，部份的“[#x005B;&hellip]]＂无法被识别。(译注原文为：Some part “[#x005B;&hellip]]＂ of the query was not understood.)',  //'Some part “[#x005B;&hellip]]＂ of the query was not understood.',
	'smw_propvalueproblem' => '质性“$1＂的值无法被识别', //'The value of property “$1＂ was not understood.',
	'smw_nodisjunctions' => '在此wiki系统内分开查询是不被支持的，并有部份查询已被遗漏 ($1)。(译注原文为：Disjunctions in queries are not supported in this wiki and part of the query was dropped ($1).)',
	'smw_querytoolarge' => '基于此wiki系统对查询的规模及在深度方面的限制，以下的查询条件无法被接受：$1', //The following query conditions could not be considered due to the wikis restrictions in query size or depth: $1.
);

protected $m_UserMessages = array(
	'smw_devel_warning' => '此元件尚于开发中，也许无法完成发挥功效，在使用它之前，请先备份您的资料',      //'This feature is currently under development, and might not be fully functional. Backup your data before using it.',
	// Messages for pages of types and properties
	'smw_type_header' => '“$1＂型态的性质',  //'Properties of type “$1＂',
	'smw_typearticlecount' => '以此型态显示 $1 性质',  //'Showing $1 properties using this type.',
	'smw_attribute_header' => '使用性质“$1＂的页面',   //'Pages using the property “$1＂',
	'smw_attributearticlecount' => '<p>以此性质显示$1页面.</p>',   //'<p>Showing $1 pages using this property.</p>',
	// Messages for Export RDF Special
	'exportrdf' => '输出页面至RDF 。',       //'Export pages to RDF', //name of this special
	'smw_exportrdf_docu' => '<p>此一页面可让您获取RDF格式页面的资料，要输出页面，请在下方的文字框内键入页面的抬头，一项一行。</p>',     //'<p>This page allows you to obtain data from a page in RDF format. To export pages, enter the titles in the text box below, one title per line.</p>',
	'smw_exportrdf_recursive' => '逐项输出所有的相关的页面，请注意输出的结果可能颇为庞大。',       //'Recursively export all related pages. Note that the result could be large!',
	'smw_exportrdf_backlinks' => '并且输出与输出页面有关的页面，产生可供人阅读的RDF。(browsable RDF)', //'Also export all pages that refer to the exported pages. Generates browsable RDF.',
	'smw_exportrdf_lastdate' => '无须输出那些在所设之时间点以后就未再被更动过的页面',   //'Do not export pages that were not changed since the given point in time.',
	// Messages for Properties Special
	'properties' => '性质',      //'Properties',
	'smw_properties_docu' => '以下的性质已被用于此wiki内',        //'The following properties are used in the wiki.',
	'smw_property_template' => ' 型态 $2 ($3)的$1 (译注原文为：$1 of type $2 ($3) )',   //'$1 of type $2 ($3)', // <propname> of type <type> (<count>)
	'smw_propertylackspage' => '所有的性质应以某一页面加以描述。(译注原文为：All properties should be described by a page!)',        //'All properties should be described by a page!',
	'smw_propertylackstype' => '此一性质尚未被指定形态，先暂定为$1型态。(译注原文为：No type was specified for this property (assuming type $1 for now).)',     //'No type was specified for this property (assuming type $1 for now).',
	'smw_propertyhardlyused' => '此一性质难以用于此wiki内',      //'This property is hardly used within the wiki!',
	// Messages for Unused Properties Special
	'unusedproperties' => '未使用的性质',    //'Unused Properties',
	'smw_unusedproperties_docu' => '下方的性质虽已存在，但无其他的页面使用它们。',   //'The following properties exist although no other page makes use of them.',
	'smw_unusedproperty_template' => '$2型态的$1',        //'$1 of type $2', // <propname> of type <type>
	// Messages for Wanted Properties Special
	'wantedproperties' => '待建立的性质',    //'Wanted Properties',
	'smw_wantedproperties_docu' => '下方的性质虽已用于此wiki内，但却未事先以任何页面去定义它们。', //'The following properties are used in the wiki but do not yet have a page for describing them.',
	'smw_wantedproperty_template' => '$1 (已用于$2处)',    //'$1 ($2 uses)', // <propname> (<count> uses)
	// Messages for the refresh button
	'tooltip-purge' => '按此处以更新此页全部的查询项目及样板。',  //'Click here to refresh all queries and templates on this page',
	'purge' => '更新',   //'Refresh',
	// Messages for Import Ontology Special
	'ontologyimport' => '输入知识本体(ontology)',    //'Import ontology',
	'smw_oi_docu' => '此特殊页可用以输入知识本体(ontology)，此知识本体(ontology)必须依循特定的格式，此特定格式在<a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">知识本体(ontology)的导入求助页面。</a>',     //'This special page allows to import ontologies. The ontologies have to follow a certain format, specified at the <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">ontology import help page</a>.',
	'smw_oi_action' => '输入',   //'Import',
	'smw_oi_return' => '回车<a href="$1">Special:OntologyImport</a>',      //'Return to <a href="$1">Special:OntologyImport</a>.',
	'smw_oi_noontology' => '无知识本体(ontology)可提供或者无法载入知识本体',     //'No ontology supplied, or could not load ontology.',
	'smw_oi_select' => '请选择某叙述以输入然后按输入键',      //'Please select the statements to import, and then click the import button.',
	'smw_oi_textforall' => '用以添加于所有输入的标题文字(也许是空白)：',   //'Header text to add to all imports (may be empty):',
	'smw_oi_selectall' => '选取或放弃选取全部的叙述',      //'Select or unselect all statements',
	'smw_oi_statementsabout' => '相关描述',        //'Statements about',
	'smw_oi_mapto' => '对映本质(entity)至', //'Map entity to',
	'smw_oi_comment' => '添加以下的文字：',    //'Add the following text:',
	'smw_oi_thisissubcategoryof' => '所属的次分类',  //'A subcategory of',
	'smw_oi_thishascategory' => '此部分附属于(Is part of)',  //'Is part of',
	'smw_oi_importedfromontology' => '从知识本体(ontology)输入',      //'Import from ontology',
	// Messages for (data)Types Special
	'types' => '型态',   //'Types',
	'smw_types_docu' => '以下为所有资料型态的列表，资料型态可用于指定性质，每项资料型态皆有提供附加信息的页面。', //'The following is a list of all datatypes that can be assigned to properties. Each datatype has a page where additional information can be provided.',
	'smw_typeunits' => '“$1＂型态的量测单位：$2',       //'Units of measurement of type “$1＂: $2',
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => '语意统计(Semantic Statistics)',       //'Semantic Statistics',
	'smw_semstats_text' => '此wiki含有<b>$1</b>性质的值以用于总计<b>$2</b> 不同于 <a href="$3">性质</a>。 <b>$4</b>性质有着专属的专面，且预期所需的资料型态因着<b>$5</b>，而已被指定了，有些现有的性质也许为<a href="$6">未使用的性质</a>。您可在 <a href="$7">待建立的性质列表</a>中，找到那些尚未建立专属页面的性质。(译注原文为：This wiki contains <b>$1</b> property values for a total of <b>$2</b> different <a href="$3">properties</a>. <b>$4</b> properties have an own page, and the intended datatype is specified for <b>$5</b> of those. Some of the existing properties might by <a href="$6">unused properties</a>. Properties that still lack a page are found on the <a href="$7">list of wanted properties</a>.This wiki contains <b>$1</b> property values for a total of <b>$2</b> different <a href="$3">properties</a>. <b>$4</b> properties have an own page, and the intended datatype is specified for <b>$5</b> of those. Some of the existing properties might by <a href="$6">unused properties</a>. Properties that still lack a page are found on the <a href="$7">list of wanted properties</a>.)',
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => '错误的性质',     //'Flawed Properties',
	'smw_fattributes' => '在下方处被列出的页面有着一项非正确定义的属性，非正确的属性的数量置于中括号内',     //'The pages listed below have an incorrectly defined property. The number of incorrect properties is given in the brackets.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'URI Resolver',   //'URI Resolver',
	'smw_uri_doc' => '<p>The URI resolver implements the <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. It takes care that humans don\'t turn into websites.</p>',      //'<p>The URI resolver implements the <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. It takes care that humans don\'t turn into websites.</p>',
	// Messages for ask Special
	'ask' => '语意搜寻',   //'Semantic search',
	'smw_ask_doculink' => '语意搜寻',      //'Semantic search',
	'smw_ask_sortby' => '依栏位排序',       //(Sort by column)
	'smw_ask_ascorder' => '升幂',        //(Ascending)
	'smw_ask_descorder' => '降幂',       //(Descending)
	'smw_ask_submit' => '搜自导引结果',       //(Find results)
	'smw_ask_editquery' => '[Edit query]', // TODO: translate
	'smw_ask_hidequery' => 'Hide query', // TODO: translate
	'smw_ask_help' => 'Querying help', // TODO: translate
	'smw_ask_queryhead' => 'Query', // TODO: translate
	'smw_ask_printhead' => 'Additional printouts (optional)', // TODO: translate
	// Messages for the search by property special
	'searchbyproperty' => '依性质搜寻',     //'Search by property',
	'smw_sbv_docu' => '<p>依所指定的性质及其值来搜寻页面</p>',    //'<p>Search for all pages that have a given property and value.</p>',
	'smw_sbv_noproperty' => '请输入某项性质', //'<p>Please enter a property.</p>',
	'smw_sbv_novalue' => '<p>请为该性质输入一项有效值，或请查阅“$1.＂的全部的性质的值</p>',  //'<p>Please enter a valid value for the property, or view all property values for “$1.＂</p>',
	'smw_sbv_displayresult' => '所有“$1＂性质项目中，皆带有“$2＂值的页面列表',    //'A list of all pages that have property “$1＂ with value “$2＂',
	'smw_sbv_property' => '性质',        //'Property',
	'smw_sbv_value' => '值',    //'Value',
	'smw_sbv_submit' => '搜自导引结果',      //'Find results',
	// Messages for the browsing special
	'browse' => '浏览wiki',      //'Browse wiki',
	'smw_browse_article' => '在开始浏览的表单中输入页面名称', //'Enter the name of the page to start browsing from.',
	'smw_browse_go' => '前往',   //'Go',
	'smw_browse_more' => '&hellip;',       //'&hellip;',
	// Messages for the page property special
	'pageproperty' => '页面性质搜寻',        //'Page property search',
	'smw_pp_docu' => '搜寻某一页面全部性质的过滤条件，请同时输入页面及性质',     //'Search for all the fillers of a property on a given page. Please enter both a page and a property.',
	'smw_pp_from' => '开始页面(From page)',
	'smw_pp_type' => '性质',     //'Property',
	'smw_pp_submit' => '搜自导引结果',       //'Find results',
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => '前一页',        //(Previous)
	'smw_result_next' => '下一页',        //(Next)
	'smw_result_results' => '结果',      //(Results)
	'smw_result_noresults' => '抱歉，无您所要的结果。'    //(Sorry, no results.)
);

protected $m_DatatypeLabels = array(
	'_wpg' => '页面',    //'Page', // name of page datatype
	'_str' => '字串',    //'String',  // name of the string type
	'_txt' => '文字',    //'Text',  // name of the text type
	//'_boo' => '布林',    //'Boolean',  // name of the boolean type
	'_num' => '数字',    //'Number',  // name for the datatype of numbers
	'_geo' => '地理学的座标',        //'Geographic coordinate', // name of the geocoord type
	'_tem' => '温度',    //'Temperature',  // name of the temperature type
	'_dat' => '日期',    //'Date',  // name of the datetime (calendar) type
	'_ema' => 'Email', //'Email',  // name of the email type
	'_uri' => 'URL',   //'URL',  // name of the URL type
	'_anu' => 'URI的注解',        //'Annotation URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'浮点数'       => '_num',
	'整数'         => '_num',
	'列举'         => '_str',
	// SMW0.7 compatibility:
	'Float'       => '_num',
	'Integer'     => '_num',
	'Enumeration' => '_str',
	'URI'         => '_uri',
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
	SMW_SP_HAS_TYPE  => '设有型态', //'Has type',
	SMW_SP_HAS_URI   => '对应的URI',       //'Equivalent URI',
	SMW_SP_SUBPROPERTY_OF => '所属的子性质',  //'Subproperty of',
	SMW_SP_DISPLAY_UNITS => '显示单位',      //Display unit
	SMW_SP_IMPORTED_FROM => '输入来源',     //Imported from
	SMW_SP_CONVERSION_FACTOR => '符合于',  //Corresponds to
	SMW_SP_SERVICE_LINK => '提供服务',      //Provides service
	SMW_SP_POSSIBLE_VALUE => '允许值'      //Allows value
);

protected $m_SpecialPropertyAliases = array(
	'Display unit'      => SMW_SP_DISPLAY_UNITS,
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
	SMW_NS_RELATION       => '关联',      //'Relation',
	SMW_NS_RELATION_TALK  => '关联_talk', //'Relation_talk',
	SMW_NS_PROPERTY       => '性质',      //'Property',
	SMW_NS_PROPERTY_TALK  => '性质_talk', //'Property_talk',
	SMW_NS_TYPE           => '型态',      //'Type',
	SMW_NS_TYPE_TALK      => '型态_talk', //'Type_talk'
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
