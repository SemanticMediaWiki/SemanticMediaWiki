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
 * Arabic language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Mahmoud Zouari  mahmoudzouari@yahoo.fr http://www.cri.ensmp.fr
 * @author Meno25
 * @author Ahmad Gharbeia أحمد غربية <ahmad@arabdigitalexpression.org>
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageAr extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'صفحة', // name of page datatype
		'_txt' => 'نص',  // name of the text type
		'_cod' => 'كود',  // name of the (source) code type
		'_boo' => 'منطقي',  // name of the boolean type
		'_num' => 'عدد',  // name for the datatype of numbers
		'_geo' => 'إحداثيات جغرافية', // name of the geocoord type
		'_tem' => 'درجة حرارة',  // name of the temperature type
		'_dat' => 'تاريخ',  // name of the datetime (calendar) type
		'_ema' => 'عنوان بريد إلكتروني',  // name of the email type
		'_uri' => 'URL',  // name of the URL type
		'_anu' => 'URL حاشية',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'رقم هاتفي',  // name of the telephone (URI) type
		'_rec' => 'ّسجل', // name of record data type
		'_qty' => 'كميّة', // name of the number type with units of measurement
		'_mlt_rec' => 'نص أحادي اللغة',
	);

	protected $m_DatatypeAliases = array(
		'URI'         => '_uri',
		'Float'       => '_num',
		'Integer'     => '_num',
		 /*LTR hint for text editors*/ 'سلسلة' => '_txt',  // old name of the string type
		'Enumeration' => '_txt'
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'من النوع',
		'_URI'  => 'URL مكافئ',
		'_SUBP' => 'خصيصة فرعية من',
		'_SUBC' => 'مُصنّف على التصنيف',
		'_UNIT' => 'وحدة العرض',
		'_IMPO' => 'مستوردة من',
		'_CONV' => 'توافق',
		'_SERV' => 'تقدّم خدمة',
		'_PVAL' => 'تقبل القيمة',
		'_MDAT' => 'تاريخ التعديل',
		'_CDAT' => 'تاريخ الإنشاء',
		'_NEWP' => 'هي صفحة جديدة',
		'_LEDT' => 'آخر مَن حرّرها',
		'_ERRP' => 'فيها قيمة غير صحيحة في',//الخصيصة
		'_LIST' => 'يتأّلف من الحقول',//السجِّل
		'_SOBJ' => 'تحوي الكائن الفرعي', //الصفحة
		'_ASK'  => 'فيها الاستعلام',//الصفحة
		'_ASKST'=> 'نص الاستعلام',
		'_ASKFO'=> 'صيغة الاستعلام',
		'_ASKSI'=> 'حجم الاستعلام',
		'_ASKDE'=> 'عمق الاستعلام',
		'_ASKDU'=> 'مدّة الاستعلام',
		'_MEDIA'=> 'نوع الميديا',
		'_MIME' => 'نوع MIME',
		'_ERRC' => 'بها خطأ في المعالجة',
		'_ERRT' => 'لها وصف الخطأ في المعالجة',
		'_PREC'  => 'تعرض الدّقة إلى',
		'_LCODE' => 'رمز اللغة',
		'_TEXT'  => 'نص',
		'_PDESC' => 'لها وصف الخصيصة',
		'_PVAP'  => 'تقبل النمط',
		'_DTITLE' => 'تعرض العنوان',
		'_PVUC' => 'مقيّدة بالتفرّد',
	);

	protected $m_SpecialPropertyAliases = array(
		'وحدة العرض' => '_UNIT'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => 'خصيصة',
		SMW_NS_PROPERTY_TALK  => 'نقاش_الخصيصة',
		SMW_NS_TYPE           => 'نوع',
		SMW_NS_TYPE_TALK      => 'نقاش_النوع',
		SMW_NS_CONCEPT        => 'مفهوم',
		SMW_NS_CONCEPT_TALK   => 'نقاش_المفهوم'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو", "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر" );

	protected $m_monthsshort = array( "ينا", "فبر", "مارس", "أبريل", "ماي", "يوني", "يولي", "غسط", "سبت", "أكت", "نوف", "ديس" );

}
