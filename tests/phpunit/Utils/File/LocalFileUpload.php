<?php

namespace SMW\Tests\Utils\File;

use RuntimeException;
use SMW\Tests\Utils\Mock\MockSuperUser;
use UploadBase;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class LocalFileUpload extends UploadBase {

	/**
	 * @var boolean
	 */
	private $removeTemporaryFile = true;

	/**
	 * @var string
	 */
	private $localUploadPath;

	/**
	 * @var string
	 */
	private $desiredDestName;

	/**
	 * @var DummyFileCreator
	 */
	private $dummyFileCreator;

	/**
	 * @var string
	 */
	private $error = '';

	/**
	 * @since 2.1
	 *
	 * @param string $localUploadPath
	 * @param string $desiredDestName
	 */
	public function __construct( $localUploadPath = '', $desiredDestName = '' ) {
		$this->localUploadPath = $localUploadPath;
		$this->desiredDestName = $desiredDestName;
	}

	/**
	 * @since 2.5
	 *
	 * @param DummyFileCreator $dummyFileCreator
	 */
	public function setDummyFileCreator( DummyFileCreator $dummyFileCreator ) {
		$this->dummyFileCreator = $dummyFileCreator;
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getUploadError() {
		return $this->error;
	}

	/**
	 * @since 2.1
	 */
	public function delete() {
		unlink( $this->localUploadPath );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $localUploadPath
	 * @param string $desiredDestName
	 * @param string $pageText
	 * @param string $comment
	 *
	 * @return boolean
	 */
	public function doUploadCopyFromLocation( $localUploadPath, $desiredDestName, $pageText = '', $comment = '' ) {

		if ( !$this->dummyFileCreator instanceof DummyFileCreator ) {
			throw new RuntimeException( "Expected a DummyFileCreator instance." );
		}

		$this->dummyFileCreator->createFileWithCopyFrom(
			$desiredDestName,
			$localUploadPath
		);

		$this->doUploadFromLocation(
			$this->dummyFileCreator->getPath(),
			$desiredDestName,
			$pageText,
			$comment
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param string $localUploadPath
	 * @param string $desiredDestName
	 * @param string $pageText
	 * @param string $comment
	 *
	 * @return boolean
	 */
	public function doUploadFromLocation( $localUploadPath, $desiredDestName, $pageText = '', $comment = '' ) {

		$localUploadPath = $this->createReadablePath( $localUploadPath );

		$this->initializePathInfo(
			$desiredDestName,
			$localUploadPath,
			filesize( $localUploadPath ),
			$this->removeTemporaryFile
		);

		$status = $this->performUpload(
			$comment,
			$pageText,
			false,
			new MockSuperUSer()
		);

		if ( !$status->isGood() ) {
			$this->error = $status->getWikiText();
			return false;
		}

		return true;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $pageText
	 * @param string $comment
	 *
	 * @return boolean
	 */
	public function doUpload( $pageText = '', $comment = '' ) {
		return $this->doUploadFromLocation( $this->localUploadPath, $this->desiredDestName, $pageText, $comment );
	}

	/**
	 * @see UploadBase::initializeFromRequest
	 */
	public function initializeFromRequest( &$request ) {
	}

	/**
	 * @see UploadBase::getSourceType
	 */
	public function getSourceType() {
		return 'file';
	}

	private function createReadablePath( $path ) {

		$path = str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $path );

		if ( is_readable( $path ) ) {
			return $path;
		}

		throw new RuntimeException( "Expected an accessible {$path}" );
	}

}
