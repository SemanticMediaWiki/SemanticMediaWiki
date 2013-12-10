<?php

namespace SMW;

/**
 * Namespace setup and registration
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 */
final class NamespaceCustomizer {

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
		$this->initNamespaces();
	}

	/**
	 * Adds canonical names and called during Hooks:CanonicalNamespaces initialization
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public static function getCanonicalNames( &$namespaces ) {

		$namespaces[SMW_NS_PROPERTY]      = 'Property';
		$namespaces[SMW_NS_PROPERTY_TALK] = 'Property_talk';
		$namespaces[SMW_NS_TYPE]          = 'Type';
		$namespaces[SMW_NS_TYPE_TALK]     = 'Type_talk';
		$namespaces[SMW_NS_CONCEPT]       = 'Concept';
		$namespaces[SMW_NS_CONCEPT_TALK]  = 'Concept_talk';

		return true;
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
	 * @return array
	 */
	public static function buildCustomNamespaceIndex( $offset ) {

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
	 * @since 1.9
	 */
	protected function initNamespaces() {

		if ( !isset( $this->globals['smwgNamespaceIndex'] ) ) {
			$this->globals['smwgNamespaceIndex'] = 100;
		}

		foreach ( $this->buildCustomNamespaceIndex( $this->globals['smwgNamespaceIndex'] ) as $ns => $index ) {
			$this->assertIsDefined( $ns, $index );
		}

		if ( empty( $this->globals['smwgContLang'] ) ) {
			$this->initContentLanguage( $this->globals['wgLanguageCode'] );
		}

		// Register namespace identifiers
		if ( !is_array( $this->globals['wgExtraNamespaces'] ) ) {
			$this->globals['wgExtraNamespaces'] = array();
		}

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
	 * @since  1.9
	 */
	protected function assertIsDefined( $ns, $index ) {
		return defined( $ns ) ? true : define( $ns, $index );
	}

	/**
	 * Initialise a global language object for content language. This must happen
	 * early on, even before user language is known, to determine labels for
	 * additional namespaces. In contrast, messages can be initialised much later
	 * when they are actually needed.
	 * @since 1.9
	 */
	protected function initContentLanguage( $langcode ) {
		Profiler::In();

		$this->globals['smwContLangFile'] = 'SMW_Language' . str_replace( '-', '_', ucfirst( $langcode ) );
		$this->globals['smwContLangClass'] = 'SMWLanguage' . str_replace( '-', '_', ucfirst( $langcode ) );

		if ( file_exists( $this->directory . '/' . 'languages/' . $this->globals['smwContLangFile'] . '.php' ) ) {
			include_once( $this->directory . '/' . 'languages/' . $this->globals['smwContLangFile'] . '.php' );
		}

		// Fallback if language not supported.
		if ( !class_exists( $this->globals['smwContLangClass'] ) ) {
			include_once( $this->directory . '/' . 'languages/SMW_LanguageEn.php' );
			$this->globals['smwContLangClass'] = 'SMWLanguageEn';
		}

		$this->globals['smwgContLang'] = new $this->globals['smwContLangClass'];

		Profiler::Out();
	}

}
