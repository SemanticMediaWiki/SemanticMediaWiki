<?php

namespace SMW\Property;

use SMw\MediaWiki\RedirectTargetFinder;
use SMW\Property\Annotators\CategoryPropertyAnnotator;
use SMW\Property\Annotators\DisplayTitlePropertyAnnotator;
use SMW\Property\Annotators\EditProtectedPropertyAnnotator;
use SMW\Property\Annotators\MandatoryTypePropertyAnnotator;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\PredefinedPropertyAnnotator;
use SMW\Property\Annotators\RedirectPropertyAnnotator;
use SMW\Property\Annotators\SchemaPropertyAnnotator;
use SMW\Property\Annotators\SortKeyPropertyAnnotator;
use SMW\Property\Annotators\TranslationPropertyAnnotator;
use SMW\Property\Annotators\AttachmentLinkPropertyAnnotator;
use SMW\Store;
use SMW\Schema\Schema;
use SMW\SemanticData;
use SMW\ApplicationFactory;
use SMW\PropertyAnnotator;
use SMW\PageInfo;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class AnnotatorFactory {

	/**
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return NullPropertyAnnotator
	 */
	public function newNullPropertyAnnotator( SemanticData $semanticData ) {
		return new NullPropertyAnnotator( $semanticData );
	}

	/**
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 * @param RedirectTargetFinder $redirectTargetFinder
	 *
	 * @return RedirectPropertyAnnotator
	 */
	public function newRedirectPropertyAnnotator( PropertyAnnotator $propertyAnnotator, RedirectTargetFinder $redirectTargetFinder ) {
		return new RedirectPropertyAnnotator(
			$propertyAnnotator,
			$redirectTargetFinder
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param Schema $schema
	 *
	 * @return SchemaPropertyAnnotator
	 */
	public function newSchemaPropertyAnnotator( PropertyAnnotator $propertyAnnotator, Schema $schema = null ) {

		$schemaPropertyAnnotator = new SchemaPropertyAnnotator(
			$propertyAnnotator,
			$schema
		);

		return $schemaPropertyAnnotator;
	}

	/**
	 * @since 3.1
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param array $images
	 *
	 * @return AttachmentLinkPropertyAnnotator
	 */
	public function newAttachmentLinkPropertyAnnotator( PropertyAnnotator $propertyAnnotator, array $images = [] ) {

		$attachmentLinkPropertyAnnotator = new AttachmentLinkPropertyAnnotator(
			$propertyAnnotator,
			$images
		);

		$attachmentLinkPropertyAnnotator->setPredefinedPropertyList(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgPageSpecialProperties' )
		);

		return $attachmentLinkPropertyAnnotator;
	}

	/**
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 * @param PageInfo $pageInfo
	 *
	 * @return PredefinedPropertyAnnotator
	 */
	public function newPredefinedPropertyAnnotator( PropertyAnnotator $propertyAnnotator, PageInfo $pageInfo ) {

		$predefinedPropertyAnnotator = new PredefinedPropertyAnnotator(
			$propertyAnnotator,
			$pageInfo
		);

		$predefinedPropertyAnnotator->setPredefinedPropertyList(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgPageSpecialProperties' )
		);

		return $predefinedPropertyAnnotator;
	}

	/**
	 * @since 2.5
	 *
	 * @param SemanticData $semanticData
	 * @param Title $title
	 *
	 * @return EditProtectedPropertyAnnotator
	 */
	public function newEditProtectedPropertyAnnotator( PropertyAnnotator $propertyAnnotator, Title $title ) {

		$editProtectedPropertyAnnotator = new EditProtectedPropertyAnnotator(
			$propertyAnnotator,
			$title
		);

		$editProtectedPropertyAnnotator->setEditProtectionRight(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgEditProtectionRight' )
		);

		return $editProtectedPropertyAnnotator;
	}

	/**
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 * @param string $sortkey
	 *
	 * @return SortKeyPropertyAnnotator
	 */
	public function newSortKeyPropertyAnnotator( PropertyAnnotator $propertyAnnotator, $sortkey ) {
		return new SortKeyPropertyAnnotator(
			$propertyAnnotator,
			$sortkey
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param SemanticData $semanticData
	 * @param arrat|null $translation
	 *
	 * @return TranslationPropertyAnnotator
	 */
	public function newTranslationPropertyAnnotator( PropertyAnnotator $propertyAnnotator, $translation ) {

		$translationPropertyAnnotator = new TranslationPropertyAnnotator(
			$propertyAnnotator,
			$translation
		);

		$translationPropertyAnnotator->setPredefinedPropertyList(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgPageSpecialProperties' )
		);

		return $translationPropertyAnnotator;
	}

	/**
	 * @since 2.4
	 *
	 * @param SemanticData $semanticData
	 * @param string|false $displayTitle
	 * @param string $defaultSort
	 *
	 * @return DisplayTitlePropertyAnnotator
	 */
	public function newDisplayTitlePropertyAnnotator( PropertyAnnotator $propertyAnnotator, $displayTitle, $defaultSort ) {

		$displayTitlePropertyAnnotator = new DisplayTitlePropertyAnnotator(
			$propertyAnnotator,
			$displayTitle,
			$defaultSort
		);

		$displayTitlePropertyAnnotator->canCreateAnnotation(
			( ApplicationFactory::getInstance()->getSettings()->get( 'smwgDVFeatures' ) & SMW_DV_WPV_DTITLE ) != 0
		);

		return $displayTitlePropertyAnnotator;
	}

	/**
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 * @param array $categories
	 *
	 * @return CategoryPropertyAnnotator
	 */
	public function newCategoryPropertyAnnotator( PropertyAnnotator $propertyAnnotator, array $categories ) {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$categoryPropertyAnnotator = new CategoryPropertyAnnotator(
			$propertyAnnotator,
			$categories
		);

		$categoryPropertyAnnotator->showHiddenCategories(
			$settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_HID_CATS )
		);

		$categoryPropertyAnnotator->useCategoryInstance(
			$settings->isFlagSet( 'smwgCategoryFeatures', SMW_CAT_INSTANCE )
		);

		$categoryPropertyAnnotator->useCategoryHierarchy(
			$settings->isFlagSet( 'smwgCategoryFeatures', SMW_CAT_HIERARCHY )
		);

		$categoryPropertyAnnotator->useCategoryRedirect(
			$settings->isFlagSet( 'smwgCategoryFeatures', SMW_CAT_REDIRECT )
		);

		return $categoryPropertyAnnotator;
	}

	/**
	 * @since 2.2
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return MandatoryTypePropertyAnnotator
	 */
	public function newMandatoryTypePropertyAnnotator( PropertyAnnotator $propertyAnnotator ) {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$mandatoryTypePropertyAnnotator = new MandatoryTypePropertyAnnotator(
			$propertyAnnotator
		);

		$mandatoryTypePropertyAnnotator->setSubpropertyParentTypeInheritance(
			$settings->get( 'smwgMandatorySubpropertyParentTypeInheritance' )
		);

		return $mandatoryTypePropertyAnnotator;
	}

}
