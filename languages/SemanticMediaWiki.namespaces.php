<?php

/**
 * Internationalisation namespace file for SemanticMedaWiki extension
 *
 * @ingroup SMW
 */

$namespaceNames = array();

$namespaceNames['en'] = array(
	SMW_NS_PROPERTY       => 'Property',
	SMW_NS_PROPERTY_TALK  => 'Property_talk',
	SMW_NS_TYPE           => 'Type',
	SMW_NS_TYPE_TALK      => 'Type_talk',
	SMW_NS_CONCEPT        => 'Concept',
	SMW_NS_CONCEPT_TALK   => 'Concept_talk',
);

$namespaceNames['de'] = array(
	SMW_NS_PROPERTY       => "Attribut",
	SMW_NS_PROPERTY_TALK  => "Attribut_Diskussion",
	SMW_NS_TYPE           => "Datentyp",
	SMW_NS_TYPE_TALK      => "Datentyp_Diskussion",
	SMW_NS_CONCEPT        => 'Konzept',
	SMW_NS_CONCEPT_TALK   => 'Konzept_Diskussion',
);

$namespaceNames['zh-cn'] = array(
	SMW_NS_PROPERTY       => '性质',	// 'Property',
	SMW_NS_PROPERTY_TALK  => '性质讨论',	// 'Property_talk',
	SMW_NS_TYPE           => '型态',	// 'Type',
	SMW_NS_TYPE_TALK      => '型态讨论',	// 'Type_talk'
	SMW_NS_CONCEPT        => 'Concept', // TODO: translate
	SMW_NS_CONCEPT_TALK   => 'Concept_talk' // TODO: translate
);

# Namespace aliases
$namespaceAliases = array();

$namespaceAliases['en'] = array(
	'Property'      => SMW_NS_PROPERTY,
	'Property_talk' => SMW_NS_PROPERTY_TALK,
	'Type'          => SMW_NS_TYPE,
	'Type_talk'     => SMW_NS_TYPE_TALK,
	'Concept'       => SMW_NS_CONCEPT,
	'Concept_talk'  => SMW_NS_CONCEPT_TALK,
);

$namespaceAliases['de'] = array(
	'Attribut'            => SMW_NS_PROPERTY,
	'Attribut_Diskussion' => SMW_NS_PROPERTY_TALK,
	'Datentyp'            => SMW_NS_TYPE,
	'Datentyp_Diskussion' => SMW_NS_TYPE_TALK,
	'Konzept'             => SMW_NS_CONCEPT,
	'Konzept_Diskussion'  => SMW_NS_CONCEPT_TALK,
);

$namespaceAliases['zh-cn'] = array(
	'性质'         => SMW_NS_PROPERTY,
	'性质讨论'      => SMW_NS_PROPERTY_TALK,
	'型态'         => SMW_NS_TYPE,
	'型态讨论'      => SMW_NS_TYPE_TALK,
	'Concept'      => SMW_NS_CONCEPT,
	'Concept_talk' => SMW_NS_CONCEPT_TALK
);
