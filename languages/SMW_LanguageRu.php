<?php
/**
 * @file
 * @ingroup SMWLanguage
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

global $smwgIP;
include_once ( $smwgIP . 'languages/SMW_Language.php' );

/**
 * Russian language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Dmitry Khoroshev cnit\@uniyar.ac.ru
 * @author Yury Katkov
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageRu extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Страница', // name of page datatype
		'_txt' => 'Текст',  // name of the text type (very long strings)
		'_cod' => 'Исходный код',  // name of the (source) code type
		'_boo' => 'Булево',  // name of the boolean type
		'_num' => 'Число', // name for the datatype of numbers
		'_geo' => 'Географические координаты', // name of the geocoord type
		'_tem' => 'Температура',  // name of the temperature type
		'_dat' => 'Дата',  // name of the datetime (calendar) type
		'_ema' => 'Почта',  // name of the email type
		'_uri' => 'URL',  // name of the URL type
		'_anu' => 'URI аннотации',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Номер телефона',  // name of the telephone (URI) type
		'_rec' => 'Запись', // name of record data type
		'_qty' => 'Количество', // name of the number type with units of measurement
	);

	protected $m_DatatypeAliases = array(
		'URI'                   => '_uri',
		'Целое'                 => '_num',
		'Десятичное'            => '_num',
		'Плавающее'             => '_num',
		'Перечисление'          => '_txt',
		'Строка'                => '_txt',  // old name of the string type
		'Телефон'               => '_tel',
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Имеет тип',
		'_URI'  => 'Эквивалентный URI',
		'_SUBP' => 'Подчиненное свойству',
		'_SUBC' => 'Вложенная категория',
		'_UNIT' => 'Отображаемые единицы',
		'_IMPO' => 'Импортировано из',
		'_CONV' => 'Относится к',
		'_SERV' => 'Предоставляет сервис',
		'_PVAL' => 'Допустимое значение',
		'_MDAT' => 'Дата последней правки',
		'_CDAT' => 'Дата создания',
		'_NEWP' => 'Новая страница',
		'_LEDT' => 'Последний редактор',
		'_ERRP' => 'Содержит некорректное значение',
		'_LIST' => 'Имеет поля',
		'_SOBJ' => 'Содержит субобъект',
		'_ASK'  => 'Содержит запрос',
		'_ASKST'=> 'Строка запроса',
		'_ASKFO'=> 'Формат запроса',
		'_ASKSI'=> 'Размер запроса',
		'_ASKDE'=> 'Глубина запроса',
		'_ASKDU'=> 'Длительность запроса',
		'_MEDIA'=> 'Тип медиа',
		'_MIME' => 'MIME-тип'
	);

	protected $m_SpecialPropertyAliases = array(
		'Тип данных'           => '_TYPE',
		'Отображаемая единица' => '_UNIT'
	);


	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => 'Свойство',
		SMW_NS_PROPERTY_TALK  => 'Обсуждение_свойства',
		SMW_NS_TYPE           => 'Тип',
		SMW_NS_TYPE_TALK      => 'Обсуждение_типа',
		SMW_NS_CONCEPT        => 'Концепция',
		SMW_NS_CONCEPT_TALK   => 'Обсуждение_концепции'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_DMY, SMW_MDY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря" );

	protected $m_monthsshort = array( "янв", "фев", "мар", "апр", "мая", "июн", "июл", "авг", "сен", "окт", "ноя", "дек" );

}
