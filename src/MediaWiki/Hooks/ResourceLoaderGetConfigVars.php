<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use MediaWiki\Title\NamespaceInfo;
use SMW\Localizer\Localizer;
use SMW\Settings;

/**
 * Hook: ResourceLoaderGetConfigVars called right before
 * ResourceLoaderStartUpModule::getConfig and exports static configuration
 * variables to JavaScript. Things that depend on the current
 * page/request state should use MakeGlobalVariablesScript instead
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ResourceLoaderGetConfigVars implements ResourceLoaderGetConfigVarsHook {

	const OPTION_KEYS = [
		'smwgQMaxLimit',
		'smwgQMaxInlineLimit',
		'smwgNamespacesWithSemanticLinks',
		'smwgResultFormats'
	];

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly NamespaceInfo $namespaceInfo,
		private readonly Settings $settings,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		$vars['smw-config'] = [
			'version' => SMW_VERSION,
			'namespaces' => [],
			'settings' => [
				'smwgQMaxLimit' => $this->settings->get( 'smwgQMaxLimit' ),
				'smwgQMaxInlineLimit' => $this->settings->get( 'smwgQMaxInlineLimit' ),
			]
		];

		$localizer = Localizer::getInstance();

		// Available semantic namespaces
		foreach ( array_keys( $this->settings->get( 'smwgNamespacesWithSemanticLinks' ) ) as $ns ) {
			$name = $this->namespaceInfo->getCanonicalName( $ns );
			$vars['smw-config']['settings']['namespace'][$name] = $ns;
			$vars['smw-config']['namespaces']['canonicalName'][$ns] = $name;
			$vars['smw-config']['namespaces']['localizedName'][$ns] = $localizer->getNsText( $ns );
		}

		foreach ( array_keys( $this->settings->get( 'smwgResultFormats' ) ) as $format ) {
			$vars['smw-config']['formats'][$format] = htmlspecialchars( $format );
		}
	}

}
