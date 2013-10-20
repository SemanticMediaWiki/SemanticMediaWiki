<?php

namespace SMW;

use LinksUpdate;

/**
 * LinksUpdateConstructed hook is called at the end of LinksUpdate() is contruction
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
 *
 * @ingroup FunctionHook
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class LinksUpdateConstructed extends FunctionHook {

	/** @var LinksUpdate */
	protected $linksUpdate = null;

	/**
	 * @since  1.9
	 *
	 * @param LinksUpdate $linksUpdate
	 */
	public function __construct( LinksUpdate $linksUpdate ) {
		$this->linksUpdate = $linksUpdate;
	}

	/**
	 * @see FunctionHook::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->withContext()->getDependencyBuilder()->newObject( 'ParserData', array(
			'Title'        => $this->linksUpdate->getTitle(),
			'ParserOutput' => $this->linksUpdate->getParserOutput()
		) );

		$parserData->updateStore();

		return true;
	}

}
