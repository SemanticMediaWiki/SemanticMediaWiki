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
	 * @var string
	 */
	protected $data = null;

	/**
	 * @var Skin
	 */
	protected $skin = null;

	/**
	 * @since  1.9
	 *
	 * @param string $data
	 * @param Skin|null $skin
	 */
	public function __construct( &$data, Skin $skin = null ) {
		$this->data =& $data;
		$this->skin = $skin;
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return $this->canPerformUpdate() ? $this->performUpdate() : true;
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

	private function performUpdate() {

		$cachedFactbox = ApplicationFactory::getInstance()->newFactboxFactory()->newCachedFactbox();

		$this->data .= $cachedFactbox->retrieveContent(
			$this->skin->getOutput()
		);

		return true;
	}

}
