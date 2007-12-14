<?php
/**
 * Created by Terry A. Hurlbut using an automatic translation machine. Please use with caution,
 * and send suggesntions for improvement to mak@aifb.uni-karlsruhe.de. The original English messages
 * found in the file SMW_LanguageEn.php may be useful as a reference.
 * @author Terry A. Hurlbut
 */

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

class SMW_LanguageKo extends SMW_Language {

protected $m_ContentMessages = array(
	'smw_edithelp' => '도움말을 수정하려면 속성을',
	'smw_viewasrdf' => '으로 볼 rdf',
	'smw_finallistconjunct' => ', 그리고', //used in "A, B, and C"
	'smw_factbox_head' => '사실에 대한 $1',
	'smw_isspecprop' => '이 속성은이 위키는 특별한 속성입니다.',
	'smw_isknowntype' => '이 유형은 표준 데이터 형식의들 사이에이 위키.',
	'smw_isaliastype' => '이 유형은 데이터의 별칭을 “$1”.',
	'smw_isnotype' => '이 유형이 “$1” 아닌 표준 데이터 형식은 위키가, 그리고 사용자 정의를 부여하지 않았습니다.',
	// URIs that should not be used in objects in cases where users can provide URIs
	'smw_uri_blacklist' => " http://www.w3.org/1999/02/22-rdf-syntax-ns#\n http://www.w3.org/2000/01/rdf-schema#\n http://www.w3.org/2002/07/owl#",
	'smw_baduri' => '죄송합니다, uri의 범위에서 “$1” 해당 장소에서 사용할 수 없다.',
	// Messages and strings for inline queries
	'smw_iq_disabled' => "죄송합니다. 이 위키에 대한 의미 론적 검색어가 해제되었습니다.",
	'smw_iq_moreresults' => '&hellip; 다른 경기 결과',
	'smw_iq_nojs' => '자바 스크립트 - 활성화된 브라우저를 사용하는이 요소를 참조하거나, 직접 <a href="$1">찾아보기 결과 목록</a>.',
	'smw_iq_altresults' => 'Browse the result list directly.', // available link when JS is disabled
	// Messages and strings for ontology resued (import)
	'smw_unknown_importns' => '져올 네임 스페이스를 사용할 수있는 기능이없습니다 “$1”.',
	'smw_nonright_importtype' => '$1 페이지에 대해서만 사용할 수있습니다 네임 스페이스 “$2”.',
	'smw_wrong_importtype' => '$1 페이지에 대해 사용할 수없습니다 네임 스페이스 “$2”.',
	'smw_no_importelement' => '원소 “$1” 에 사용할 수없습니다 져올.',
	// Messages and strings for basic datatype processing
	'smw_decseparator' => '.',
	'smw_kiloseparator' => ',',
	'smw_notitle' => '“$1” 대한 이름으로 사용하실 수없습니다이 위키는 페이지입니다.',
	'smw_unknowntype' => '지원되지 않는 종류 “$1” 정의에 대한 속성을.',
	'smw_manytypes' => '하나 이상의 속성에 대한 정의를 입력합니다.',
	'smw_emptystring' => '빈 문자열은 허용되지 않습니다.',
	'smw_maxstring' => '문자열 표현 $1 이 너무 긴이 사이트에 대한.',
	'smw_notinenum' => '“$1” 이 아닙니다의 목록에서이 속성능한 값 ($2) 에 대한.',
	'smw_noboolean' => '“$1” 가 인식되지 않습니다으로 부울 (참 / 거짓) 값.',
	'smw_true_words' => '예,진정한,',	// comma-separated synonyms for boolean TRUE besides 'true' and '1'
	'smw_false_words' => '아니오,거짓,',	// comma-separated synonyms for boolean FALSE besides 'false' and '0'
	'smw_nofloat' => '“$1” 이 아닌 숫자입니다.',
	'smw_infinite' => '숫자와 대형으로 “$1” 는 지원되지 않습니다이 사이트에서.',
	'smw_infinite_unit' => '전환으로 단위를 “$1” 결과는이 사이트에 대한 숫자가 너무 큽니다.',
	// Currently unused, floats silently store units.  'smw_unexpectedunit' => '이 속성을 지원 아니오 단위 변환',
	'smw_unsupportedprefix' => '접두사에 대한 숫자 (“$1”) 는 지원되지 않습니다.',
	'smw_unsupportedunit' => '단위 변환에 대한 단위를 “$1” 이 지원되지 않습니다.',
	// Messages for geo coordinates parsing
	'smw_lonely_unit' => '전에 번호를 찾을 수 없음을 상징 “$1”.', // $1 is something like ° or whatever Korean uses for degrees of arc.
	'smw_bad_latlong' => '위도와 경도를 한 번만 부여해야합니다, 그리고 올바른 좌표와 함께합니다.',
	'smw_abb_north' => '북',
	'smw_abb_east' => '동부',
	'smw_abb_south' => '남쪽',
	'smw_abb_west' => '서부',
	'smw_label_latitude' => '위도:',
	'smw_label_longitude' => '경도:',
	// some links for online maps; can be translated to different language versions of services, but need not
	'smw_service_online_maps' => " find&nbsp;maps|http://tools.wikimedia.de/~magnus/geo/geohack.php?params=\$9_\$7_\$10_\$8\n Google&nbsp;maps|http://maps.google.com/maps?ll=\$11\$9,\$12\$10&spn=0.1,0.1&t=k\n Mapquest|http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=degrees&latdeg=\$11\$1&latmin=\$3&latsec=\$5&longdeg=\$12\$2&longmin=\$4&longsec=\$6&zoom=6",
	// Messages for datetime parsing
	'smw_nodatetime' => '의 날짜 “$1” 는 이해할 수 없다. 날짜는 아직 실험 단계에 대한 지원을합니다.',
	// Errors and notices related to queries
	'smw_toomanyclosing' => '이 쿼리에서 사용하는 표현이 “$1” 너무 많은 시간입니다.',
	'smw_noclosingbrackets' => '일부의 사용 "[[" 검색어에 의해 폐쇄되지 않았습니다 매칭 "]]".',
	'smw_misplacedsymbol' => '의 상징 "$1" 이전에 사용하는 장소에 유용 않다.',
	'smw_unexpectedpart' => '이 부분은 "$1" 의 쿼리는 이해할 수 없다. 결과가 예상대로되지 않을 수도있습니다.',
	'smw_emptysubquery' => '특정 쿼리 조건이없습니다.',
	'smw_misplacedsubquery' => '등장하는 장소에서 하위 쿼리 질의를 사용할 수 없음.',
	'smw_valuesubquery' => '의 속성에 대한 값을 질의를 지원하지 않습니다 “$1”.',
	'smw_overprintoutlimit' => '쿼리에 너무 많은 인쇄를 요청합니다.',
	'smw_badprintout' => '검색어에 인쇄 성명 커보였다.',
	'smw_badtitle' => '죄송하지만, "$1" 이 올바르지 않습니다 페이지 이름입니다.',
	'smw_badqueryatom' => '일부 “[&#x005B;&hellip;]]” 의 쿼리는 식별할 수없는.',
	'smw_propvalueproblem' => '의 값은 속성 "$1" 는 이해할 수 없다',
	'smw_nodisjunctions' => '진술과 함께 "또는" 이 검색어는이 위키에서 지원되지 않습니다하고 검색어의 일부를 제외했습니다 ($1).',
	'smw_querytoolarge' => '다음과 같은 검색어를 조건을 초과 쿼리 크기에 대한 제한이 위키 또는 깊이: $1.'
);


protected $m_UserMessages = array(
	'smw_devel_warning' => '이 기능은 현재 개발, 그리고 의도한대로 작동하지 않을 수도있습니다. 을 만들어야합니다은 위키의 데이터를 저장하기 전에이를 사용합니다.',
	// Messages for pages of types and properties
	'smw_type_header' => '등록 정보의 유형 “$1”',
	'smw_typearticlecount' => '이 유형을 사용하여 보여주 $1 의 등록 정보를합니다.',
	'smw_attribute_header' => '페이지를 사용하여 속성이 “$1”',
	'smw_attributearticlecount' => '<p>이 속성을 사용하여 보여주 $1 페이지입니다.</p>',
	// Messages for Export RDF Special
	'exportrdf' => '수출에 페이지를 RDF', //name of this special
	'smw_exportrdf_docu' => '<p>이 페이지를 사용하면 RDF 포맷의 페이지에서 데이터를 구하려합니다. 을 수출 페이지, 아래 텍스트 상자에 제목을 입력을 한 줄에 제목입니다.</p>',
	'smw_exportrdf_recursive' => '재귀적으로 모든 관련 페이지에 수출합니다. 참고 사항이 될 결과에 큰!',
	'smw_exportrdf_backlinks' => '또한 내보낸 페이지를 참조하는 모든 페이지 내보내기합니다. 일람 RDF를 생성합니다.',
	'smw_exportrdf_lastdate' => '수출하지 마십시오하는 페이지가 주어진 시점 이후에 변경되지 않았습니다.',
	// Messages for Properties Special
	'properties' => '등록 정보',
	'smw_properties_docu' => '위키에 다음과 같은 속성을하는 데 사용됩니다.',
	'smw_property_template' => '$1 달러의 유형 $2 ($3)', // <propname> of type <type> (<count>)
	'smw_propertylackspage' => '등록 정보를 모두 한 페이지에 의해 설명해야한다!',
	'smw_propertylackstype' => '이 속성에 대해 지정된 유형이었다 없음 (정하면 종류 $1 에 대한 현재).',
	'smw_propertyhardlyused' => '이 속성은 거의 사용 내에있는 위키!',
	// Messages for Unused Properties Special
	'unusedproperties' => '사용하지 않는 속성을',
	'smw_unusedproperties_docu' => '다음과 같은 속성이 존재 다른 페이지를 활용합니다 비록 그들이없습니다.',
	'smw_unusedproperty_template' => '$1 달러의 유형 $2', // <propname> of type <type>
	// Messages for Wanted Properties Special
	'wantedproperties' => '원하는 속성을',
	'smw_wantedproperties_docu' => '위키에서 사용되는 다음과 같은 속성이 있지만 아직없는 그들을 설명하는 페이지입니다.',
	'smw_wantedproperty_template' => '$1 ($2 사용)', // <propname> (<count> uses)
	// Messages for the refresh button
	'tooltip-purge' => '여기를 클릭하여이 페이지를 새로 고치 모든 쿼리와 템플릿',
	'purge' => '새로 고침',
	// Messages for Import Ontology Special
	'ontologyimport' => '져올 존재론',
	'smw_oi_docu' => '이 특별 페이지를 통해를가 져올 존재론. 가 존재론 필요가 다음과 일정한 형식, 지정된 부분에 <a href="http://wiki.ontoworld.org/index.php/help:ontology_import"> 존재론 져올 도움말 페이지 </a>.',
	'smw_oi_action' => '져올',
	'smw_oi_return' => '돌아 <a href="$1"> 스페셜 : 존재론져올</a>.',
	'smw_oi_noontology' => '아니오 존재론 제공하거나 로드할 수없습니다 존재론.',
	'smw_oi_select' => '문장을 선택하십시오를가 져올을 누른가 져오기 버튼을 클릭하십시오.',
	'smw_oi_textforall' => '헤더 텍스트를 추가 모든 수입 (수있습니다 빈):',
	'smw_oi_selectall' => '모든 문장을 선택하거나 선택을 해제',
	'smw_oi_statementsabout' => '제표에 대한',
	'smw_oi_mapto' => '지도 사업체에',
	'smw_oi_comment' => '다음 텍스트를 추가:',
	'smw_oi_thisissubcategoryof' => '하위 카테고리 중',
	'smw_oi_thishascategory' => '의 일부인',
	'smw_oi_importedfromontology' => '에서가 져올 존재론',
	// Messages for (data)Types Special
	'types' => '유형',
	'smw_types_docu' => '다음은 목록에있는 모든 데이터를 속성을 할당할 수있습니다. 각 데이터 형식이있는 페이지가 어디에 추가 정보를 제공할 수있습니다.',
	'smw_typeunits' => '측정 단위의 유형 “$1”: $2',
	/*Messages for SemanticStatistics Special*/
	'semanticstatistics' => '의미 론적 통계',
	'smw_semstats_text' => '이 위키를 포함 <b>$1</b> 속성에 대한 값을 총 <b>$2</b> 서로 다른 <a href="$3">등록 정보</a>입니다. <b>$4</b> 속성을 갖고 자신의 페이지를, 그리고 의도에 대한 데이터 형식이 지정되어 <b>$5</b>의 해당합니다. 기존의 등록 정보의 일부에 의해 수도 <a href="$6">사용하지 않는 속성을 </a>입니다. 속성이 여전히 부족에서 찾을 수있는 페이지는 <a href="$7">목록은 싶었다 등록 정보 </a>입니다.',
	/*Messages for Flawed Attributes Special --disabled--*/
	'flawedattributes' => '결함 속성',
	'smw_fattributes' => '아래에 나열된 페이지를 잘못 정의된 속성을 갖고있습니다. 잘못된 속성의 개수가 주어질 브래킷에있습니다.',
	// Name of the URI Resolver Special (no content)
	'uriresolver' => '열린우리당 확인자',
	'smw_uri_doc' => '<p>열린우리당 확인자 <a href="http://www.w3.org/2001/tag/issues.html#httprange-14"> w3c 태그를 구현합니다 규명에 httprange-14</a>입니다. 인간을 치료하는 데 걸리는 웹사이트로 나타나지 않습니다.</p>',
	// Messages for ask Special
	'ask' => '의미 론적 검색',
	'smw_ask_doculink' => '의미 론적 검색',
	'smw_ask_sortby' => '열로 정렬',
	'smw_ask_ascorder' => '오름차순',
	'smw_ask_descorder' => '내림차순',
	'smw_ask_submit' => '검색 결과 찾기',
	'smw_ask_editquery' => '[Edit query]',
	'smw_ask_hidequery' => 'Hide query',
	'smw_ask_help' => 'Querying help',
	'smw_ask_queryhead' => 'Query',
	'smw_ask_printhead' => 'Additional printouts (optional)',
	// Messages for the search by property special
	'searchbyproperty' => '검색을 통해 재산',
	'smw_sbv_docu' => '<p>주어진 속성이있는 모든 페이지를 검색 및 값입니다.</p>',
	'smw_sbv_noproperty' => '<p>한 속성을 입력하세요.</p>',
	'smw_sbv_novalue' => '<p>유효한 값을 입력하시기 바랍니다의 재산, 또는 내용에 대한 모든 속성 값을 “$1.”</p>',
	'smw_sbv_displayresult' => '모든 페이지의 목록이있는 속성이 "$1" 와 값 “$2”',
	'smw_sbv_property' => '부동산',
	'smw_sbv_value' => '값',
	'smw_sbv_submit' => '검색 결과 찾기',
	// Messages for the browsing special
	'browse' => '위키 뉴스',
	'smw_browse_article' => '페이지의 이름을 입력하여 검색을 시작합니다.',
	'smw_browse_go' => '바둑',
	'smw_browse_more' => '&hellip;',
	// Messages for the page property special
	'pageproperty' => '검색 페이지에서 등록 정보에서',
	'smw_pp_docu' => '검색에 대한 모든 fillers의 속성이 주어진 페이지에서입니다. 한 페이지와 속성을 모두 입력하시기 바랍니다.',
	'smw_pp_from' => '페이지에서',
	'smw_pp_type' => '부동산',
	'smw_pp_submit' => '검색 결과 찾기',
	// Generic messages for result navigation in all kinds of search pages
	'smw_result_prev' => '이전',
	'smw_result_next' => '내년',
	'smw_result_results' => '결과',
	'smw_result_noresults' => '죄송합니다, 결과가없습니다.'
);

protected $m_DatatypeLabels = array(
	'_wpg' => '인공', // name of page datatype
	'_str' => '배열의 문자',  // name of the string type
	'_txt' => '텍스트',  // name of the text type
	//'_boo' => '부울',  // name of the boolean type
	'_num' => '번호',  // name for the datatype of numbers
	'_geo' => '지리적 좌표', // name of the geocoord type
	'_tem' => '온도',  // name of the temperature type
	'_dat' => '날짜',  // name of the datetime (calendar) type
	'_hda' => '역사적 날짜', // name of the historical-range calendar type
	'_ema' => '이메일',  // name of the email type
	'_uri' => '하십시오',  // name of the URL type
	'_anu' => '열린우리당 해설'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'열린우리당'        	=> '_uri',
	'부동 소수점'       	=> '_num',
	'정수'     				=> '_num',
	'열거'		 			=> '_str',
	// support English aliases:
	'Page'                  => '_wpg',
	'String'                => '_str',
	'Text'                  => '_txt',
	'Number'                => '_num',
	'Geographic coordinate' => '_geo',
	'Temperature'           => '_tem',
	'Date'                  => '_dat',
	'Historical date'       => '_hda',
	'Email'                 => '_ema',
	'URL'							=>	'_uri',
	'URI'							=>	'_uri',
	'Float'       				=> '_num',
	'Integer'     				=> '_num',
	'Enumeration' 				=> '_str',
	'Annotation URI'        => '_anu'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => '이 유형',
	SMW_SP_HAS_URI   => '이에 상응하는 열린우리당',
	SMW_SP_SUBPROPERTY_OF => '서브-재산',
	SMW_SP_DISPLAY_UNITS => '디스플레이 유닛',
	SMW_SP_IMPORTED_FROM => '수입',
	SMW_SP_CONVERSION_FACTOR => '에 해당합니다',
	SMW_SP_SERVICE_LINK => '제공 서비스',
	SMW_SP_POSSIBLE_VALUE => '허용 값'
);

protected $m_SpecialPropertyAliases = array(
	'디스플레이 기기' => SMW_SP_DISPLAY_UNITS,
	// support English aliases for special properties
	'Has type'          => SMW_SP_HAS_TYPE,
	'Equivalent URI'    => SMW_SP_HAS_URI,
	'Subproperty of'    => SMW_SP_SUBPROPERTY_OF,
	'Display units'     => SMW_SP_DISPLAY_UNITS,
	'Display unit'		  => SMW_SP_DISPLAY_UNITS,
	'Imported from'     => SMW_SP_IMPORTED_FROM,
	'Corresponds to'    => SMW_SP_CONVERSION_FACTOR,
	'Provides service'  => SMW_SP_SERVICE_LINK,
	'Allows value'      => SMW_SP_POSSIBLE_VALUE
);

protected $m_Namespaces = array(
	SMW_NS_RELATION       => '관계',
	SMW_NS_RELATION_TALK  => '관계토론',
	SMW_NS_PROPERTY       => '부동산',
	SMW_NS_PROPERTY_TALK  => '부동산토론',
	SMW_NS_TYPE           => '유형',
	SMW_NS_TYPE_TALK      => '유형토론'
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


