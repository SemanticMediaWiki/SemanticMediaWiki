<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Application;
use LinksUpdate;

/**
 * LinksUpdateConstructed hook is called at the end of LinksUpdate() is contruction
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
 *
 * @ingroup FunctionHook
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class LinksUpdateConstructed {

	/**
	 * @var LinksUpdate
	 */
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
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		/**
		 * @var ParserData $parserData
		 */
		$parserData = Application::getInstance()->newParserData(
			$this->linksUpdate->getTitle(),
			$this->linksUpdate->getParserOutput()
		);

		$parserData->updateStore();

		return true;
	}

}
