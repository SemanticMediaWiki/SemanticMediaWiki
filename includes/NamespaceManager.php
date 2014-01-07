<?php

namespace SMW;

/**
 * Namespace setup and registration
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author others
 */
class NamespaceManager {

	/** @var array */
	protected $globals;

	/**
	 * @since 1.9
	 *
	 * @param array &$globals
	 */
	public function __construct( &$globals, $directory ) {
		$this->globals =& $globals;
		$this->directory = $directory;
	}

	/**
	 * @since 1.9
	 */
	public function run() {

		if ( !$this->assertConstantIsDefined( 'SMW_NS_PROPERTY' ) ) {
			$this->initCustomNamespace( $this->globals );
		}

		if ( empty( $this->globals['smwgContLang'] ) ) {
			$this->initContentLanguage( $this->globals['wgLanguageCode'] );
		}

		$this->addNamespaceSettings();

		return true;
	}

	/**
	 * Returns canonical names
	 *
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
	 * @param array $globals
	 */
	public static function initCustomNamespace( &$globals ) {

		if ( !isset( $globals['smwgNamespaceIndex'] ) ) {
			$globals['smwgNamespaceIndex'] = 100;
		}

		foreach ( self::buildNamespaceIndex( $globals['smwgNamespaceIndex'] ) as $ns => $index ) {
			if ( !self::assertConstantIsDefined( $ns ) ) {
				define( $ns, $index );
			};
		}
	}

	/**
	 * @since 1.9
	 */
	protected function addNamespaceSettings() {

		$this->assertIsArrayOrSetDefault( 'wgExtraNamespaces' );
		$this->assertIsArrayOrSetDefault( 'wgNamespaceAliases' );

		/**
		 * @var SMWLanguage $smwgContLang
		 */
		$this->globals['wgExtraNamespaces'] = $this->globals['wgExtraNamespaces'] + $this->globals['smwgContLang']->getNamespaces();
		$this->globals['wgNamespaceAliases'] = $this->globals['wgNamespaceAliases'] + $this->globals['smwgContLang']->getNamespaceAliases();

		// Support subpages only for talk pages by default
		$this->globals['wgNamespacesWithSubpages'] = $this->globals['wgNamespacesWithSubpages'] + array(
			SMW_NS_PROPERTY_TALK => true,
			SMW_NS_TYPE_TALK => true
		);

		// not modified for Semantic MediaWiki
		/* $this->globals['wgNamespacesToBeSearchedDefault'] = array(
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
		$this->globals['smwgNamespacesWithSemanticLinks'] = array_replace(
			$smwNamespacesSettings,
			$this->globals['smwgNamespacesWithSemanticLinks']
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
		Profiler::In();

		$this->setLanguage( $langcode );
		$this->assertValidLanguageOrSetFallback( 'en' );

		$this->globals['smwgContLang'] = new $this->globals['smwContLangClass'];

		Profiler::Out();
	}

	/**
	 * @since 1.9
	 */
	protected function setLanguage( $langcode ) {

		$this->globals['smwContLangFile'] = 'SMW_Language' . str_replace( '-', '_', ucfirst( $langcode ) );
		$this->globals['smwContLangClass'] = 'SMWLanguage' . str_replace( '-', '_', ucfirst( $langcode ) );

		$file = $this->directory . '/' . 'languages' . '/' . $this->globals['smwContLangFile'] . '.php';

		if ( file_exists( $file ) ) {
			include_once( $file );
		}
	}

	/**
	 * @since 1.9
	 */
	protected function assertIsArrayOrSetDefault( $element ) {
		if ( !isset( $this->globals[$element] ) || !is_array( $this->globals[$element] ) ) {
			$this->globals[$element] = array();
		}
	}

	/**
	 * @since 1.9
	 */
	protected function assertValidLanguageOrSetFallback( $fallbackLanguageCode ) {
		if ( !class_exists( $this->globals['smwContLangClass'] ) ) {
			$this->setLanguage( $fallbackLanguageCode );
		}
	}

	/**
	 * @since 1.9.0.2
	 */
	protected static function assertConstantIsDefined( $constant ) {
		return defined( $constant );
	}

}
