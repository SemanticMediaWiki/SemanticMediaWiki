<?php

namespace SMW\Tests\Utils\JSONScript;

use SMW\Message;
use SMW\Tests\Utils\File\ContentsReader;
use SMW\Tests\Utils\File\LocalFileUpload;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageDeleter;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JsonTestCaseContentHandler {

	/**
	 * @var PageCreator
	 */
	private $pageCreator;

	/**
	 * @var PageDeleter
	 */
	private $pageDeleter;

	/**
	 * @var LocalFileUpload
	 */
	private $LocalFileUpload;

	/**
	 * @var array
	 */
	private $pages = [];

	/**
	 * @var string
	 */
	private $skipOn = '';

	/**
	 * @var string
	 */
	private $testCaseLocation = '';

	/**
	 * @since 2.5
	 *
	 * @param PageCreator $pageCreator
	 * @param PageDeleter $pageDeleter
	 * @param LocalFileUpload $localFileUpload
	 */
	public function __construct( PageCreator $pageCreator, PageDeleter $pageDeleter, LocalFileUpload $localFileUpload ) {
		$this->pageCreator = $pageCreator;
		$this->pageDeleter = $pageDeleter;
		$this->localFileUpload = $localFileUpload;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getPages() {
		return $this->pages;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $skipOn
	 */
	public function skipOn( $skipOn ) {
		$this->skipOn = $skipOn;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $testCaseLocation
	 */
	public function setTestCaseLocation( $testCaseLocation ) {
		$this->testCaseLocation = $testCaseLocation;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $pages
	 * @param integer $defaultNamespace
	 */
	public function createPagesFrom( array $pages, $defaultNamespace = NS_MAIN ) {

		foreach ( $pages as $page ) {

			$skipOn = isset( $page['skip-on'] ) ? $page['skip-on'] : [];

			if ( in_array( $this->skipOn, array_keys( $skipOn ) ) ) {
				continue;
			}

			if ( ( !isset( $page['page'] ) && !isset( $page['name'] ) ) || !isset( $page['contents'] ) ) {
				continue;
			}

			$namespace = isset( $page['namespace'] ) ? constant( $page['namespace'] ) : $defaultNamespace;

			$this->createPage( $page, $namespace );
		}
	}

	/**
	 * @since 2.5
	 *
	 * @param array $page
	 * @param integer $defaultNamespace
	 */
	public function createPage( array $page, $namespace ) {

		$pageContentLanguage = isset( $page['contentlanguage'] ) ? $page['contentlanguage'] : '';

		if ( isset( $page['message-cache'] ) && $page['message-cache'] === 'clear' ) {
			Message::clear();
		}

		$name = ( isset( $page['name'] ) ? $page['name'] : $page['page'] );

		$title = Title::newFromText(
			$name,
			$namespace
		);

		if ( $namespace === NS_FILE && isset( $page['contents']['upload'] ) ) {
			return $this->doUploadFile( $title, $page['contents']['upload'] );
		}

		if ( is_array( $page['contents'] ) && isset( $page['contents']['import-from'] ) ) {
			$contents = ContentsReader::readContentsFrom(
				$this->testCaseLocation . $page['contents']['import-from']
			);
		} else {
			$contents = $page['contents'];
		}

		$this->pageCreator->createPage( $title, $contents, $pageContentLanguage );

		$this->pages[] = $this->pageCreator->getPage();

		if ( isset( $page['move-to'] ) ) {
			$this->doMovePage( $page, $namespace );
		}

		if ( isset( $page['do-purge'] ) ) {
			$this->pageCreator->getPage()->doPurge();
		}

		if ( isset( $page['do-delete'] ) && $page['do-delete'] ) {
			$this->pageDeleter->deletePage( $title );
		}
	}

	private function doMovePage( $page, $namespace ) {
		$target = Title::newFromText(
			$page['move-to']['target'],
			$namespace
		);

		$this->pageCreator->doMoveTo(
			$target,
			$page['move-to']['is-redirect']
		);

		$this->pages[] = $target;
	}

	private function doUploadFile( $title, array $contents ) {

		$this->localFileUpload->doUploadCopyFromLocation(
			$this->testCaseLocation . $contents['file'],
			$title->getText(),
			$contents['text']
		);

		TestEnvironment::executePendingDeferredUpdates();
		$this->pages[] = $title;
	}

}
