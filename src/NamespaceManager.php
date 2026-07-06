<?php

namespace SMW;

use SMW\Exception\SiteLanguageChangeException;
use SMW\Localizer\LocalLanguage\LocalLanguage;

/**
 * Handles SMW's CanonicalNamespaces hook: localised namespace names,
 * aliases, search defaults, and the semantic-link defaults seed. Static
 * namespace registration (constants, canonical English names, subpages,
 * content flag, default content model) is owned by extension.json's
 * `namespaces` block.
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 * @author others
 */
class NamespaceManager {

	private LocalLanguage $localLanguage;

	private static string $initLanguageCode = '';

	/**
	 * @since 1.9
	 */
	public function __construct( ?LocalLanguage $localLanguage = null ) {
		$this->localLanguage = $localLanguage ?? LocalLanguage::getInstance();
	}

	/**
	 * @since 3.2
	 */
	public static function clear(): void {
		self::$initLanguageCode = '';
	}

	/**
	 * @see Hooks:CanonicalNamespaces
	 *
	 * @since 2.5
	 */
	public static function initCanonicalNamespaces( array &$namespaces ): bool {
		$instance = new self();
		$vars = $GLOBALS;
		$localLanguage = $instance->localLanguage->fetch( $instance->getLanguageCode( $vars ) );
		$extraNamespaces = $localLanguage->getNamespaces();
		$namespaceAliases = $localLanguage->getNamespaceAliases();
		$canonicalNames = self::getCanonicalNames();

		// T160665: drop any existing namespace that uses a name we own.
		$namespacesByName = array_flip( $namespaces );
		foreach ( $canonicalNames as $id => $name ) {
			if ( isset( $namespacesByName[$name] ) ) {
				unset( $namespaces[$namespacesByName[$name]] );
			}
		}

		// Left-wins ordering: localised names beat canonical names beat any
		// entry already at SMW's IDs. SMW takes precedence at its own IDs;
		// the previous behaviour preserved third-party registrations at the
		// same IDs, but extension.json's `namespaces` block has already
		// claimed them by the time this hook fires.
		$namespaces = $extraNamespaces + $canonicalNames + $namespaces;

		// $smwgNamespacesWithSemanticLinks defaults are seeded in
		// ConfigBootstrap::seedComputedDefaults() so Settings::loadFromGlobals()
		// (called inside wgExtensionFunctions) sees them before this hook fires.
		Globals::replace( [
			'wgNamespaceAliases' => $namespaceAliases
				+ array_flip( $extraNamespaces )
				+ array_flip( $canonicalNames )
				+ ( $vars['wgNamespaceAliases'] ?? [] ),
			'wgNamespacesToBeSearchedDefault' => ( $vars['wgNamespacesToBeSearchedDefault'] ?? [] ) + [
				SMW_NS_PROPERTY => true,
				SMW_NS_CONCEPT  => true,
			],
		] );

		return true;
	}

	/**
	 * @since 1.9
	 */
	public static function getCanonicalNames(): array {
		return [
			SMW_NS_PROPERTY      => 'Property',
			SMW_NS_PROPERTY_TALK => 'Property_talk',
			SMW_NS_CONCEPT       => 'Concept',
			SMW_NS_CONCEPT_TALK  => 'Concept_talk',
			SMW_NS_SCHEMA        => 'smw/schema',
			SMW_NS_SCHEMA_TALK   => 'smw/schema_talk',
		];
	}

	private function getLanguageCode( array $vars ): string {
		if ( self::$initLanguageCode === '' ) {
			self::$initLanguageCode = $vars['wgLanguageCode'];
			return self::$initLanguageCode;
		}

		if ( self::$initLanguageCode === $vars['wgLanguageCode'] ) {
			return self::$initLanguageCode;
		}

		// #4680: prevent users from changing wgLanguageCode after init.
		throw new SiteLanguageChangeException( self::$initLanguageCode, $vars['wgLanguageCode'] );
	}
}
