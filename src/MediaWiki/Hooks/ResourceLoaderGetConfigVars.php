<?php

namespace SMW\MediaWiki\Hooks;

use MWNamespace;

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
class ResourceLoaderGetConfigVars {

	/**
	 * @var array
	 */
	protected $vars;

	/**
	 * @since  2.0
	 *
	 * @param array $vars
	 */
	public function __construct( array &$vars ) {
		$this->vars =& $vars;
	}

	/**
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function process() {

		$this->vars['smw-config'] = array(
			'version' => SMW_VERSION,
			'settings' => array(
				'smwgQMaxLimit' => $GLOBALS['smwgQMaxLimit'],
				'smwgQMaxInlineLimit' => $GLOBALS['smwgQMaxInlineLimit'],
			)
		);

		// Available semantic namespaces
		foreach ( array_keys( $GLOBALS['smwgNamespacesWithSemanticLinks'] ) as $ns ) {
			$name = MWNamespace::getCanonicalName( $ns );
			$this->vars['smw-config']['settings']['namespace'][$name] = $ns;
		}

		foreach ( array_keys( $GLOBALS['smwgResultFormats'] ) as $format ) {
			$this->vars['smw-config']['formats'][$format] = htmlspecialchars( $format );
		}

		return true;
	}

}
