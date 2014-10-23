<?php

namespace SMW\Tests\Util\Page;

use Title;
use UnexpectedValueException;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 */
class PageEditor {

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
	 * @return PageEditor
	 */
	public function editPage( Title $title ) {
		$this->page = new \WikiPage( $title );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @return PageEditor
	 */
	public function doEdit( $pageContent = '', $editMessage = '' ) {

		if ( class_exists( 'WikitextContent' ) ) {
			$content = new \WikitextContent( $pageContent );

			$this->getPage()->doEditContent(
				$content,
				$editMessage
			);

		} else {
			$this->getPage()->doEdit( $pageContent, $editMessage );
		}

		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function getEditInfo() {

		if ( class_exists( 'WikitextContent' ) ) {

			$content = $this->getPage()->getRevision()->getContent();
			$format  = $content->getContentHandler()->getDefaultFormat();

			return $this->getPage()->prepareContentForEdit(
				$content,
				null,
				null,
				$format
			);
		}

		return $this->getPage()->prepareTextForEdit(
			$this->getPage()->getRevision()->getRawText(),
			null,
			null
		);
	}

}
