<?php

namespace SMW\MediaWiki\Hooks;

use MWNamespace;
use SMW\Localizer;

/**
 * Hook: ResourceLoaderGetConfigVars called right before
 * ResourceLoaderStartUpModule::getConfig and exports static configuration
 * variables to JavaScript. Things that depend on the current
 * page/request state should use MakeGlobalVariablesScript instead
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ResourceLoaderGetConfigVars extends HookHandler {

	/**
	 * @since 1.9
	 *
	 * @param array $vars
	 *
	 * @return boolean
	 */
	public function process( array &$vars ) {

		$vars['smw-config'] = [
			'version' => SMW_VERSION,
			'namespaces' => [],
			'settings' => [
				'smwgQMaxLimit' => $GLOBALS['smwgQMaxLimit'],
				'smwgQMaxInlineLimit' => $GLOBALS['smwgQMaxInlineLimit'],
			]
		];

		$localizer = Localizer::getInstance();

		// Available semantic namespaces
		foreach ( array_keys( $GLOBALS['smwgNamespacesWithSemanticLinks'] ) as $ns ) {
			$name = MWNamespace::getCanonicalName( $ns );
			$vars['smw-config']['settings']['namespace'][$name] = $ns;
			$vars['smw-config']['namespaces']['canonicalName'][$ns] = $name;
			$vars['smw-config']['namespaces']['localizedName'][$ns] = $localizer->getNamespaceTextById( $ns );
		}

		foreach ( array_keys( $GLOBALS['smwgResultFormats'] ) as $format ) {
			$vars['smw-config']['formats'][$format] = htmlspecialchars( $format );
		}

		return true;
	}

}
