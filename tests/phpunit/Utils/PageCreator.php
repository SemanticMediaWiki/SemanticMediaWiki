<?php

namespace SMW\Tests\Utils;

use Revision;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockSuperUser;
use Title;
use UnexpectedValueException;
use SMW\MediaWiki\EditInfo;

/**
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class PageCreator {

	/**
	 * @var null
	 */
	protected $page = null;

	/**
	 * @since 3.1
	 *
	 * @return WikiPage
	 */
	public function setPage( \WikiPage $page ) {
		$this->page = $page;
	}

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
	 * @param Title $title
	 * @param string $editContent
	 * @param string $pageContentLanguage
	 *
	 * @return PageCreator
	 */
	public function createPage( Title $title, $editContent = '', $pageContentLanguage = '' ) {

		if ( $pageContentLanguage !== '' ) {
			\Hooks::register( 'PageContentLanguage', function( $titleByHook, &$pageLang ) use( $title, $pageContentLanguage ) {

				// Only change the pageContentLanguage for the selected page
				if ( $title->getPrefixedDBKey() === $titleByHook->getPrefixedDBKey() ) {
					$pageLang = $pageContentLanguage;
				}

				// MW 1.19
				return true;
			} );
		}

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
	 * @param string $pageContent
	 * @param string $editMessage
	 *
	 * @return PageCreator
	 */
	public function doEdit( $pageContent = '', $editMessage = '' ) {

		$content = \ContentHandler::makeContent(
			$pageContent,
			$this->getPage()->getTitle()
		);

		$this->getPage()->doEditContent(
			$content,
			$editMessage
		);

		TestEnvironment::executePendingDeferredUpdates();

		return $this;
	}

	/**
	 * @since 2.3
	 *
	 * @param Title $target
	 * @param boolean $isRedirect
	 *
	 * @return PageCreator
	 */
	public function doMoveTo( Title $target, $isRedirect = true ) {

		$reason = "integration test";
		$source = $this->getPage()->getTitle();

		if ( class_exists( '\MovePage' ) ) {
			$mp = new \MovePage( $source, $target );
			$status = $mp->move( new MockSuperUser(), $reason, $isRedirect );
		} else {
			// deprecated since 1.25, use the MovePage class instead
			$status = $source->moveTo( $target, false, $reason, $isRedirect );
		}

		TestEnvironment::executePendingDeferredUpdates();

		if ( $status->isOK() ) {
			return true;
		} else {
			return $status->getErrorsArray();
		}
	}

	/**
	 * @since 2.0
	 *
	 * @return EditInfo
	 */
	public function getEditInfo() {

		$editInfo = new EditInfo(
			$this->getPage()
		);

		return $editInfo->fetchEditInfo();
	}

}
