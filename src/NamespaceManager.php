<?php

namespace SMW;

use SMW\Localizer\LocalLanguage\LocalLanguage;
use SMW\Exception\SiteLanguageChangeException;
use SMW\Exception\NamespaceIndexChangeException;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author others
 */
class NamespaceManager {

	/**
	 * Defines the default namespace index used as offset for building Semantic
	 * MediaWiki's specific namespace numbers.
	 */
	const DEFAULT_NAMESPACEINDEX = 100;

	/**
	 * @var LocalLanguage|null
	 */
	private $localLanguage;

	/**
	 * @var string
	 */
	private static $initLanguageCode = '';

	/**
	 * @var int|null
	 */
	private static $initNamespaceIndex = null;

	/**
	 * @since 1.9
	 *
	 * @param LocalLanguage|null $LocalLanguage
	 */
	public function __construct( LocalLanguage $LocalLanguage = null ) {
		$this->localLanguage = $LocalLanguage;

		if ( $this->localLanguage === null ) {
			$this->localLanguage = LocalLanguage::getInstance();
		}
	}

	/**
	 * @since 1.9
	 */
	public function init( array $vars ): array {
		if ( !$this->isDefinedConstant( 'SMW_NS_PROPERTY' ) ) {
			$vars = $this->initCustomNamespace( $vars )['newVars'];
		}

		$this->addNamespaceSettings( $vars );
		$this->addExtraNamespaceSettings( $vars );

		return $vars;
	}

	/**
	 * @since 3.2
	 */
	public static function clear() {
		self::$initLanguageCode = '';
		self::$initNamespaceIndex = null;
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
		$instance_newVars = self::initCustomNamespace( $GLOBALS );
		Globals::replace( $instance_newVars['newVars'] );
		$canonicalNames = $instance_newVars['instance']->getCanonicalNames();
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
			SMW_NS_SCHEMA_TALK   => 'smw/schema_talk'
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
		];

		return $namespaceIndex;
	}

	/**
	 * @since 1.9
	 */
	public static function initCustomNamespace( array $vars, LocalLanguage $localLanguage = null ): array {
		$instance = new self( $localLanguage );

		$vars['smwgNamespaceIndex'] = $instance->getNamespaceIndex( $vars );

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
			}
		}

		$localLanguage = $instance->getLocalLanguage(
			$instance->getLanguageCode( $vars )
		);

		$extraNamespaces = $localLanguage->getNamespaces();
		$namespaceAliases = $localLanguage->getNamespaceAliases();

		$vars['wgCanonicalNamespaceNames'] += $instance->getCanonicalNames();
		$vars['wgExtraNamespaces'] += $extraNamespaces + $instance->getCanonicalNames();
		$vars['wgNamespaceAliases'] = $namespaceAliases + array_flip( $extraNamespaces ) + array_flip( $instance->getCanonicalNames() ) + $vars['wgNamespaceAliases'];

		$instance->addNamespaceSettings( $vars );

		return [
			'instance' => $instance,
			'newVars' => $vars
		];
	}

	private function getNamespaceIndex( $vars ) {
		if ( !isset( $vars['smwgNamespaceIndex'] ) ) {
			return self::$initNamespaceIndex = self::DEFAULT_NAMESPACEINDEX;
		} elseif ( self::$initNamespaceIndex === null ) {
			return self::$initNamespaceIndex = $vars['smwgNamespaceIndex'];
		} elseif ( self::$initNamespaceIndex !== null && self::$initNamespaceIndex === $vars['smwgNamespaceIndex'] ) {
			return self::$initNamespaceIndex;
		}

		throw new NamespaceIndexChangeException( self::$initNamespaceIndex, $vars['smwgNamespaceIndex'] );
	}

	private function getLanguageCode( $vars ) {
		if ( self::$initLanguageCode === '' ) {
			return self::$initLanguageCode = $vars['wgLanguageCode'];
		} elseif ( self::$initLanguageCode !== '' && self::$initLanguageCode === $vars['wgLanguageCode'] ) {
			return self::$initLanguageCode;
		}

		// #4680
		//
		// Overrides aren't allowed to prevent issues with others trying to
		// manipulate the definition after the initialization (`enableSemantics`)
		// but users may define `wgLanguageCode` after the initialization and expect
		// it to work which it won't hence we raise an exception to inform the user
		// about the unexpected change.
		throw new SiteLanguageChangeException( self::$initLanguageCode, $vars['wgLanguageCode'] );
	}

	private function addNamespaceSettings( &$vars ) {
		/**
		 * Default settings for the SMW specific NS which can only
		 * be defined after SMW_NS_PROPERTY is declared
		 */
		$smwNamespacesSettings = [
			SMW_NS_PROPERTY => true,
			SMW_NS_PROPERTY_TALK => false,
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

	protected function getLocalLanguage( $languageCode ) {
		return $this->localLanguage->fetch( $languageCode );
	}

}
