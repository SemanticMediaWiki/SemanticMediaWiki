<?php

namespace SMW;

use SMW\ExtraneousLanguage\ExtraneousLanguage;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author others
 */
class NamespaceManager {

	/**
	 * @var array
	 */
	private $globalVars;

	/**
	 * @var ExtraneousLanguage
	 */
	private $extraneousLanguage;

	/**
	 * @since 1.9
	 *
	 * @param array &$globalVars
	 * @param ExtraneousLanguage|null $extraneousLanguage
	 */
	public function __construct( &$globalVars, ExtraneousLanguage $extraneousLanguage = null ) {
		$this->globalVars =& $globalVars;
		$this->extraneousLanguage = $extraneousLanguage;

		if ( $this->extraneousLanguage === null ) {
			$this->extraneousLanguage = ExtraneousLanguage::getInstance();
		}
	}

	/**
	 * @since 1.9
	 */
	public function init() {

		if ( !$this->isDefinedConstant( 'SMW_NS_PROPERTY' ) ) {
			$this->initCustomNamespace( $this->globalVars );
		}

		// Legacy seeting in case some extension request a `smwgContLang` reference
		if ( empty( $this->globalVars['smwgContLang'] ) ) {
			$this->globalVars['smwgContLang'] = $this->extraneousLanguage->fetchByLanguageCode( $this->globalVars['wgLanguageCode'] );
		}

		$this->addNamespaceSettings();
		$this->addExtraNamespaceSettings();
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

		$canonicalNames = array(
			SMW_NS_PROPERTY      => 'Property',
			SMW_NS_PROPERTY_TALK => 'Property_talk',
			SMW_NS_TYPE          => 'Type',
			SMW_NS_TYPE_TALK     => 'Type_talk',
			SMW_NS_CONCEPT       => 'Concept',
			SMW_NS_CONCEPT_TALK  => 'Concept_talk'
		);

		if ( !array_key_exists( 'smwgHistoricTypeNamespace', $GLOBALS ) || !$GLOBALS['smwgHistoricTypeNamespace'] ) {
			unset( $canonicalNames[SMW_NS_TYPE] );
			unset( $canonicalNames[SMW_NS_TYPE_TALK] );
		}

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

		$namespaceIndex = array(
			'SMW_NS_PROPERTY'      => $offset + 2,
			'SMW_NS_PROPERTY_TALK' => $offset + 3,
			'SMW_NS_TYPE'          => $offset + 4,
			'SMW_NS_TYPE_TALK'     => $offset + 5,
			'SF_NS_FORM'           => $offset + 6,
			'SF_NS_FORM_TALK'      => $offset + 7,
			'SMW_NS_CONCEPT'       => $offset + 8,
			'SMW_NS_CONCEPT_TALK'  => $offset + 9,
		);

		return $namespaceIndex;
	}

	/**
	 * 100 and 101 used to be occupied by SMW's now obsolete namespaces
	 * "Relation" and "Relation_Talk"
	 *
	 * 106 and 107 are occupied by the Semantic Forms, we define them here
	 * to offer some (easy but useful) support to SF
	 *
	 * @since 1.9
	 *
	 * @param array $globalVars
	 */
	public static function initCustomNamespace( &$globalVars ) {

		$instance = new self( $globalVars );

		if ( !isset( $globalVars['smwgNamespaceIndex'] ) ) {
			$globalVars['smwgNamespaceIndex'] = 100;
		}

		$defaultSettings = array(
			'wgNamespaceAliases',
			'wgExtraNamespaces',
			'wgNamespacesWithSubpages',
			'smwgNamespacesWithSemanticLinks',
			'smwgNamespaceIndex',
			'wgCanonicalNamespaceNames'
		);

		foreach ( $defaultSettings as $key ) {
			$globalVars[$key] = !isset( $globalVars[$key] ) ? array() : $globalVars[$key];
		}

		foreach ( $instance->buildNamespaceIndex( $globalVars['smwgNamespaceIndex'] ) as $ns => $index ) {
			if ( !$instance->isDefinedConstant( $ns ) ) {
				define( $ns, $index );
			};
		}

		$extraNamespaces = $instance->getNamespacesByLanguageCode(
			$globalVars['wgLanguageCode']
		);

		$globalVars['wgCanonicalNamespaceNames'] += $instance->getCanonicalNames();
		$globalVars['wgExtraNamespaces'] += $extraNamespaces + $instance->getCanonicalNames();
		$globalVars['wgNamespaceAliases'] = array_flip( $extraNamespaces ) + array_flip( $instance->getCanonicalNames() ) + $globalVars['wgNamespaceAliases'];

		$instance->addNamespaceSettings();

		return $instance;
	}

	protected function addNamespaceSettings() {

		/**
		 * Default settings for the SMW specific NS which can only
		 * be defined after SMW_NS_PROPERTY is declared
		 */
		$smwNamespacesSettings = array(
			SMW_NS_PROPERTY  => true,
			SMW_NS_PROPERTY_TALK  => false,
			SMW_NS_TYPE => true,
			SMW_NS_TYPE_TALK => false,
			SMW_NS_CONCEPT => true,
			SMW_NS_CONCEPT_TALK => false,
		);

		if ( !array_key_exists( 'smwgHistoricTypeNamespace', $GLOBALS ) || !$GLOBALS['smwgHistoricTypeNamespace'] ) {
			unset( $smwNamespacesSettings[SMW_NS_TYPE] );
			unset( $smwNamespacesSettings[SMW_NS_TYPE_TALK] );
			unset( $this->globalVars['wgNamespacesWithSubpages'][SMW_NS_TYPE_TALK] );
		}

		// Combine default values with values specified in other places
		// (LocalSettings etc.)
		$this->globalVars['smwgNamespacesWithSemanticLinks'] = array_replace(
			$smwNamespacesSettings,
			$this->globalVars['smwgNamespacesWithSemanticLinks']
		);
	}

	private function addExtraNamespaceSettings() {

		/**
		 * Indicating which namespaces allow sub-pages
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:$wgNamespacesWithSubpages
		 */
		$this->globalVars['wgNamespacesWithSubpages'] = $this->globalVars['wgNamespacesWithSubpages'] + array(
			SMW_NS_PROPERTY_TALK => true,
			SMW_NS_CONCEPT_TALK => true,
		);

		/**
		 * Allow custom namespaces to be acknowledged as containing useful content
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:$wgContentNamespaces
		 */
		$this->globalVars['wgContentNamespaces'] = $this->globalVars['wgContentNamespaces'] + array(
			SMW_NS_PROPERTY,
			SMW_NS_CONCEPT
		);

		/**
		 * To indicate which namespaces are enabled for searching by default
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:$wgNamespacesToBeSearchedDefault
		 */
		$this->globalVars['wgNamespacesToBeSearchedDefault'] = $this->globalVars['wgNamespacesToBeSearchedDefault'] + array(
			SMW_NS_PROPERTY => true,
			SMW_NS_CONCEPT => true
		);
	}

	protected function isDefinedConstant( $constant ) {
		return defined( $constant );
	}

	protected function getNamespacesByLanguageCode( $languageCode ) {
		$GLOBALS['smwgContLang'] = $this->extraneousLanguage->fetchByLanguageCode( $languageCode );
		return $GLOBALS['smwgContLang']->getNamespaces();
	}

}
