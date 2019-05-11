<?php

namespace SMW\Tests\Utils\Page;

use Revision;
use RuntimeException;
use Title;
use WikiPage;
use SMW\MediaWiki\EditInfo;

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
	private $page;

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

		$content = new \WikitextContent( $pageContent );

		$this->getPage()->doEditContent(
			$content,
			$editMessage
		);

		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function getEditInfo() {

		$editInfo = new EditInfo(
			$this->getPage()
		);

		return $editInfo->fetchEditInfo();
	}

}
