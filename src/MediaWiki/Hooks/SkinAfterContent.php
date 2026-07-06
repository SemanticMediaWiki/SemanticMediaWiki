<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\SkinAfterContentHook;
use Skin;
use SMW\Factbox\FactboxFactory;

/**
 * SkinAfterContent hook to add text after the page content and
 * article metadata
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SkinAfterContent implements SkinAfterContentHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct( private readonly FactboxFactory $factboxFactory ) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSkinAfterContent( &$data, $skin ) {
		if ( $this->canAddFactbox( $skin ) ) {
			$this->addFactboxTo( $skin, $data );
		}

		return true;
	}

	private function canAddFactbox( ?Skin $skin ): bool {
		if ( !$skin instanceof Skin || !defined( 'SMW_EXTENSION_LOADED' ) ) {
			return false;
		}

		$request = $skin->getContext()->getRequest();

		if ( in_array( $request->getVal( 'action' ), [ 'delete', 'purge', 'protect', 'unprotect', 'history' ] ) ) {
			return false;
		}

		return true;
	}

	private function addFactboxTo( Skin $skin, string &$data ): void {
		$cachedFactbox = $this->factboxFactory->newCachedFactbox();

		$data .= $cachedFactbox->retrieveContent(
			$skin->getOutput()
		);
	}

}
