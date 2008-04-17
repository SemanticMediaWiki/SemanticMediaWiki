<?php
/**
 * @author Mahmoud Zouari  mahmoudzouari@yahoo.fr http://www.cri.ensmp.fr
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageAr extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => ' تغيير المساعدة خصائص ',
	'smw_viewasrdf' => 'RDF feed',
	'smw_finallistconjunct' => ', و', //used in "A, B, and C"
	'smw_factbox_head' => 'حقائق عن $1',
	'smw_isspecprop' => 'هذه الممتلكات هى ممتلكات خاصة في هذا الويكي',
	'smw_isknowntype' => '.هذا النوع هو من بين انواع البيانات الموحدة من هذا الويكي',
	'smw_isaliastype' => 'هذا النوع هو الاسم المستعار لنوع البيانات “$1”.',
	'smw_isnotype' => 'هذا النوع “$1” هو ليس معيار البيانات في ويكي ، ولم يعط تعريفا من قبل المستخدمين',
	// URIs that should not be used in objects in cases where users can provide URIs
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => ' ، من شكل "$ 1" غير مسموح بها uris عذرا .',
	// Link to RSS feeds
	'smw_rss_link' => 'رس س',
	// Messages and strings for inline queries
	'smw_iq_disabled' => "عذرا. الاستفسارات الدلاليه جرى تعطيلها في هذا الويكي.",
	'smw_iq_moreresults' => '&hellip; مزيد من النتائج ',
	'smw_iq_nojs' => 'الرجاء استخدام المتصفح الذي يمكن جافا سكريبت لعرض هذا العنصر.',
	'smw_iq_altresults' => 'استعرض قائمة النتائج مباشرة.', // available link when JS is disabled
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => 'امكانيه استيراد ليست متوفره لاسم الفضاء “$1”.',
	'smw_nonright_importtype' => '$1 لا يمكن ان تستخدم الا لصفحات مع اسم الفضاء “$2”.',
	'smw_wrong_importtype' => '$1 can not be used for pages in the namespace “$2”.', // TODO: translate
	'smw_no_importelement' => ' غير متاح للاستيراد “$1”  عنصر ',
	// Messages and strings for basic datatype processing
	'smw_parseerror' => 'The given value was not understood.', // TODO: translate; generic error, "something" went
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_notitle' => '“$1” لا يمكن أن تستخدم مثل هذا الاسم في صفحة ويكي.',
	'smw_unknowntype' => ' نوع غير مدعوم "$ 1" لتعريف الممتلكات.',
	'smw_manytypes' => '.أكثر من نوع واحد لتعريف الخاصيه',
	'smw_emptystring' => '.الجمل الفارغة غير مقبولة',
	'smw_maxstring' => '{{SITENAME}} طويل جدا لل $1 سلسلة احرف ترميز ',
	'smw_notinenum' => '( "$ 2") لهذه الممتلكات ليس في قائمة القيم المحتملة “$1” ',
	'smw_noboolean' => '  (لا تعتبر قيمة منطقيه (صحيح / غير صحيح “$1”',
	'smw_true_words' => ' صحيح ،ص ، نعم ، ن ', // comma-separated synonyms for Boolean TRUE besides '1', primary value first
	'smw_false_words' => ' ليس صحيحا ،ص  ، لا ', // comma-separated synonyms for Boolean FALSE besides '0', primary value first
	'smw_nofloat' => ' ليس العدد“$1”',
	'smw_infinite' => '{{SITENAME}} ليست مدعومه في “$1” ارقام كبيرة حسب  ',
	'smw_infinite_unit' => '{{SITENAME}} اسفر ذلك عدد كبير جدا بالنسبة الى“$1” تحويلها الى وحدة ',
	// Currently unused, floats silently store units. 'smw_unexpectedunit' => ' هذه الخاصيه لا تدعم وحدة التحويل',
	'smw_unsupportedprefix' => ' غير مدعوم (“$1”) البادءات لارقام',
	'smw_unsupportedunit' => ' غير مدعوم “$1”  وحده لتحويل وحدة ',
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => ' “$1” لا توجد أي عدد قبل الرمز ', // $1 is something like ° 
	'smw_bad_latlong' => '.خطوط الطول والعرض يجب ان تعطى مرة واحدة فقط ، واحداثيات صحيحة',
	'smw_abb_north' => 'شمال',
	'smw_abb_east' => 'شرق',
	'smw_abb_south' => 'جنوب',
	'smw_abb_west' => 'غرب',
	'smw_label_latitude' => ':خطوط الطول',
	'smw_label_longitude' => ':خطوط العرض ',
	// some links for online maps; can be translated to different language versions of services, but need not
	'smw_service_online_maps' => " Find&nbsp;online&nbsp;maps|http://tools.wikimedia.de/~magnus/geo/geohack.php?params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	// Messages for datetime parsing
	'smw_nodatetime' => 'لم يفهم الدعم للتواريخ لا تزال تجريبيه)). “$1”  تاريخ',
	// Errors and notices related to queries
	'smw_toomanyclosing' => ' في الاستعلام“$1”  يبدو ان هناك الكثير من الحوادث',
	'smw_noclosingbrackets' => ' “]]” في بحثك لم تكن مغلقة باستخدام “[&#x005B;” بعض استخدام  ',
	'smw_misplacedsymbol' => ' تم استخدامه في مكان حيث انها ليست مفيدة “$1”   الرمز',
	'smw_unexpectedpart' => ' للاستفسار لا يفهم. النتائج قد لا تكون كما هو متوقع “$1”  هذا الجزء ',
	'smw_emptysubquery' => '.بعض الاستفسارات ليس لها شرطا صحيحا',
	'smw_misplacedsubquery' => 'استفسار الفرعي استخدم في مكان لا يسمح الاستفسارات الفرعية',
	'smw_valuesubquery' => ' “$1” الاستفسارات الفرعية لا يدعم قيم خاصيه  ',
	'smw_overprintoutlimit' => '.هذا الاستعلام تحتوي على عدد كبير جدا من طلبات العرض على الشاشه',
	'smw_badprintout' => '.بعض المطبوعات في بيان الاستعلام لم تتشكل بصورة صحيحة',
	'smw_badtitle' => ' ليس عنوان صفحه صحيح. “$1” عذرا ، ولكن ',
	'smw_badqueryatom' => ' لم يكن يفهم“[&#x005B;&hellip;]]” أجزاء من  ',
	'smw_propvalueproblem' => ' لم يكن يفهم“$1” قيمة الخاصيه ',
	'smw_nodisjunctions' => 'المفارق في استعلامات ليست مدعومه في هذا الويكي وجزء من الاستعلام رفض $1.',
	'smw_querytoolarge' => '	
هذه شروط استفسار لا يمكن اعتباره نتيجة لقيود الويكي في الحجم أو عمق استفسار $1.'
);


protected $m_UserMessages = array(
	'smw_devel_warning' => 'هذه السمة هي حاليا قيد التطوير ، وربما لا يكون كاملا وظيفيه. احفظ البيانات قبل استخدامها',
	// Messages for pages of types and properties
	'smw_type_header' => ' “$1” خصائص النوع',
	'smw_typearticlecount' => ' باستخدام هذا النوع $1 خصائص عرض ',
	'smw_attribute_header' => '“$1” هذه الصفحات تستخدم الخصائص ',
	'smw_attributearticlecount' => '<p>  الخصائص باستخدام هذه $1 صفحات عرض </p>',
	// Messages used in RSS feeds
	'smw_rss_description' => '$1 [رس س] تخول ',
	// Messages for Export RDF Special
	'exportrdf' => 'آردی‌اف إل صفحات تصدير ', //name of this special
	'smw_exportrdf_docu' => '<p> هذه الصفحه تتيح لك الحصول على بيانات من صفحة في شكل آردی‌اف. التصدير الى صفحات ، أدخل العناوين في مربع النص أدناه ، عنوان واحد لكل سطر. </p>',
	'smw_exportrdf_recursive' => ' تصدير جميع الصفحات ذات الصلة بشكل تكراري. علما انه يمكن ان تكون النتيجة كبيرة',
	'smw_exportrdf_backlinks' => ' ايضا تصدير كل الصفحات التي تشير الى الصفحات  تم تصديرها', // Generates browsable RDF not traslated
	'smw_exportrdf_lastdate' => ' لا تصدر الصفحات التي لم تتغير منذ نقطة زمنيه محددة',
	// Messages for Properties Special
	'properties' => ' الخصائص ',
	'smw_properties_docu' => '.التالية تستخدم في ويكي الخصائص',
	'smw_property_template' => '$1 من نوع $2 ($3)', // <propname> of type <type> (<count>)
	'smw_propertylackspage' => '! جميع الخصائص ينبغي ان توصف بصفحة',
	'smw_propertylackstype' => ' ("$1" لالان نوع الخاصيه ليست محددة (على افتراض نوع ',
	'smw_propertyhardlyused' => ' هذه الخاصيه لا يكاد يستخدم داخل يكي',
	// Messages for Unused Properties Special
	'unusedproperties' => ' خصائص معطله',
	'smw_unusedproperties_docu' => ' ا الخصائص التالية تظهر على الرغم من عدم وجود صفحة اخرى يستفيد منها ',
	'smw_unusedproperty_template' => '$1 من نوع $2', // <propname> of type <type>
	// Messages for Wanted Properties Special
	'wantedproperties' => ' الخصائص التي تحتاجها ',
	'smw_wantedproperties_docu' => '. التالية تستخدم في ويكي ولكن ليس لديها حتى الآن صفحة لوصفها ا الخصائص.',
	'smw_wantedproperty_template' => '$1 ($2 الاستعمالات)', // <propname> (<count> uses)
	// Messages for the refresh button
	'tooltip-purge' => ' اضغط هنا لتحديث كافة الاستفسارات والقوالب على هذه الصفحه',
	'purge' => 'تحديث',
	// Messages for Import Ontology Special
	'ontologyimport' => 'استيراد أنتولوجي',
	
'smw_oi_docu' => ' استيراد صفحة المساعدة أنتولوجي <a href="http://wiki.ontoworld.org/index.php/Help:Ontology_import">  هذه صفحة خاصة تسمح باستيراد أنتولوجي. فان أنتولوجي
 يجب اتباع شكل معين ، كما هو محدد في </a>.',
	'smw_oi_action' => ' استيراد ',
	'smw_oi_return' => ' <a href="$1">Special:OntologyImport</a> العودة الى ',
	'smw_oi_noontology' => ' لا توجد أنتولوجي ، او تعذر تحميل أنتولوجي ',
	'smw_oi_select' => ' رجاء اختر بيانات الاستيراد ، وبعد ذلك انقر على زر استيراد ',
	'smw_oi_textforall' => ' بداية النص الذي سيضاف الى جميع الواردات( قد تكون فارغه) ',
	'smw_oi_selectall' => ' اختر او احذف جميع البيانات ',
	'smw_oi_statementsabout' => ' بيانات حول ',
	'smw_oi_mapto' => ' خريطه لكيان ',
	'smw_oi_comment' => ' يضاف النص التالي : ',
	'smw_oi_thisissubcategoryof' => ' فئة فرعية لل ',
	'smw_oi_thishascategory' => ' هي جزء من ',
	'smw_oi_importedfromontology' => ' الاستيراد منالأنتولوجيا ',
	// Messages for (data)Types Special
	'types' => ' أنواع ',
	'smw_types_docu' => ' فيما يلى قائمة من جميع انواع البيانات التي يمكن أن تسند الى الخصائص. كل البيانات له صفحة فيها معلومات اضافية يمكن توفيرها. ',
	'smw_typeunits' => ' $2 : “$1” وحدات القياس من النوع',
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => ' احصاءات دلاليه ',
	'smw_semstats_text' => '<a href="$7"> قائمة المطلوبين الخصائص </a> الخصائص التي لا تزال تفتقر الى صفحة موجودة على <a href="$6"> معطله</a> <b>$5</b> . بعض من الخصائص الموجودة قد تكون<b>$4</b> خواص لها صفحة خاصة بها ، والمقصود هو نوع البيانات المحدد ل </a> مختلفة  <a href="$3"> الخصائص <b>$1</b> خصائص القيم مجموعة <b>$2</b> يتضمن هذا يكي ',
	
/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => 'Flawed Properties',
	'smw_fattributes' => ' خصائص الصفحات المذكورة ادناه لم تعرف بشكل صحيح. عدد الخصائص غير صحيح يرد في الاقواس. ',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => 'URI Resolver',
	'smw_uri_doc' => '<p>The URI resolver implements the <a href="http://www.w3.org/2001/tag/issues.html#httpRange-14">W3C TAG finding on httpRange-14</a>. It takes care that humans don\'t turn into websites.</p>',
	// Messages for ask Special
	'ask' => ' البحث الدلالي ',
	'smw_ask_doculink' => 'http://semantic-mediawiki.org/wiki/Help:Semantic_search',
	'smw_ask_sortby' => ' الترتيب حسب العمود (اختياري)',
	'smw_ask_ascorder' => ' صعود ',
	'smw_ask_descorder' => ' تنازلي',
	'smw_ask_submit' => ' ايجاد نتائج ',
	'smw_ask_editquery' => '[تحرير استفسار]',
	'smw_add_sortcondition' => '[اضافة شرط الترتيب]',
	'smw_ask_hidequery' => ' اخفاء الاستعلام ',
	'smw_ask_help' => ' سؤال مساعدة ',
	'smw_ask_queryhead' => ' إستفسار ',
	'smw_ask_printhead' => ' مطبوعات اضافية (اختياري)',
	// Messages for the search by property special
	'searchbyproperty' => ' البحث حسب الخصائص ',
	'smw_sbv_docu' => '<p> البحث عن كل الصفحات التي لها خصائص معينة وقيمه </p>',
	'smw_sbv_noproperty' => '<p>. الرجاء ادخال خاصيه </p>',
	'smw_sbv_novalue' => '<p> “$1.” الرجاء ادخال قيمة الخصائص ، أو اعرض كل قيم الخصائص </p>',
	'smw_sbv_displayresult' => '“$2”  مع قيمه “$1” قائمة بجميع الصفحات التي لديه الخصائص ',
	'smw_sbv_property' => ' خاصيه',
	'smw_sbv_value' => ' القيمه',
	'smw_sbv_submit' => ' ايجاد نتائج ',
	// Messages for the browsing special
	'browse' => ' استعرض يكي ',
	'smw_browse_article' => ' ادخل اسم الصفحه لتبدأ التصفح',
	'smw_browse_go' => ' الاطلاق ',
	'smw_browse_more' => '&hellip;',
	// Messages for the page property special
	'pageproperty' => ' بحث عن خصائص الصفحه ',
	'smw_pp_docu' => ' البحث عن جميع قيم سمة على صفحة معينة. الرجاء ادخال كل صفحة وميزة',
	'smw_pp_from' => ' من صفحة ',
	'smw_pp_type' => ' الخاصيه ',
	'smw_pp_submit' => ' ايجاد نتائج ',
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => ' السابق ',
	'smw_result_next' => ' القادم ',
	'smw_result_results' => ' النتائج ',
	'smw_result_noresults' => '. عفوا ، لا توجد نتائج '
);

protected $m_DatatypeLabels = array(
	'_wpg' => ' الصفحه ', // name of page datatype
	'_str' => ' سلسلة احرف',  // name of the string type
	'_txt' => ' نص',  // name of the text type
	'_boo' => ' منطقي ',  // name of the boolean type
	'_num' => ' عدد ',  // name for the datatype of numbers
	'_geo' => ' الاحداثيات ', // name of the geocoord type
	'_tem' => ' الحرارة ',  // name of the temperature type
	'_dat' => ' التاريخ ',  // name of the datetime (calendar) type
	'_ema' => ' البريد الالكتروني ',  // name of the email type
	'_uri' => ' عنوان الصفحة',  // name of the URL type
	'_anu' => ' التعليق علي معرف الموارد الموحد '  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'         => '_uri',
	'Float'       => '_num',
	'Integer'     => '_num',
	'Enumeration' => '_str'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => ' نوع لديه ',
	SMW_SP_HAS_URI   => ' معرف الموارد الموحد معادلة ',
	SMW_SP_SUBPROPERTY_OF => 'الخاصيه الفرعية لل',
	SMW_SP_DISPLAY_UNITS => 'عرض الوحدات',
	SMW_SP_IMPORTED_FROM => ' المستورده من ',
	SMW_SP_CONVERSION_FACTOR => ' يقابل',
	SMW_SP_SERVICE_LINK => ' توفر الخدمة ',
	SMW_SP_POSSIBLE_VALUE => ' تسمح القيمه '
);

protected $m_SpecialPropertyAliases = array(
	' عرض الوحدات' => SMW_SP_DISPLAY_UNITS
);

protected $m_Namespaces = array(
	SMW_NS_RELATION       => ' العلاقة ',
	SMW_NS_RELATION_TALK  => ' فيما يتعلق الحديث ',
	SMW_NS_PROPERTY       => ' الخاصيه ',
	SMW_NS_PROPERTY_TALK  => ' الحديث عن السمة ',
	SMW_NS_TYPE           => ' النوع ',
	SMW_NS_TYPE_TALK      => ' نوع الحديث'
);

}




