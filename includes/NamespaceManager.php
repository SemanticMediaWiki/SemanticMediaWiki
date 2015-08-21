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

	/** @var array */
	protected $globalVars;

	/**
	 * @since 1.9
	 *
	 * @param array &$globalVars
	 * @param string|null &directory
	 */
	public function __construct( &$globalVars, $directory = null ) {
		$this->globalVars =& $globalVars;
		$this->directory = $directory;
	}

	/**
	 * @since 1.9
	 */
	public function run() {

		if ( !$this->isDefinedConstant( 'SMW_NS_PROPERTY' ) ) {
			$this->initCustomNamespace( $this->globalVars );
		}

		if ( empty( $this->globalVars['smwgContLang'] ) ) {
			$this->initContentLanguage( $this->globalVars['wgLanguageCode'] );
		}

		$this->addNamespaceSettings();

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
	}

	protected function addNamespaceSettings() {

		$this->isValidConfigurationOrSetDefault( 'wgExtraNamespaces', array() );
		$this->isValidConfigurationOrSetDefault( 'wgNamespaceAliases', array() );

		/**
		 * @var SMWLanguage $smwgContLang
		 */
		$this->globalVars['wgExtraNamespaces'] = $this->globalVars['wgExtraNamespaces'] + $this->globalVars['smwgContLang']->getNamespaces();
		$this->globalVars['wgNamespaceAliases'] = $this->globalVars['wgNamespaceAliases'] + $this->globalVars['smwgContLang']->getNamespaceAliases();

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

	/**
	 * Initialise a global language object for content language. This must happen
	 * early on, even before user language is known, to determine labels for
	 * additional namespaces. In contrast, messages can be initialised much later
	 * when they are actually needed.
	 *
	 * @since 1.9
	 */
	protected function initContentLanguage( $langcode ) {

		$this->setLanguage( $langcode );
		$this->isValidLanguageClassOrSetFallback( $this->globalVars['smwContLangClass'], 'en' );

		$this->globalVars['smwgContLang'] = new $this->globalVars['smwContLangClass'];
	}

	protected function setLanguage( $langcode ) {

		$this->globalVars['smwContLangFile'] = 'SMW_Language' . str_replace( '-', '_', ucfirst( $langcode ) );
		$this->globalVars['smwContLangClass'] = 'SMWLanguage' . str_replace( '-', '_', ucfirst( $langcode ) );

		$file = $this->directory . '/' . 'languages' . '/' . $this->globalVars['smwContLangFile'] . '.php';

		if ( file_exists( $file ) ) {
			include_once ( $file );
		}
	}

	protected function isValidConfigurationOrSetDefault( $element, $default ) {
		if ( !isset( $this->globalVars[$element] ) || !is_array( $this->globalVars[$element] ) ) {
			$this->globalVars[$element] = $default;
		}
	}

	protected function isValidLanguageClassOrSetFallback( $langClass, $fallbackLanguageCode ) {
		if ( !class_exists( $langClass ) ) {
			$this->setLanguage( $fallbackLanguageCode );
		}
	}

	protected function isDefinedConstant( $constant ) {
		return defined( $constant );
	}

}
