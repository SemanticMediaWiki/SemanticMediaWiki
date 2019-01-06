<?php

/**
 * Magic words
 *
 * @author أحمد غربية <ahmad@arabdigitalexpression.org>
 * @file
 * @ingroup Extensions
 * @ingroup SMWLanguage
 */

$magicWords = [];

/** English (English) */
$magicWords['en'] = [
	'ask' => [ 0, 'ask' ],
	'show' => [ 0, 'show' ],
	'info' => [ 0, 'info' ],
	'concept' => [ 0, 'concept' ],
	'subobject' => [ 0, 'subobject' ],
	'smwdoc' => [ 0, 'smwdoc' ],
	'set' => [ 0, 'set' ],
	'set_recurring_event' => [ 0, 'set_recurring_event' ],
	'declare' => [ 0, 'declare' ],
	'SMW_NOFACTBOX' => [ 0, '__NOFACTBOX__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__SHOWFACTBOX__' ],
];

/** Afrikaans (Afrikaans) */
$magicWords['af'] = [
	'ask' => [ 0, 'vra', 'ask' ],
	'show' => [ 0, 'wys', 'show' ],
	'concept' => [ 0, 'konsep', 'concept' ],
	'set' => [ 0, 'stel', 'set' ],
	'declare' => [ 0, 'verklaar', 'declare' ],
];

/** Arabic (العربية) */
$magicWords['ar'] = [
	'ask' => [ 0, 'سؤال' ],
	'show' => [ 0, 'عرض' ],
	'info' => [ 0, 'معلومات' ],
	'concept' => [ 0, 'مفهوم' ],
	'subobject' => [ 0, 'كائن_فرعي' ],
	'smwdoc' => [ 0, 'وثائق_سمو', 'توثيق_سمو' ],
	'set' => [ 0, 'تعيين' ], //من تعيين القيمة للمتغير\الكائن
	'set_recurring_event' => [ 0, 'تعيين_حدث_متكرر' ],
	'declare' => [ 0, 'إقرار', 'إعلان' ],
	'SMW_NOFACTBOX' => [ 0, '__لا_صندوق_حقائق__', '__لا_صندوق_حقيقة__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__عرض_صندوق_الحقائق__', '__عرض_صندوق_الحقيقة__' ,]
];

/** Egyptian Arabic (مصرى) */
$magicWords['arz'] = [
	'ask' => [ 0, 'سؤال' ],
	'show' => [ 0, 'عرض' ],
	'info' => [ 0, 'معلومات' ],
	'concept' => [ 0, 'مبدأ' ],
	'subobject' => [ 0, 'كائن_فرعى' ],
	'smwdoc' => [ 0, 'توثيق_سمو' ],
	'set' => [ 0, 'مجموعة' ],
	'set_recurring_event' => [ 0, 'ضبط_حدث_جارى' ],
	'declare' => [ 0, 'إعلان' ],
	'SMW_NOFACTBOX' => [ 0, '__لا_صندوق_حقيقة__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__عرض_صندوق_الحقيقة__' ],
];

/** Assamese (অসমীয়া) */
$magicWords['as'] = [
	'ask' => [ 0, 'সোধক' ],
	'show' => [ 0, 'দেখুৱাওক' ],
	'info' => [ 0, 'তথ্য' ],
];

/** Breton (brezhoneg) */
$magicWords['br'] = [
	'ask' => [ 0, 'goulenn' ],
	'show' => [ 0, 'diskouez' ],
	'info' => [ 0, 'keloù' ],
	'concept' => [ 0, 'meizad' ],
	'declare' => [ 0, 'disklêriañ' ],
];

/** Czech (čeština) */
$magicWords['cs'] = [
	'ask' => [ 0, 'otázka' ],
	'show' => [ 0, 'zobrazit' ],
	'set' => [ 0, 'nastavit' ],
];

/** Chuvash (Чӑвашла) */
$magicWords['cv'] = [
	'SMW_NOFACTBOX' => [ 0, '__NOFACTBOX__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__SHOWFACTBOX__' ],
];

/** German (Deutsch) */
$magicWords['de'] = [
	'ask' => [ 0, 'frage' ],
	'show' => [ 0, 'zeige' ],
	'info' => [ 0, 'informiere' ],
	'concept' => [ 0, 'konzept' ],
	'subobject' => [ 0, 'unterobjekt' ],
	'smwdoc' => [ 0, 'smwdok' ],
	'set' => [ 0, 'setze' ],
	'set_recurring_event' => [ 0, 'setze_wiederholung' ],
	'declare' => [ 0, 'deklariere' ],
	'SMW_NOFACTBOX' => [ 0, '__KEINE_FAKTENANZEIGE__', '__KEINEFAKTENANZEIGE__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__FAKTENANZEIGE__' ],
];

/** Zazaki (Zazaki) */
$magicWords['diq'] = [
	'ask' => [ 0, 'perske' ],
	'show' => [ 0, 'bımocne' ],
	'info' => [ 0, 'zanışe' ],
	'concept' => [ 0, 'konsept' ],
	'subobject' => [ 0, 'bınobce' ],
	'set' => [ 0, 'saz' ],
	'declare' => [ 0, 'ilaniye' ],
	'SMW_NOFACTBOX' => [ 0, '__DORARAŞTAYÇINİYA__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__DORARAŞTAYBIMOCNE__' ],
];

/** Spanish (español) */
$magicWords['es'] = [
	'ask' => [ 0, 'preguntar', 'pregunta' ],
	'show' => [ 0, 'muestra', 'mostrar' ],
	'info' => [ 0, 'informacion', 'información' ],
	'concept' => [ 0, 'concepto' ],
	'set' => [ 0, 'establecer', 'determinar' ],
	'set_recurring_event' => [ 0, 'establecer_evento_recurrente', 'determinar_evento_recurrente' ],
	'declare' => [ 0, 'declarar', 'declara' ],
];

/** Persian (فارسی) */
$magicWords['fa'] = [
	'ask' => [ 0, 'پرسش','سوال' ],
	'show' => [ 0, 'نمایش' ],
	'info' => [ 0, 'اطلاع' ],
	'concept' => [ 0, 'مفهوم' ],
	'subobject' => [ 0, 'جزءشیء' ],
	'smwdoc' => [ 0, 'smwdoc' ],
	'set' => [ 0, 'مجموعه' ],
	'set_recurring_event' => [ 0, 'set_recurring_event' ],
	'declare' => [ 0, 'declare' ],
	'SMW_NOFACTBOX' => [ 0, '__NOFACTBOX__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__SHOWFACTBOX__' ],
];

/** French (français) */
$magicWords['fr'] = [
	'ask' => [ 0, 'demander' ],
	'show' => [ 0, 'afficher' ],
	'info' => [ 0, 'infos' ],
	'concept' => [ 0, 'concept' ],
	'subobject' => [ 0, 'sousobjet' ],
	'smwdoc' => [ 0, 'docsmw' ],
	'set' => [ 0, 'définit' ],
	'set_recurring_event' => [ 0, 'définit_périodique' ],
	'declare' => [ 0, 'déclare' ],
	'SMW_NOFACTBOX' => [ 0, '__SANSBOÎTEFAITS__', '__SANSBOITEFAITS__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__AFFICHERBOÎTEFAITS__', '__AFFICHERBOITEFAITS__' ],
];

/** Western Frisian (Frysk) */
$magicWords['fy'] = [
	'info' => [ 0, 'ynfo' ],
];

/** Hebrew (עברית) */
$magicWords['he'] = [
	'ask' => [ 0, 'שאל' ],
];

/** Indonesian (Bahasa Indonesia) */
$magicWords['id'] = [
	'ask' => [ 0, 'tanya' ],
	'show' => [ 0, 'tampilkan' ],
	'info' => [ 0, 'info' ],
	'concept' => [ 0, 'konsep' ],
	'set' => [ 0, 'tetapkan' ],
	'declare' => [ 0, 'deklarasi' ],
];

/** Igbo (Igbo) */
$magicWords['ig'] = [
	'ask' => [ 0, 'jüo', 'ask' ],
];

/** Georgian (ქართული) */
$magicWords['ka'] = [
	'ask' => [ 0, 'კითხვა' ],
	'show' => [ 0, 'ჩვენება' ],
	'info' => [ 0, 'ინფო' ],
];

/** Korean (한국어) */
$magicWords['ko'] = [
	'ask' => [ 0, '묻기' ],
	'show' => [ 0, '보이기' ],
	'info' => [ 0, '정보' ],
	'concept' => [ 0, '생각' ],
	'subobject' => [ 0, '하위객체' ],
	'smwdoc' => [ 0, 'smw문서' ],
	'set' => [ 0, '설정' ],
	'set_recurring_event' => [ 0, '반복_일정_설정' ],
	'declare' => [ 0, '선언' ],
	'SMW_NOFACTBOX' => [ 0, '__사실상자숨김__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__사실상자보이기__', '__사실상자표시__' ],
];

/** Cornish (kernowek) */
$magicWords['kw'] = [
	'ask' => [ 0, 'govyn' ],
	'show' => [ 0, 'diskwedhes' ],
	'info' => [ 0, 'kedhlow' ],
	'set' => [ 0, 'settya' ],
];

/** Luxembourgish (Lëtzebuergesch) */
$magicWords['lb'] = [
	'ask' => [ 0, 'froen' ],
	'show' => [ 0, 'weisen' ],
	'concept' => [ 0, 'Konzept' ],
];

/** Macedonian (македонски) */
$magicWords['mk'] = [
	'ask' => [ 0, 'прашај' ],
	'show' => [ 0, 'прикажи' ],
	'info' => [ 0, 'инфо' ],
	'concept' => [ 0, 'поим' ],
	'subobject' => [ 0, 'подобјект' ],
	'smwdoc' => [ 0, 'смвдок' ],
	'set' => [ 0, 'постави' ],
	'set_recurring_event' => [ 0, 'постави_повторлив_настан' ],
	'declare' => [ 0, 'изјави' ],
	'SMW_NOFACTBOX' => [ 0, '__БЕЗФАКТКУТИЈА__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__ПРИКАЖИФАКТКУТИЈА__' ],
];

/** Malayalam (മലയാളം) */
$magicWords['ml'] = [
	'ask' => [ 0, 'ചോദിക്കുക' ],
	'show' => [ 0, 'പ്രദർശിപ്പിക്കുക' ],
	'info' => [ 0, 'വിവരം' ],
	'concept' => [ 0, 'ആശയം' ],
	'set' => [ 0, 'ഗണം' ],
	'declare' => [ 0, 'പ്രഖ്യാപിക്കുക' ],
];

/** Marathi (मराठी) */
$magicWords['mr'] = [
	'ask' => [ 0, 'विचारा' ],
	'show' => [ 0, 'दाखवा' ],
	'info' => [ 0, 'माहिती' ],
	'concept' => [ 0, 'कंसेप्ट', 'कल्पना' ],
	'set' => [ 0, 'प्रयुक्त', 'सेट', 'स्थापित' ],
	'set_recurring_event' => [ 0, 'प्रयुक्त_पुर्न_कार्य' ],
	'declare' => [ 0, 'प्रकटकरा' ],
	'SMW_NOFACTBOX' => [ 0, '__फॅक्टबॉक्सनाही__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__फॅक्टबॉक्सदाखवा__' ],
];

/** Low Saxon (Netherlands) (Nedersaksies) */
$magicWords['nds-nl'] = [
	'concept' => [ 0, 'konsept' ],
	'set_recurring_event' => [ 0, 'herhaolende_gebeurtenisse_instellen' ],
	'declare' => [ 0, 'deklareren' ],
	'SMW_NOFACTBOX' => [ 0, '__GIENFEITENKAODER__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__FEITENKAODERWEERGEVEN__' ],
];

/** Dutch (Nederlands) */
$magicWords['nl'] = [
	'ask' => [ 0, 'vragen' ],
	'show' => [ 0, 'weergeven' ],
	'subobject' => [ 0, 'onderobject' ],
	'set' => [ 0, 'instellen' ],
	'set_recurring_event' => [ 0, 'herhalende_gebeurtenis_instellen' ],
	'declare' => [ 0, 'declareren' ],
	'SMW_NOFACTBOX' => [ 0, '__GEENFEITENKADER__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__FEITENKADERWEERGEVEN__' ],
];

/** Punjabi (ਪੰਜਾਬੀ) */
$magicWords['pa'] = [
	'ask' => [ 0, 'ਪੁੱਛੋ' ],
	'show' => [ 0, 'ਵਿਖਾਓ' ],
	'info' => [ 0, 'ਜਾਣਕਾਰੀ' ],
];

/** Polish (polski) */
$magicWords['pl'] = [
	'ask' => [ 0, 'pytanie' ],
	'show' => [ 0, 'pokaż' ],
	'info' => [ 0, 'informacja' ],
	'concept' => [ 0, 'koncept' ],
	'set' => [ 0, 'ustaw' ],
	'declare' => [ 0, 'zadeklaruj' ],
];

/** Pashto (پښتو) */
$magicWords['ps'] = [
	'ask' => [ 0, 'پوښتل', 'ask' ],
	'show' => [ 0, 'ښکاره_کول', 'show' ],
	'info' => [ 0, 'مالومات', 'info' ],
];

/** Portuguese (português) */
$magicWords['pt'] = [
	'ask' => [ 0, 'consultar' ],
	'show' => [ 0, 'mostrar' ],
	'concept' => [ 0, 'conceito' ],
	'subobject' => [ 0, 'subobjeto' ],
	'set' => [ 0, 'definir' ],
	'set_recurring_event' => [ 0, 'definir_evento_recorrente' ],
	'declare' => [ 0, 'declarar' ],
	'SMW_NOFACTBOX' => [ 0, '__SEMCAIXADEFATOS__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__EXIBIRCAIXADEFATOS__' ],
];

/** Brazilian Portuguese (português do Brasil) */
$magicWords['pt-br'] = [
	'ask' => [ 0, 'consultar' ],
	'show' => [ 0, 'mostrar' ],
	'info' => [ 0, 'info' ],
	'concept' => [ 0, 'conceito' ],
	'subobject' => [ 0, 'subobjeto' ],
	'smwdoc' => [ 0, 'smwdoc' ],
	'set' => [ 0, 'definir' ],
	'set_recurring_event' => [ 0, 'definir_evento_recorrente' ],
	'declare' => [ 0, 'declarar' ],
	'SMW_NOFACTBOX' => [ 0, '__SEMCAIXADEFATOS__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__EXIBIRCAIXADEFATOS__' ],
];

/** Serbian (Cyrillic script) (српски (ћирилица)‎) */
$magicWords['sr-ec'] = [
	'ask' => [ 0, 'питај' ],
	'show' => [ 0, 'прикажи' ],
	'info' => [ 0, 'подаци' ],
	'concept' => [ 0, 'концепт' ],
	'set' => [ 0, 'постави' ],
	'set_recurring_event' => [ 0, 'постави_периодични_догађај' ],
	'declare' => [ 0, 'одреди' ],
];

/** Serbian (Latin script) (srpski (latinica)‎) */
$magicWords['sr-el'] = [
	'ask' => [ 0, 'pitaj' ],
	'show' => [ 0, 'prikaži' ],
	'info' => [ 0, 'podaci' ],
	'concept' => [ 0, 'koncept' ],
	'set' => [ 0, 'postavi' ],
	'set_recurring_event' => [ 0, 'postavi_periodični_događaj' ],
	'declare' => [ 0, 'odredi' ],
	'SMW_NOFACTBOX' => [ 0, '__BEZČINJENICA__', '__BEZ_ČINJENICA__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__PRIKAŽIČINJENICE__', '__PRIKAŽI_ČINJENICE__' ],
];

/** Swedish (svenska) */
$magicWords['sv'] = [
	'ask' => [ 0, 'fråga', 'ask' ],
	'show' => [ 0, 'visa', 'show' ],
	'concept' => [ 0, 'koncept', 'concept' ],
];

/** Tatar (Cyrillic script) (татарча) */
$magicWords['tt-cyrl'] = [
	'ask' => [ 0, 'сорау' ],
	'show' => [ 0, 'күрсәт' ],
	'info' => [ 0, 'мәгълүмат' ],
];

/** Vietnamese (Tiếng Việt) */
$magicWords['vi'] = [
	'ask' => [ 0, 'hỏi' ],
	'show' => [ 0, 'hiển_thị' ],
	'info' => [ 0, 'thông_tin' ],
	'concept' => [ 0, 'khái_niệm' ],
	'set' => [ 0, 'đặt' ],
];

/** Simplified Chinese (中文（简体）‎) */
$magicWords['zh-hans'] = [
	'ask' => [ 0, '询问' ],
	'show' => [ 0, '显示' ],
	'info' => [ 0, '信息' ],
	'concept' => [ 0, '概念' ],
	'subobject' => [ 0, '子对象' ],
	'smwdoc' => [ 0, 'SMW文档' ],
	'set' => [ 0, '设置' ],
	'set_recurring_event' => [ 0, '设置循环活动' ],
	'declare' => [ 0, '宣布' ],
	'SMW_NOFACTBOX' => [ 0, '__无实际内容框__' ],
	'SMW_SHOWFACTBOX' => [ 0, '__显示实际内容框__' ],
];

/** Traditional Chinese (中文（繁體）‎) */
$magicWords['zh-hant'] = [
	'ask' => [ 0, '訪問' ],
	'show' => [ 0, '顯示' ],
	'info' => [ 0, '資訊' ],
	'smwdoc' => [ 0, 'SMW檔案' ],
	'set' => [ 0, '設定' ],
	'set_recurring_event' => [ 0, '設定循環活動', '設置定期活動' ],
];
