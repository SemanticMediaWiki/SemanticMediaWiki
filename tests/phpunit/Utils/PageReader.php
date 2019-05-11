<?php

namespace SMW\Tests\Utils;

use Revision;
use TextContent;
use Title;
use UnexpectedValueException;
use SMW\MediaWiki\EditInfo;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PageReader {

	/**
	 * @var WikiPage
	 */
	private $page = null;

	/**
	 * @since 2.1
	 *
	 * @return WikiPage
	 * @throws UnexpectedValueException
	 */
	public function getPage() {

		if ( $this->page instanceof \WikiPage ) {
			return $this->page;
		}

		throw new UnexpectedValueException( 'Expected a WikiPage instance, use createPage first' );
	}

	/**
	 * @since 2.1
	 *
	 * @param Title $title
	 *
	 * @return text
	 */
	public function getContentAsText( Title $title ) {

		$this->page = new \WikiPage( $title );
		$content = $this->page->getContent();

		return $content->getNativeData();
	}

	/**
	 * @since 2.2
	 */
	public function getEditInfo( Title $title ) {

		$this->page = new \WikiPage( $title );

		$editInfo = new EditInfo(
			$this->getPage()
		);

		return $editInfo->fetchEditInfo();
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 *
	 * @return ParserOutput|null
	 */
	public function getParserOutputFromEdit( Title $title ) {
		return $this->getEditInfo( $title )->getOutput();
	}

}
