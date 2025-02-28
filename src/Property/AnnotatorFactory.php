<?php

namespace SMW\Property;

use SMW\MediaWiki\RedirectTargetFinder;
use SMW\PageInfo;
use SMW\Property\Annotators\AttachmentLinkPropertyAnnotator;
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
use SMW\Schema\Schema;
use SMW\SemanticData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use Title;

/**
 * @license GPL-2.0-or-later
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
	 * @param Annotator $propertyAnnotator
	 * @param RedirectTargetFinder $redirectTargetFinder
	 *
	 * @return RedirectPropertyAnnotator
	 */
	public function newRedirectPropertyAnnotator( Annotator $propertyAnnotator, RedirectTargetFinder $redirectTargetFinder ) {
		return new RedirectPropertyAnnotator(
			$propertyAnnotator,
			$redirectTargetFinder
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param Annotator $propertyAnnotator
	 * @param Schema|null $schema
	 *
	 * @return SchemaPropertyAnnotator
	 */
	public function newSchemaPropertyAnnotator( Annotator $propertyAnnotator, ?Schema $schema = null ) {
		$schemaPropertyAnnotator = new SchemaPropertyAnnotator(
			$propertyAnnotator,
			$schema
		);

		return $schemaPropertyAnnotator;
	}

	/**
	 * @since 3.1
	 *
	 * @param Annotator $propertyAnnotator
	 * @param array $images
	 *
	 * @return AttachmentLinkPropertyAnnotator
	 */
	public function newAttachmentLinkPropertyAnnotator( Annotator $propertyAnnotator, array $images = [] ) {
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
	 * @param Annotator $propertyAnnotator
	 * @param PageInfo $pageInfo
	 *
	 * @return PredefinedPropertyAnnotator
	 */
	public function newPredefinedPropertyAnnotator( Annotator $propertyAnnotator, PageInfo $pageInfo ) {
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
	 * @param Annotator $propertyAnnotator
	 * @param Title $title
	 *
	 * @return EditProtectedPropertyAnnotator
	 */
	public function newEditProtectedPropertyAnnotator( Annotator $propertyAnnotator, Title $title ) {
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
	 * @param Annotator $propertyAnnotator
	 * @param string $sortkey
	 *
	 * @return SortKeyPropertyAnnotator
	 */
	public function newSortKeyPropertyAnnotator( Annotator $propertyAnnotator, $sortkey ) {
		return new SortKeyPropertyAnnotator(
			$propertyAnnotator,
			$sortkey
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param Annotator $propertyAnnotator
	 * @param array|null $translation
	 *
	 * @return TranslationPropertyAnnotator
	 */
	public function newTranslationPropertyAnnotator( Annotator $propertyAnnotator, $translation ) {
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
	 * @param Annotator $propertyAnnotator
	 * @param string|false $displayTitle
	 * @param string $defaultSort
	 *
	 * @return DisplayTitlePropertyAnnotator
	 */
	public function newDisplayTitlePropertyAnnotator( Annotator $propertyAnnotator, $displayTitle, $defaultSort ) {
		$displayTitlePropertyAnnotator = new DisplayTitlePropertyAnnotator(
			$propertyAnnotator,
			$displayTitle,
			$defaultSort
		);

		$smwgDVFeatures = ( ApplicationFactory::getInstance()->getSettings()->get( 'smwgDVFeatures' ) & SMW_DV_WPV_DTITLE );
		$displayTitlePropertyAnnotator->canCreateAnnotation( $smwgDVFeatures != 0 );

		return $displayTitlePropertyAnnotator;
	}

	/**
	 * @since 2.0
	 *
	 * @param Annotator $propertyAnnotator
	 * @param array $categories
	 *
	 * @return CategoryPropertyAnnotator
	 */
	public function newCategoryPropertyAnnotator( Annotator $propertyAnnotator, array $categories ) {
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
	 * @param Annotator $propertyAnnotator
	 *
	 * @return MandatoryTypePropertyAnnotator
	 */
	public function newMandatoryTypePropertyAnnotator( Annotator $propertyAnnotator ) {
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
