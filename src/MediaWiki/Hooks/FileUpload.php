<?php

namespace SMW\MediaWiki\Hooks;

use File;
use ParserOptions;
use SMW\ApplicationFactory;
use SMW\Localizer;
use Title;
use User;
use Hooks;

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
	 * @var boolean
	 */
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

		$applicationFactory = ApplicationFactory::getInstance();
		$filePage = $this->makeFilePage();

		$parserData = $applicationFactory->newParserData(
			$this->file->getTitle(),
			$filePage->getParserOutput( $this->makeCanonicalParserOptions() )
		);

		$pageInfoProvider = $applicationFactory->newMwCollaboratorFactory()->newPageInfoProvider(
			$filePage
		);

		$propertyAnnotatorFactory = $applicationFactory->singleton( 'PropertyAnnotatorFactory' );

		$propertyAnnotator = $propertyAnnotatorFactory->newNullPropertyAnnotator(
			$parserData->getSemanticData()
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newPredefinedPropertyAnnotator(
			$propertyAnnotator,
			$pageInfoProvider
		);

		$propertyAnnotator->addAnnotation();

		// 2.4+
		Hooks::run( 'SMW::FileUpload::BeforeUpdate', array( $filePage, $parserData->getSemanticData() ) );

		$parserData->setOrigin( 'FileUpload' );

		$parserData->pushSemanticDataToParserOutput();
		$parserData->updateStore( true );

		return true;
	}

	private function makeFilePage() {

		$filePage = ApplicationFactory::getInstance()->newPageCreator()->createFilePage(
			$this->file->getTitle()
		);

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
