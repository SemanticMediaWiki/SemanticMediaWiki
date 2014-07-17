<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Application;

use OutputPage;
use Title;
use Skin;

/**
 * SkinAfterContent hook to add text after the page content and
 * article metadata
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
 *
 * @note This hook is used for inserting the Factbox text after the
 * article contents (including categories).
 *
 * @ingroup FunctionHook
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

		$factboxCache = Application::getInstance()->newFactboxBuilder()->newFactboxCache( $this->skin->getOutput() );
		$this->data .= $factboxCache->retrieveContent();

		return true;
	}

}
