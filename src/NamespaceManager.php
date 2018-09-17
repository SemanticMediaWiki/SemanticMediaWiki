<?php

namespace SMW;

use SMW\Lang\Lang;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author others
 */
class NamespaceManager {

	/**
	 * @var Lang
	 */
	private $lang;

	/**
	 * @since 1.9
	 *
	 * @param Lang|null $lang
	 */
	public function __construct( Lang $lang = null ) {
		$this->lang = $lang;

		if ( $this->lang === null ) {
			$this->lang = Lang::getInstance();
		}
	}

	/**
	 * @since 1.9
	 *
	 * @param &$vars
	 */
	public function init( &$vars ) {

		if ( !$this->isDefinedConstant( 'SMW_NS_PROPERTY' ) ) {
			$this->initCustomNamespace( $vars );
		}

		// Legacy seeting in case some extension request a `smwgContLang` reference
		if ( empty( $vars['smwgContLang'] ) ) {
			$vars['smwgContLang'] = $this->lang->fetch( $vars['wgLanguageCode'] );
		}

		$this->addNamespaceSettings( $vars );
		$this->addExtraNamespaceSettings( $vars );
	}

	/**
	 * @see Hooks:CanonicalNamespaces
	 * CanonicalNamespaces initialization
	 *
	 * @note According to T104954 registration via wgExtensionFunctions is to late
	 * and should happen before that
	 *
	 * @see https://phabricator.wikimedia.org/T104954#2391291
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CanonicalNamespaces
	 * @Bug 34383
	 *
	 * @since 2.5
	 *
	 * @param array &$namespaces
	 */
	public static function initCanonicalNamespaces( array &$namespaces ) {

		$canonicalNames = self::initCustomNamespace( $GLOBALS )->getCanonicalNames();
		$namespacesByName = array_flip( $namespaces );

		// https://phabricator.wikimedia.org/T160665
		// Find any namespace that uses the same canonical name and remove it
		foreach ( $canonicalNames as $id => $name ) {
			if ( isset( $namespacesByName[$name] ) ) {
				unset( $namespaces[$namespacesByName[$name]] );
			}
		}

		$namespaces += $canonicalNames;

		return true;
	}

	/**
	 * @see Hooks:CanonicalNamespaces
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public static function getCanonicalNames() {

		$canonicalNames = [
			SMW_NS_PROPERTY      => 'Property',
			SMW_NS_PROPERTY_TALK => 'Property_talk',
			SMW_NS_CONCEPT       => 'Concept',
			SMW_NS_CONCEPT_TALK  => 'Concept_talk',
			SMW_NS_SCHEMA        => 'smw/schema',
			SMW_NS_SCHEMA_TALK   => 'smw/schema_talk',
			SMW_NS_RULE          => 'Rule',
			SMW_NS_RULE_TALK     => 'Rule_talk'
		];

		return $canonicalNames;
	}

	/**
	 * @since 1.9
	 *
	 * @param integer offset
	 *
	 * @return array
	 */
	public static function buildNamespaceIndex( $offset ) {

		// 100 and 101 used to be occupied by SMW's now obsolete namespaces
		// "Relation" and "Relation_Talk"

		// 106 and 107 are occupied by the Semantic Forms, we define them here
		// to offer some (easy but useful) support to SF

		$namespaceIndex = [
			'SMW_NS_PROPERTY'      => $offset + 2,
			'SMW_NS_PROPERTY_TALK' => $offset + 3,
			//'SF_NS_FORM'           => $offset + 6,
			//'SF_NS_FORM_TALK'      => $offset + 7,
			'SMW_NS_CONCEPT'       => $offset + 8,
			'SMW_NS_CONCEPT_TALK'  => $offset + 9,

			// #3019 notes "Conflicts with the DPLforum extension ..."
			//'SMW_NS_SCHEMA'      => $offset + 10,
			//'SMW_NS_SCHEMA_TALK' => $offset + 11,

			'SMW_NS_SCHEMA'        => $offset + 12,
			'SMW_NS_SCHEMA_TALK'   => $offset + 13,

			'SMW_NS_RULE'          => $offset + 14,
			'SMW_NS_RULE_TALK'     => $offset + 15,
		];

		return $namespaceIndex;
	}

	/**
	 * @since 1.9
	 *
	 * @param array &$vars
	 * @param Lang|null $lang
	 */
	public static function initCustomNamespace( &$vars, Lang $lang = null ) {

		$instance = new self( $lang );

		if ( !isset( $vars['smwgNamespaceIndex'] ) ) {
			$vars['smwgNamespaceIndex'] = 100;
		}

		$defaultSettings = [
			'wgNamespaceAliases',
			'wgExtraNamespaces',
			'wgNamespacesWithSubpages',
			'smwgNamespacesWithSemanticLinks',
			'smwgNamespaceIndex',
			'wgCanonicalNamespaceNames'
		];

		foreach ( $defaultSettings as $key ) {
			$vars[$key] = !isset( $vars[$key] ) ? [] : $vars[$key];
		}

		foreach ( $instance->buildNamespaceIndex( $vars['smwgNamespaceIndex'] ) as $ns => $index ) {
			if ( !$instance->isDefinedConstant( $ns ) ) {
				define( $ns, $index );
			};
		}

		$extraNamespaces = $instance->getNamespacesByLanguageCode(
			$vars['wgLanguageCode']
		);

		$namespaceAliases = $instance->getNamespaceAliasesByLanguageCode(
			$vars['wgLanguageCode']
		);

		$vars['wgCanonicalNamespaceNames'] += $instance->getCanonicalNames();
		$vars['wgExtraNamespaces'] += $extraNamespaces + $instance->getCanonicalNames();
		$vars['wgNamespaceAliases'] = $namespaceAliases + array_flip( $extraNamespaces ) + array_flip( $instance->getCanonicalNames() ) + $vars['wgNamespaceAliases'];

		$instance->addNamespaceSettings( $vars );

		return $instance;
	}

	private function addNamespaceSettings( &$vars ) {

		/**
		 * Default settings for the SMW specific NS which can only
		 * be defined after SMW_NS_PROPERTY is declared
		 */
		$smwNamespacesSettings = [
			SMW_NS_PROPERTY  => true,
			SMW_NS_PROPERTY_TALK  => false,
			SMW_NS_CONCEPT => true,
			SMW_NS_CONCEPT_TALK => false,
			SMW_NS_SCHEMA => true,
			SMW_NS_SCHEMA_TALK => false,
		];

		// Combine default values with values specified in other places
		// (LocalSettings etc.)
		$vars['smwgNamespacesWithSemanticLinks'] = array_replace(
			$smwNamespacesSettings,
			$vars['smwgNamespacesWithSemanticLinks']
		);

		$vars['wgNamespaceContentModels'][SMW_NS_SCHEMA] = CONTENT_MODEL_SMW_SCHEMA;
	}

	private function addExtraNamespaceSettings( &$vars ) {

		/**
		 * Indicating which namespaces allow sub-pages
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:$wgNamespacesWithSubpages
		 */
		$vars['wgNamespacesWithSubpages'] = $vars['wgNamespacesWithSubpages'] + [
			SMW_NS_PROPERTY_TALK => true,
			SMW_NS_CONCEPT_TALK => true,
		];

		/**
		 * Allow custom namespaces to be acknowledged as containing useful content
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:$wgContentNamespaces
		 */
		$vars['wgContentNamespaces'] = $vars['wgContentNamespaces'] + [
			SMW_NS_PROPERTY,
			SMW_NS_CONCEPT
		];

		/**
		 * To indicate which namespaces are enabled for searching by default
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:$wgNamespacesToBeSearchedDefault
		 */
		$vars['wgNamespacesToBeSearchedDefault'] = $vars['wgNamespacesToBeSearchedDefault'] + [
			SMW_NS_PROPERTY => true,
			SMW_NS_CONCEPT => true
		];
	}

	protected function isDefinedConstant( $constant ) {
		return defined( $constant );
	}

	protected function getNamespacesByLanguageCode( $languageCode ) {
		$GLOBALS['smwgContLang'] = $this->lang->fetch( $languageCode );
		return $GLOBALS['smwgContLang']->getNamespaces();
	}

	private function getNamespaceAliasesByLanguageCode( $languageCode ) {
		return $this->lang->fetch( $languageCode )->getNamespaceAliases();
	}

}
