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

	private $error = '';

	/**
	 * @since 2.1
	 *
	 * @param string $localUploadPath
	 * @param string $desiredDestName
	 */
	public function __construct( $localUploadPath, $desiredDestName ) {
		$this->localUploadPath = $localUploadPath;
		$this->desiredDestName = $desiredDestName;
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
	 * @since 2.1
	 *
	 * @param string $pageText
	 * @param string $comment
	 *
	 * @return boolean
	 */
	public function doUpload( $pageText = '', $comment = '' ) {

		$localUploadPath = $this->canRead( $this->localUploadPath );

		$this->initializePathInfo(
			$this->desiredDestName,
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

	private function canRead( $path ) {

		$path = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $path );

		if ( is_readable( $path ) ) {
			return $path;
		}

		throw new RuntimeException( "Expected an accessible {$path}" );
	}

}
