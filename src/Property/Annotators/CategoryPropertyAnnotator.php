<?php

namespace SMW\Property\Annotators;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\ProcessingErrorMsgHandler;
use SMW\PropertyAnnotator;
use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\Parser\AnnotationProcessor;

/**
 * Handling category annotation
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CategoryPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @var array
	 */
	private $categories;

	/**
	 * @var array|null
	 */
	private $hiddenCategories = null;

	/**
	 * @var boolean
	 */
	private $showHiddenCategories = true;

	/**
	 * @var boolean
	 */
	private $useCategoryInstance = true;

	/**
	 * @var boolean
	 */
	private $useCategoryHierarchy = true;

	/**
	 * @var boolean
	 */
	private $useCategoryRedirect = true;

	/**
	 * @since 1.9
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param array $categories
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, array $categories ) {
		parent::__construct( $propertyAnnotator );
		$this->categories = $categories;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $showHiddenCategories
	 */
	public function showHiddenCategories( $showHiddenCategories ) {
		$this->showHiddenCategories = (bool)$showHiddenCategories;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $useCategoryInstance
	 */
	public function useCategoryInstance( $useCategoryInstance ) {
		$this->useCategoryInstance = (bool)$useCategoryInstance;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $useCategoryHierarchy
	 */
	public function useCategoryHierarchy( $useCategoryHierarchy ) {
		$this->useCategoryHierarchy = (bool)$useCategoryHierarchy;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $useCategoryRedirect
	 */
	public function useCategoryRedirect( $useCategoryRedirect ) {
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
			$property = new DIProperty( DIProperty::TYPE_CATEGORY );
		}

		if ( $this->useCategoryHierarchy && ( $namespace === NS_CATEGORY ) ) {
			$property = new DIProperty( DIProperty::TYPE_SUBCATEGORY );
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

		$cat = new DIWikiPage( $catname, NS_CATEGORY );

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

	private function isHiddenCategory( $catName ) {

		if ( $this->hiddenCategories === null ) {

			$wikipage = ApplicationFactory::getInstance()->newPageCreator()->createPage(
				$this->getSemanticData()->getSubject()->getTitle()
			);

			$this->hiddenCategories = $wikipage->getHiddenCategories();
		}

		foreach ( $this->hiddenCategories as $hiddenCategory ) {

			if ( $hiddenCategory->getText() === $catName ) {
				return true;
			};

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
