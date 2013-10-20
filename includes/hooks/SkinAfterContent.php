<?php

namespace SMW;

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
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SkinAfterContent extends FunctionHook {

	/** @var OutputPage */
	protected $data = null;

	/** @var ParserOutput */
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
	 * @see FunctionHook::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return $this->performUpdate( $this->skin->getOutput() );
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	protected function performUpdate( OutputPage $outputPage ) {

		/**
		 * @var FactboxCache $factboxCache
		 */
		$factboxCache = $this->withContext()->getDependencyBuilder()->newObject( 'FactboxCache', array(
			'OutputPage' => $outputPage
		) );

		$this->data .= $factboxCache->retrieveContent();

		return true;
	}

}
