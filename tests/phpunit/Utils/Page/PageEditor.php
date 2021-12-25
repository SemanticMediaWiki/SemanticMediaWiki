<?php

namespace SMW\Tests\Utils\Page;

use CommentStoreComment;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\RevisionSlotsUpdate;
use RequestContext;
use RuntimeException;
use Title;
use WikiPage;
use SMW\Services\ServicesFactory;

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

		// Simplified implementation of WikiPage::doUserEditContent() from MW 1.36
		$performer = RequestContext::getMain()->getUser();
		$summary = CommentStoreComment::newUnsavedComment( trim( $editMessage ) );

		$slotsUpdate = new RevisionSlotsUpdate();
		$slotsUpdate->modifyContent( SlotRecord::MAIN, $content );

		$updater = $this->getPage()->newPageUpdater( $performer, $slotsUpdate );
		$updater->setContent( SlotRecord::MAIN, $content );
		$updater->saveRevision( $summary );

		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function getEditInfo() {

		$editInfo = ServicesFactory::getInstance()->newMwCollaboratorFactory()->newEditInfo(
			$this->getPage()
		);

		return $editInfo->fetchEditInfo();
	}

}
