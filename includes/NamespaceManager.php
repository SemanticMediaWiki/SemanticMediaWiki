<?php

namespace SMW;

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
	protected $globalVars;

	/**
	 * @since 1.9
	 *
	 * @param array &$globalVars
	 */
	public function __construct( &$globalVars ) {
		$this->globalVars =& $globalVars;
	}

	/**
	 * @since 1.9
	 */
	public function init() {

		if ( !$this->isDefinedConstant( 'SMW_NS_PROPERTY' ) ) {
			$this->initCustomNamespace( $this->globalVars );
		}

		if ( empty( $this->globalVars['smwgContLang'] ) ) {
			$this->globalVars['smwgContLang'] = ExtraneousLanguage::getInstance()->fetchByLanguageCode( $this->globalVars['wgLanguageCode'] );
		}

		$this->addNamespaceSettings();

		return true;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $languageCode
	 *
	 * @return array
	 */
	public static function getNamespacesByLanguageCode( $languageCode ) {
		$GLOBALS['smwgContLang'] = ExtraneousLanguage::getInstance()->fetchByLanguageCode( $languageCode );
		return $GLOBALS['smwgContLang']->getNamespaces();
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

		foreach ( $instance->buildNamespaceIndex( $globalVars['smwgNamespaceIndex'] ) as $ns => $index ) {
			if ( !$instance->isDefinedConstant( $ns ) ) {
				define( $ns, $index );
			};
		}

		$globalVars['wgExtraNamespaces'] = ( isset( $globalVars['wgExtraNamespaces'] ) ? $globalVars['wgExtraNamespaces'] : array() ) + self::getCanonicalNames();
	}

	protected function addNamespaceSettings() {

		$this->isValidConfigurationOrSetDefault( 'wgExtraNamespaces', array() );
		$this->isValidConfigurationOrSetDefault( 'wgNamespaceAliases', array() );

		/**
		 * @var SMWLanguage $smwgContLang
		 */
		$this->globalVars['wgExtraNamespaces'] = $this->globalVars['smwgContLang']->getNamespaces() + $this->globalVars['wgExtraNamespaces'];
		$this->globalVars['wgNamespaceAliases'] = array_flip( $this->globalVars['smwgContLang']->getNamespaces() ) + $this->globalVars['wgNamespaceAliases'];

		// Support subpages only for talk pages by default
		$this->globalVars['wgNamespacesWithSubpages'] = $this->globalVars['wgNamespacesWithSubpages'] + array(
			SMW_NS_PROPERTY_TALK => true,
			SMW_NS_TYPE_TALK => true,
			SMW_NS_CONCEPT_TALK => true,
		);

		// not modified for Semantic MediaWiki
		/* $this->globalVars['wgNamespacesToBeSearchedDefault'] = array(
			NS_MAIN           => true,
			);
		*/

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

		// Combine default values with values specified in other places
		// (LocalSettings etc.)
		$this->globalVars['smwgNamespacesWithSemanticLinks'] = array_replace(
			$smwNamespacesSettings,
			$this->globalVars['smwgNamespacesWithSemanticLinks']
		);

	}

	protected function isValidConfigurationOrSetDefault( $element, $default ) {
		if ( !isset( $this->globalVars[$element] ) || !is_array( $this->globalVars[$element] ) ) {
			$this->globalVars[$element] = $default;
		}
	}

	protected function isDefinedConstant( $constant ) {
		return defined( $constant );
	}

}
