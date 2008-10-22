<?php
/**
 * @file
 * @ingroup SMWLanguage
 */

/*
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if (!defined('MEDIAWIKI')) die();

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

/**
 * Korean language labels for important SMW labels (namespaces, datatypes,...).
 * Created by Terry A. Hurlbut using an automatic translation machine. Please use with caution,
 * and send suggestions for improvement to mak\@aifb.uni-karlsruhe.de. The original English messages
 * found in the file SMW_LanguageEn.php may be useful as a reference.
 *
 * @author Terry A. Hurlbut
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageKo extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => '인공', // name of page datatype
	'_str' => '배열의 문자',  // name of the string type
	'_txt' => '텍스트',  // name of the text type
	'_cod' => 'Code',  // name of the (source) code type //TODO: translate
	'_boo' => '부울',  // name of the boolean type
	'_num' => '번호',  // name for the datatype of numbers
	'_geo' => '지리적 좌표', // name of the geocoord type
	'_tem' => '온도',  // name of the temperature type
	'_dat' => '날짜',  // name of the datetime (calendar) type
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
	'Boolean'               => '_boo',
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
	'_TYPE' => '이 유형',
	'_URI'  => '이에 상응하는 열린우리당',
	'_SUBP' => '서브-재산',
	'_UNIT' => '디스플레이 유닛',
	'_IMPO' => '수입',
	'_CONV' => '에 해당합니다',
	'_SERV' => '제공 서비스',
	'_PVAL' => '허용 값'
);

protected $m_SpecialPropertyAliases = array(
	'디스플레이 기기' => '_UNIT',
	// support English aliases for special properties
	'Has type'          => '_TYPE',
	'Equivalent URI'    => '_URI',
	'Subproperty of'    => '_SUBP',
	'Display units'     => '_UNIT',
	'Display unit'      => '_UNIT',
	'Imported from'     => '_IMPO',
	'Corresponds to'    => '_CONV',
	'Provides service'  => '_SERV',
	'Allows value'      => '_PVAL'
);

protected $m_Namespaces = array(
	SMW_NS_RELATION       => '관계',
	SMW_NS_RELATION_TALK  => '관계토론',
	SMW_NS_PROPERTY       => '부동산',
	SMW_NS_PROPERTY_TALK  => '부동산토론',
	SMW_NS_TYPE           => '유형',
	SMW_NS_TYPE_TALK      => '유형토론',
	SMW_NS_CONCEPT        => 'Concept', // TODO: translate
	SMW_NS_CONCEPT_TALK   => 'Concept_talk' // TODO: translate
);

protected $m_NamespaceAliases = array(
	// support English aliases for namespaces
	'Relation'      => SMW_NS_RELATION,
	'Relation_talk' => SMW_NS_RELATION_TALK,
	'Property'      => SMW_NS_PROPERTY,
	'Property_talk' => SMW_NS_PROPERTY_TALK,
	'Type'          => SMW_NS_TYPE,
	'Type_talk'     => SMW_NS_TYPE_TALK,
	'Concept'       => SMW_NS_CONCEPT,
	'Concept_talk'  => SMW_NS_CONCEPT_TALK
);
}


