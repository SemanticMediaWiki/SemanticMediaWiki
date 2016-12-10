<?php

namespace SMW\Tests\Utils;

use Revision;
use SMW\Tests\TestEnvironment;
use Title;
use UnexpectedValueException;

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

		if ( class_exists( 'ContentHandler' ) ) {
			$content = \ContentHandler::makeContent(
				$pageContent,
				$this->getPage()->getTitle()
			);

			$this->getPage()->doEditContent(
				$content,
				$editMessage
			);

		} else {
			$this->getPage()->doEdit( $pageContent, $editMessage );
		}

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

		$this->getPage()->getTitle()->moveTo(
			$target,
			false,
			"integration test",
			$isRedirect
		);

		TestEnvironment::executePendingDeferredUpdates();

		return $this;
	}

	/**
	 * @since 2.0
	 *
	 * @return EditInfo
	 */
	public function getEditInfo() {

		$revision = $this->getPage()->getRevision();

		if ( class_exists( 'ContentHandler' ) ) {

			$content = $revision->getContent();
			$format  = $content->getContentHandler()->getDefaultFormat();

			return $this->getPage()->prepareContentForEdit(
				$content,
				null,
				null,
				$format
			);
		}

		$text = method_exists( $revision, 'getContent' ) ? $revision->getContent( Revision::RAW ) : $revision->getRawText();

		return $this->getPage()->prepareTextForEdit(
			$text,
			null,
			null
		);
	}

}
