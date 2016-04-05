<?php

namespace SMW\Tests\Utils\Page;

use Revision;
use RuntimeException;
use Title;
use WikiPage;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 */
class PageEditor {

	/**
	 * @var WikiPage|null
	 */
	private $page = null;

	/**
	 * @since 2.1
	 *
	 * @return WikiPage
	 * @throws RuntimeException
	 */
	public function getPage() {

		if ( $this->page instanceof WikiPage ) {
			return $this->page;
		}

		throw new RuntimeException( 'Expected a valid WikiPage instance.' );
	}

	/**
	 * @since 2.1
	 *
	 * @param Title $title
	 *
	 * @return PageEditor
	 */
	public function editPage( Title $title ) {
		$this->page = new WikiPage( $title );
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $pageContent
	 * @param string $editMessage
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

		if ( method_exists( $this->getPage()->getRevision(), 'getContent' ) ) {
			$text = $this->getPage()->getRevision()->getContent( Revision::RAW );
		} else {
			$text = $this->getPage()->getRevision()->getRawText();
		}
		return $this->getPage()->prepareTextForEdit(
			$text,
			null,
			null
		);
	}

}
