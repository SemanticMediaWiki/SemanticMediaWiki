<?php

namespace SMW;

use LinksUpdate;

/**
 * LinksUpdateConstructed hook
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * LinksUpdateConstructed hook is called at the end of LinksUpdate() is contruction
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
 *
 * @ingroup Hook
 */
class LinksUpdateConstructed extends MediaWikiHook {

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
	 * @see HookBase::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		$parserData = new ParserData( $this->linksUpdate->getTitle(), $this->linksUpdate->getParserOutput() );
		$parserData->updateStore();

		return true;
	}

}
