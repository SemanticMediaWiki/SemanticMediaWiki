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

		if ( !$this->canPerformUpdate() ) {
			return true;
		}

		return $this->doPerformUpdate( $data );
	}

	private function canPerformUpdate() {

		if ( !$this->skin instanceof Skin ) {
			return false;
		}

		$request = $this->skin->getContext()->getRequest();

		if ( $request->getVal( 'action' ) === 'delete' || $request->getVal( 'action' ) === 'purge' || !ApplicationFactory::getInstance()->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return false;
		}

		return true;
	}

	private function doPerformUpdate( &$data ) {

		$cachedFactbox = ApplicationFactory::getInstance()->singleton( 'FactboxFactory' )->newCachedFactbox();

		$data .= $cachedFactbox->retrieveContent(
			$this->skin->getOutput()
		);

		return true;
	}

}
