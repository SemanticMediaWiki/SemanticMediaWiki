<?php

namespace SMW\Tests\Utils;

use Revision;
use SMW\Tests\TestEnvironment;
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

	/**
	 * @var TestEnvironment
	 */
	private $testEnvironment;

	/**
	 * @var null
	 */
	protected $page = null;

	public function __construct() {
		$this->testEnvironment = new TestEnvironment();
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

		$this->testEnvironment->executePendingDeferredUpdates();

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

		$this->testEnvironment->executePendingDeferredUpdates();

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

// FIXME SemanticGlossary usage
class_alias( 'SMW\Tests\Utils\PageCreator', 'SMW\Tests\Util\PageCreator' );
