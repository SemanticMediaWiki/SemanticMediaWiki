<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Application;

use File;

/**
 * Fires when a local file upload occurs
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
 *
 * @ingroup FunctionHook
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class FileUpload {

	/**
	 * @var File
	 */
	private $file = null;

	/**
	 * @var Application
	 */
	private $application = null;

	private $fileReUploadStatus = false;

	/**
	 * @since  1.9.1
	 *
	 * @param File $file
	 * @param boolean $fileReUploadStatus
	 */
	public function __construct( File $file, $fileReUploadStatus = false ) {
		$this->file = $file;
		$this->fileReUploadStatus = $fileReUploadStatus;
	}

	/**
	 * @since 1.9.1
	 *
	 * @return true
	 */
	public function process() {
		return $this->file->getTitle() ? $this->performUpdate() : true;
	}

	protected function performUpdate() {

		$this->application = Application::getInstance();

		$parserData = $this->application
			->newParserData( $this->file->getTitle(), $this->makeParserOutput() );

		$pageInfoProvider = $this->application
			->newPropertyAnnotatorFactory()
			->newPageInfoProvider( $this->makeFilePage() );

		$propertyAnnotator = $this->application
			->newPropertyAnnotatorFactory()
			->newPredefinedPropertyAnnotator( $parserData->getSemanticData(), $pageInfoProvider );

		$propertyAnnotator->addAnnotation();

		$parserData->updateOutput();
		$parserData->updateStore();

		return true;
	}

	private function makeParserOutput() {

		$contentParser = $this->application->newContentParser( $this->file->getTitle() );
		$contentParser->parse();

		return $contentParser->getOutput();
	}

	private function makeFilePage() {

		$filePage = $this->application->newPageCreator()->createFilePage( $this->file->getTitle() );
		$filePage->setFile( $this->file );

		$filePage->smwFileReUploadStatus = $this->fileReUploadStatus;

		return $filePage;
	}

}
