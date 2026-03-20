<?php

namespace SMW\Property\Annotators;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\Parser\AnnotationProcessor;
use SMW\ProcessingErrorMsgHandler;
use SMW\Property\Annotator;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * Handling category annotation
 *
 * @ingroup SMW
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class CategoryPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @var array|null
	 */
	private $hiddenCategories = null;

	/**
	 * @var bool
	 */
	private $showHiddenCategories = true;

	/**
	 * @var bool
	 */
	private $useCategoryInstance = true;

	/**
	 * @var bool
	 */
	private $useCategoryHierarchy = true;

	/**
	 * @var bool
	 */
	private $useCategoryRedirect = true;

	private ProcessingErrorMsgHandler $processingErrorMsgHandler;

	/**
	 * @since 1.9
	 */
	public function __construct(
		Annotator $propertyAnnotator,
		private readonly array $categories,
	) {
		parent::__construct( $propertyAnnotator );
	}

	/**
	 * @since 2.3
	 *
	 * @param bool $showHiddenCategories
	 */
	public function showHiddenCategories( $showHiddenCategories ): void {
		$this->showHiddenCategories = (bool)$showHiddenCategories;
	}

	/**
	 * @since 2.3
	 *
	 * @param bool $useCategoryInstance
	 */
	public function useCategoryInstance( $useCategoryInstance ): void {
		$this->useCategoryInstance = (bool)$useCategoryInstance;
	}

	/**
	 * @since 2.3
	 *
	 * @param bool $useCategoryHierarchy
	 */
	public function useCategoryHierarchy( $useCategoryHierarchy ): void {
		$this->useCategoryHierarchy = (bool)$useCategoryHierarchy;
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $useCategoryRedirect
	 */
	public function useCategoryRedirect( $useCategoryRedirect ): void {
		$this->useCategoryRedirect = (bool)$useCategoryRedirect;
	}

	/**
	 * @see PropertyAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {
		$subject = $this->getSemanticData()->getSubject();
		$namespace = $subject->getNamespace();
		$property = null;

		$this->processingErrorMsgHandler = new ProcessingErrorMsgHandler(
			$subject
		);

		if ( $this->useCategoryInstance && ( $namespace !== NS_CATEGORY ) ) {
			$property = new Property( Property::TYPE_CATEGORY );
		}

		if ( $this->useCategoryHierarchy && ( $namespace === NS_CATEGORY ) ) {
			$property = new Property( Property::TYPE_SUBCATEGORY );
		}

		$semanticData = $this->getSemanticData();

		$annotationProcessor = new AnnotationProcessor(
			$semanticData,
			DataValueFactory::getInstance()
		);

		foreach ( $this->categories as $catname ) {

			if ( ( !$this->showHiddenCategories && $this->isHiddenCategory( $catname ) ) || $property === null ) {
				continue;
			}

			$this->modifySemanticData( $semanticData, $annotationProcessor, $subject, $property, $catname );
		}

		$annotationProcessor->release();
	}

	private function modifySemanticData( $semanticData, $annotationProcessor, $subject, $property, $catname ) {
		$cat = new WikiPage( $catname, NS_CATEGORY );

		if ( ( $cat = $this->getRedirectTarget( $cat ) ) && $cat->getNamespace() === NS_CATEGORY ) {

			$dataValue = $annotationProcessor->newDataValueByItem(
				$cat,
				$property,
				false,
				$subject
			);

			// Explicitly run a check here since the annotation process is run after
			// any visible output to the user hereby ensure that violations are caught
			// by the constraint error lookup
			$dataValue->checkConstraints();

			$semanticData->addPropertyObjectValue(
				$dataValue->getProperty(),
				$cat
			);

			return $semanticData->addDataValue( $dataValue );
		}

		$container = $this->processingErrorMsgHandler->newErrorContainerFromMsg(
			[
				'smw-category-invalid-redirect-target',
				str_replace( '_', ' ', $catname )
			]
		);

		$this->processingErrorMsgHandler->addToSemanticData(
			$semanticData,
			$container
		);
	}

	private function isHiddenCategory( $catName ): bool {
		if ( $this->hiddenCategories === null ) {

			$wikipage = ApplicationFactory::getInstance()->newPageCreator()->createPage(
				$this->getSemanticData()->getSubject()->getTitle()
			);

			$this->hiddenCategories = $wikipage->getHiddenCategories();
		}

		foreach ( $this->hiddenCategories as $hiddenCategory ) {

			if ( $hiddenCategory->getText() === $catName ) {
				return true;
			}

		}

		return false;
	}

	private function getRedirectTarget( $subject ) {
		if ( $this->useCategoryRedirect ) {
			return ApplicationFactory::getInstance()->getStore()->getRedirectTarget( $subject );
		}

		return $subject;
	}

}
