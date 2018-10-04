<?php

namespace SMW\MediaWiki\Hooks;

use Skin;
use SMW\ApplicationFactory;

/**
 * SkinAfterContent hook to add text after the page content and
 * article metadata
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SkinAfterContent {

	/**
	 * @var Skin
	 */
	private $skin = null;

	/**
	 * @since  1.9
	 *
	 * @param Skin|null $skin
	 */
	public function __construct( Skin $skin = null ) {
		$this->skin = $skin;
	}

	/**
	 * @since 1.9
	 *
	 * @param string &$data
	 *
	 * @return true
	 */
	public function performUpdate( &$data ) {

		if ( $this->canAddFactbox() ) {
			$this->addFactboxTo( $data );
		}

		return true;
	}

	private function canAddFactbox() {

		if ( !$this->skin instanceof Skin || !ApplicationFactory::getInstance()->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return false;
		}

		$request = $this->skin->getContext()->getRequest();

		if ( in_array( $request->getVal( 'action' ), [ 'delete', 'purge', 'protect', 'unprotect', 'history' ] ) ) {
			return false;
		}

		return true;
	}

	private function addFactboxTo( &$data ) {

		$cachedFactbox = ApplicationFactory::getInstance()->singleton( 'FactboxFactory' )->newCachedFactbox();

		$data .= $cachedFactbox->retrieveContent(
			$this->skin->getOutput()
		);
	}

}
