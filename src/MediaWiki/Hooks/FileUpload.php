<?php

namespace SMW\MediaWiki\Hooks;

use File;
use ParserOptions;
use SMW\ApplicationFactory;
use SMW\Localizer;
use Title;
use User;

/**
 * Fires when a local file upload occurs
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
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
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

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
		return $this->canPerformUpdate() ? $this->performUpdate() : true;
	}

	private function canPerformUpdate() {

		if ( $this->file->getTitle() !== null && $this->isSemanticEnabledNamespace( $this->file->getTitle() ) ) {
			return true;
		}

		return false;
	}

	private function performUpdate() {

		$this->applicationFactory = ApplicationFactory::getInstance();

		$filePage = $this->makeFilePage();

		$parserData = $this->applicationFactory
			->newParserData(
				$this->file->getTitle(),
				$filePage->getParserOutput( $this->makeCanonicalParserOptions() ) );

		$pageInfoProvider = $this->applicationFactory
			->newMwCollaboratorFactory()
			->newPageInfoProvider( $filePage );

		$propertyAnnotator = $this->applicationFactory
			->newPropertyAnnotatorFactory()
			->newPredefinedPropertyAnnotator( $parserData->getSemanticData(), $pageInfoProvider );

		$propertyAnnotator->addAnnotation();

		$parserData->pushSemanticDataToParserOutput();
		$parserData->updateStore();

		return true;
	}

	private function makeFilePage() {

		$filePage = $this->applicationFactory->newPageCreator()->createFilePage( $this->file->getTitle() );
		$filePage->setFile( $this->file );

		$filePage->smwFileReUploadStatus = $this->fileReUploadStatus;

		return $filePage;
	}

	/**
	 * Anonymous user with default preferences and content language
	 */
	private function makeCanonicalParserOptions() {
		return ParserOptions::newFromUserAndLang(
			new User(),
			Localizer::getInstance()->getContentLanguage()
		);
	}

	private function isSemanticEnabledNamespace( Title $title ) {
		return ApplicationFactory::getInstance()->getNamespaceExaminer()->isSemanticEnabled( $title->getNamespace() );
	}

}
