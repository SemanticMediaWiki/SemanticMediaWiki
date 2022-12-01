<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Globals;
use SMW\Localizer;
use SMW\MediaWiki\NamespaceInfo;
use SMW\MediaWiki\HookListener;
use SMW\OptionsAwareTrait;

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
class ResourceLoaderGetConfigVars implements HookListener {

	use OptionsAwareTrait;

	const OPTION_KEYS = [
		'smwgQMaxLimit',
		'smwgQMaxInlineLimit',
		'smwgNamespacesWithSemanticLinks',
		'smwgResultFormats'
	];

	/**
	 * @var NamespaceInfo
	 */
	private $namespaceInfo;

	/**
	 * @since 3.1
	 *
	 * @param NamespaceInfo $namespaceInfo
	 */
	public function __construct( NamespaceInfo $namespaceInfo ) {
		$this->namespaceInfo = $namespaceInfo;
	}

	/**
	 * @since 1.9
	 *
	 * @param array $vars
	 *
	 * @return array $newVars
	 */
	public function process( array $vars ) {
		$newVars = [];

		$newVars['smw-config'] = [
			'version' => SMW_VERSION,
			'namespaces' => [],
			'settings' => [
				'smwgQMaxLimit' => $this->getOption( 'smwgQMaxLimit' ),
				'smwgQMaxInlineLimit' => $this->getOption( 'smwgQMaxInlineLimit' ),
			]
		];

		$localizer = Localizer::getInstance();

		// Available semantic namespaces
		foreach ( array_keys( $this->getOption( 'smwgNamespacesWithSemanticLinks' ) ) as $ns ) {
			$name = $this->namespaceInfo->getCanonicalName( $ns );
			$newVars['smw-config']['settings']['namespace'][$name] = $ns;
			$newVars['smw-config']['namespaces']['canonicalName'][$ns] = $name;
			$newVars['smw-config']['namespaces']['localizedName'][$ns] = $localizer->getNsText( $ns );
		}

		foreach ( array_keys( $this->getOption( 'smwgResultFormats' ) ) as $format ) {
			$newVars['smw-config']['formats'][$format] = htmlspecialchars( $format );
		}

		Globals::replace( $newVars );

		return $newVars;
	}

}
