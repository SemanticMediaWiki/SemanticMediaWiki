<?php

/**
 * Magic words
 *
 * @author أحمد غربية <ahmad@arabdigitalexpression.org>
 * @file
 * @ingroup Extensions
 * @ingroup SMWLanguage
 */

$magicWords = array();

/** English (English) */
$magicWords['en'] = array(
	'ask' => array( 0, 'ask' ),
	'show' => array( 0, 'show' ),
	'info' => array( 0, 'info' ),
	'concept' => array( 0, 'concept' ),
	'subobject' => array( 0, 'subobject' ),
	'smwdoc' => array( 0, 'smwdoc' ),
	'set' => array( 0, 'set' ),
	'set_recurring_event' => array( 0, 'set_recurring_event' ),
	'declare' => array( 0, 'declare' ),
	'SMW_NOFACTBOX' => array( 0, '__NOFACTBOX__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__SHOWFACTBOX__' ),
);

/** Afrikaans (Afrikaans) */
$magicWords['af'] = array(
	'ask' => array( 0, 'vra', 'ask' ),
	'show' => array( 0, 'wys', 'show' ),
	'concept' => array( 0, 'konsep', 'concept' ),
	'set' => array( 0, 'stel', 'set' ),
	'declare' => array( 0, 'verklaar', 'declare' ),
);

/** Arabic (العربية) */
$magicWords['ar'] = array(
	'ask' => array( 0, 'سؤال' ),
	'show' => array( 0, 'عرض' ),
	'info' => array( 0, 'معلومات' ),
	'concept' => array( 0, 'مفهوم' ),
	'subobject' => array( 0, 'كائن_فرعي' ),
	'smwdoc' => array( 0, 'وثائق_سمو', 'توثيق_سمو' ),
	'set' => array( 0, 'تعيين' ), //من تعيين القيمة للمتغير\الكائن
	'set_recurring_event' => array( 0, 'تعيين_حدث_متكرر' ),
	'declare' => array( 0, 'إقرار', 'إعلان' ),
	'SMW_NOFACTBOX' => array( 0, '__لا_صندوق_حقائق__', '__لا_صندوق_حقيقة__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__عرض_صندوق_الحقائق__', '__عرض_صندوق_الحقيقة__' ,)
);

/** Egyptian Arabic (مصرى) */
$magicWords['arz'] = array(
	'ask' => array( 0, 'سؤال' ),
	'show' => array( 0, 'عرض' ),
	'info' => array( 0, 'معلومات' ),
	'concept' => array( 0, 'مبدأ' ),
	'subobject' => array( 0, 'كائن_فرعى' ),
	'smwdoc' => array( 0, 'توثيق_سمو' ),
	'set' => array( 0, 'مجموعة' ),
	'set_recurring_event' => array( 0, 'ضبط_حدث_جارى' ),
	'declare' => array( 0, 'إعلان' ),
	'SMW_NOFACTBOX' => array( 0, '__لا_صندوق_حقيقة__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__عرض_صندوق_الحقيقة__' ),
);

/** Assamese (অসমীয়া) */
$magicWords['as'] = array(
	'ask' => array( 0, 'সোধক' ),
	'show' => array( 0, 'দেখুৱাওক' ),
	'info' => array( 0, 'তথ্য' ),
);

/** Breton (brezhoneg) */
$magicWords['br'] = array(
	'ask' => array( 0, 'goulenn' ),
	'show' => array( 0, 'diskouez' ),
	'info' => array( 0, 'keloù' ),
	'concept' => array( 0, 'meizad' ),
	'declare' => array( 0, 'disklêriañ' ),
);

/** Czech (čeština) */
$magicWords['cs'] = array(
	'ask' => array( 0, 'otázka' ),
	'show' => array( 0, 'zobrazit' ),
	'set' => array( 0, 'nastavit' ),
);

/** Chuvash (Чӑвашла) */
$magicWords['cv'] = array(
	'SMW_NOFACTBOX' => array( 0, '__NOFACTBOX__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__SHOWFACTBOX__' ),
);

/** German (Deutsch) */
$magicWords['de'] = array(
	'ask' => array( 0, 'frage' ),
	'show' => array( 0, 'zeige' ),
	'info' => array( 0, 'informiere' ),
	'concept' => array( 0, 'konzept' ),
	'subobject' => array( 0, 'unterobjekt' ),
	'smwdoc' => array( 0, 'smwdok' ),
	'set' => array( 0, 'setze' ),
	'set_recurring_event' => array( 0, 'setze_wiederholung' ),
	'declare' => array( 0, 'deklariere' ),
	'SMW_NOFACTBOX' => array( 0, '__KEINE_FAKTENANZEIGE__', '__KEINEFAKTENANZEIGE__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__FAKTENANZEIGE__' ),
);

/** Zazaki (Zazaki) */
$magicWords['diq'] = array(
	'ask' => array( 0, 'perske' ),
	'show' => array( 0, 'bımocne' ),
	'info' => array( 0, 'zanışe' ),
	'concept' => array( 0, 'konsept' ),
	'subobject' => array( 0, 'bınobce' ),
	'set' => array( 0, 'saz' ),
	'declare' => array( 0, 'ilaniye' ),
	'SMW_NOFACTBOX' => array( 0, '__DORARAŞTAYÇINİYA__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__DORARAŞTAYBIMOCNE__' ),
);

/** Spanish (español) */
$magicWords['es'] = array(
	'ask' => array( 0, 'preguntar', 'pregunta' ),
	'show' => array( 0, 'muestra', 'mostrar' ),
	'info' => array( 0, 'informacion', 'información' ),
	'concept' => array( 0, 'concepto' ),
	'set' => array( 0, 'establecer', 'determinar' ),
	'set_recurring_event' => array( 0, 'establecer_evento_recurrente', 'determinar_evento_recurrente' ),
	'declare' => array( 0, 'declarar', 'declara' ),
);

/** French (français) */
$magicWords['fr'] = array(
	'ask' => array( 0, 'demander' ),
	'show' => array( 0, 'afficher' ),
	'info' => array( 0, 'infos' ),
	'concept' => array( 0, 'concept' ),
	'subobject' => array( 0, 'sousobjet' ),
	'smwdoc' => array( 0, 'docsmw' ),
	'set' => array( 0, 'définit' ),
	'set_recurring_event' => array( 0, 'définit_périodique' ),
	'declare' => array( 0, 'déclare' ),
	'SMW_NOFACTBOX' => array( 0, '__SANSBOÎTEFAITS__', '__SANSBOITEFAITS__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__AFFICHERBOÎTEFAITS__', '__AFFICHERBOITEFAITS__' ),
);

/** Western Frisian (Frysk) */
$magicWords['fy'] = array(
	'info' => array( 0, 'ynfo' ),
);

/** Hebrew (עברית) */
$magicWords['he'] = array(
	'ask' => array( 0, 'שאל' ),
);

/** Indonesian (Bahasa Indonesia) */
$magicWords['id'] = array(
	'ask' => array( 0, 'tanya' ),
	'show' => array( 0, 'tampilkan' ),
	'info' => array( 0, 'info' ),
	'concept' => array( 0, 'konsep' ),
	'set' => array( 0, 'tetapkan' ),
	'declare' => array( 0, 'deklarasi' ),
);

/** Igbo (Igbo) */
$magicWords['ig'] = array(
	'ask' => array( 0, 'jüo', 'ask' ),
);

/** Georgian (ქართული) */
$magicWords['ka'] = array(
	'ask' => array( 0, 'კითხვა' ),
	'show' => array( 0, 'ჩვენება' ),
	'info' => array( 0, 'ინფო' ),
);

/** Korean (한국어) */
$magicWords['ko'] = array(
	'ask' => array( 0, '묻기' ),
	'show' => array( 0, '보이기' ),
	'info' => array( 0, '정보' ),
	'concept' => array( 0, '생각' ),
	'subobject' => array( 0, '하위객체' ),
	'smwdoc' => array( 0, 'smw문서' ),
	'set' => array( 0, '설정' ),
	'set_recurring_event' => array( 0, '반복_일정_설정' ),
	'declare' => array( 0, '선언' ),
	'SMW_NOFACTBOX' => array( 0, '__사실상자숨김__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__사실상자보이기__', '__사실상자표시__' ),
);

/** Cornish (kernowek) */
$magicWords['kw'] = array(
	'ask' => array( 0, 'govyn' ),
	'show' => array( 0, 'diskwedhes' ),
	'info' => array( 0, 'kedhlow' ),
	'set' => array( 0, 'settya' ),
);

/** Luxembourgish (Lëtzebuergesch) */
$magicWords['lb'] = array(
	'ask' => array( 0, 'froen' ),
	'show' => array( 0, 'weisen' ),
	'concept' => array( 0, 'Konzept' ),
);

/** Macedonian (македонски) */
$magicWords['mk'] = array(
	'ask' => array( 0, 'прашај' ),
	'show' => array( 0, 'прикажи' ),
	'info' => array( 0, 'инфо' ),
	'concept' => array( 0, 'поим' ),
	'subobject' => array( 0, 'подобјект' ),
	'smwdoc' => array( 0, 'смвдок' ),
	'set' => array( 0, 'постави' ),
	'set_recurring_event' => array( 0, 'постави_повторлив_настан' ),
	'declare' => array( 0, 'изјави' ),
	'SMW_NOFACTBOX' => array( 0, '__БЕЗФАКТКУТИЈА__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__ПРИКАЖИФАКТКУТИЈА__' ),
);

/** Malayalam (മലയാളം) */
$magicWords['ml'] = array(
	'ask' => array( 0, 'ചോദിക്കുക' ),
	'show' => array( 0, 'പ്രദർശിപ്പിക്കുക' ),
	'info' => array( 0, 'വിവരം' ),
	'concept' => array( 0, 'ആശയം' ),
	'set' => array( 0, 'ഗണം' ),
	'declare' => array( 0, 'പ്രഖ്യാപിക്കുക' ),
);

/** Marathi (मराठी) */
$magicWords['mr'] = array(
	'ask' => array( 0, 'विचारा' ),
	'show' => array( 0, 'दाखवा' ),
	'info' => array( 0, 'माहिती' ),
	'concept' => array( 0, 'कंसेप्ट', 'कल्पना' ),
	'set' => array( 0, 'प्रयुक्त', 'सेट', 'स्थापित' ),
	'set_recurring_event' => array( 0, 'प्रयुक्त_पुर्न_कार्य' ),
	'declare' => array( 0, 'प्रकटकरा' ),
	'SMW_NOFACTBOX' => array( 0, '__फॅक्टबॉक्सनाही__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__फॅक्टबॉक्सदाखवा__' ),
);

/** Low Saxon (Netherlands) (Nedersaksies) */
$magicWords['nds-nl'] = array(
	'concept' => array( 0, 'konsept' ),
	'set_recurring_event' => array( 0, 'herhaolende_gebeurtenisse_instellen' ),
	'declare' => array( 0, 'deklareren' ),
	'SMW_NOFACTBOX' => array( 0, '__GIENFEITENKAODER__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__FEITENKAODERWEERGEVEN__' ),
);

/** Dutch (Nederlands) */
$magicWords['nl'] = array(
	'ask' => array( 0, 'vragen' ),
	'show' => array( 0, 'weergeven' ),
	'subobject' => array( 0, 'onderobject' ),
	'set' => array( 0, 'instellen' ),
	'set_recurring_event' => array( 0, 'herhalende_gebeurtenis_instellen' ),
	'declare' => array( 0, 'declareren' ),
	'SMW_NOFACTBOX' => array( 0, '__GEENFEITENKADER__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__FEITENKADERWEERGEVEN__' ),
);

/** Punjabi (ਪੰਜਾਬੀ) */
$magicWords['pa'] = array(
	'ask' => array( 0, 'ਪੁੱਛੋ' ),
	'show' => array( 0, 'ਵਿਖਾਓ' ),
	'info' => array( 0, 'ਜਾਣਕਾਰੀ' ),
);

/** Polish (polski) */
$magicWords['pl'] = array(
	'ask' => array( 0, 'pytanie' ),
	'show' => array( 0, 'pokaż' ),
	'info' => array( 0, 'informacja' ),
	'concept' => array( 0, 'koncept' ),
	'set' => array( 0, 'ustaw' ),
	'declare' => array( 0, 'zadeklaruj' ),
);

/** Pashto (پښتو) */
$magicWords['ps'] = array(
	'ask' => array( 0, 'پوښتل', 'ask' ),
	'show' => array( 0, 'ښکاره_کول', 'show' ),
	'info' => array( 0, 'مالومات', 'info' ),
);

/** Portuguese (português) */
$magicWords['pt'] = array(
	'ask' => array( 0, 'consultar' ),
	'show' => array( 0, 'mostrar' ),
	'concept' => array( 0, 'conceito' ),
	'subobject' => array( 0, 'subobjeto' ),
	'set' => array( 0, 'definir' ),
	'set_recurring_event' => array( 0, 'definir_evento_recorrente' ),
	'declare' => array( 0, 'declarar' ),
	'SMW_NOFACTBOX' => array( 0, '__SEMCAIXADEFATOS__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__EXIBIRCAIXADEFATOS__' ),
);

/** Brazilian Portuguese (português do Brasil) */
$magicWords['pt-br'] = array(
	'ask' => array( 0, 'consultar' ),
	'show' => array( 0, 'mostrar' ),
	'info' => array( 0, 'info' ),
	'concept' => array( 0, 'conceito' ),
	'subobject' => array( 0, 'subobjeto' ),
	'smwdoc' => array( 0, 'smwdoc' ),
	'set' => array( 0, 'definir' ),
	'set_recurring_event' => array( 0, 'definir_evento_recorrente' ),
	'declare' => array( 0, 'declarar' ),
	'SMW_NOFACTBOX' => array( 0, '__SEMCAIXADEFATOS__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__EXIBIRCAIXADEFATOS__' ),
);

/** Serbian (Cyrillic script) (српски (ћирилица)‎) */
$magicWords['sr-ec'] = array(
	'ask' => array( 0, 'питај' ),
	'show' => array( 0, 'прикажи' ),
	'info' => array( 0, 'подаци' ),
	'concept' => array( 0, 'концепт' ),
	'set' => array( 0, 'постави' ),
	'set_recurring_event' => array( 0, 'постави_периодични_догађај' ),
	'declare' => array( 0, 'одреди' ),
);

/** Serbian (Latin script) (srpski (latinica)‎) */
$magicWords['sr-el'] = array(
	'ask' => array( 0, 'pitaj' ),
	'show' => array( 0, 'prikaži' ),
	'info' => array( 0, 'podaci' ),
	'concept' => array( 0, 'koncept' ),
	'set' => array( 0, 'postavi' ),
	'set_recurring_event' => array( 0, 'postavi_periodični_događaj' ),
	'declare' => array( 0, 'odredi' ),
	'SMW_NOFACTBOX' => array( 0, '__BEZČINJENICA__', '__BEZ_ČINJENICA__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__PRIKAŽIČINJENICE__', '__PRIKAŽI_ČINJENICE__' ),
);

/** Swedish (svenska) */
$magicWords['sv'] = array(
	'ask' => array( 0, 'fråga', 'ask' ),
	'show' => array( 0, 'visa', 'show' ),
	'concept' => array( 0, 'koncept', 'concept' ),
);

/** Tatar (Cyrillic script) (татарча) */
$magicWords['tt-cyrl'] = array(
	'ask' => array( 0, 'сорау' ),
	'show' => array( 0, 'күрсәт' ),
	'info' => array( 0, 'мәгълүмат' ),
);

/** Vietnamese (Tiếng Việt) */
$magicWords['vi'] = array(
	'ask' => array( 0, 'hỏi' ),
	'show' => array( 0, 'hiển_thị' ),
	'info' => array( 0, 'thông_tin' ),
	'concept' => array( 0, 'khái_niệm' ),
	'set' => array( 0, 'đặt' ),
);

/** Simplified Chinese (中文（简体）‎) */
$magicWords['zh-hans'] = array(
	'ask' => array( 0, '询问' ),
	'show' => array( 0, '显示' ),
	'info' => array( 0, '信息' ),
	'concept' => array( 0, '概念' ),
	'subobject' => array( 0, '子对象' ),
	'smwdoc' => array( 0, 'SMW文档' ),
	'set' => array( 0, '设置' ),
	'set_recurring_event' => array( 0, '设置循环活动' ),
	'declare' => array( 0, '宣布' ),
	'SMW_NOFACTBOX' => array( 0, '__无实际内容框__' ),
	'SMW_SHOWFACTBOX' => array( 0, '__显示实际内容框__' ),
);

/** Traditional Chinese (中文（繁體）‎) */
$magicWords['zh-hant'] = array(
	'ask' => array( 0, '訪問' ),
	'show' => array( 0, '顯示' ),
	'info' => array( 0, '資訊' ),
	'smwdoc' => array( 0, 'SMW檔案' ),
	'set' => array( 0, '設定' ),
	'set_recurring_event' => array( 0, '設定循環活動', '設置定期活動' ),
);
