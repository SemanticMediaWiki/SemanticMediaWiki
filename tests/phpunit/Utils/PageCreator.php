<?php

namespace SMW\Tests\Utils;

use Revision;
use Title;
use UnexpectedValueException;

/**
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.1
 */
class PageCreator {

	/** @var WikiPage */
	protected $page = null;

	/**
	 * @since 1.9.1
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
	 * @since 1.9.1
	 *
	 * @return PageCreator
	 */
	public function createPage( Title $title, $editContent = '' ) {

		$this->page = new \WikiPage( $title );

		if ( $editContent === '' ) {
			$editContent = 'Content of ' . $title->getFullText();
		}

		$editMessage = 'SMW system test: create page';

		return $this->doEdit( $editContent, $editMessage );
	}

	/**
	 * @since 1.9.1
	 *
	 * @return PageCreator
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
	 * @since 2.3
	 *
	 * @return PageCreator
	 */
	public function doMoveTo( Title $target, $isRedirect = true ) {

		$this->getPage()->getTitle()->moveTo(
			$target,
			false,
			"integration test",
			$isRedirect
		);

		return $this;
	}

	/**
	 * @since 2.0
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
			$this->getPage()->getRevision()->getContent( Revision::RAW ),
			null,
			null
		);
	}

}

// FIXME SemanticGlossary usage
class_alias( 'SMW\Tests\Utils\PageCreator', 'SMW\Tests\Util\PageCreator' );
