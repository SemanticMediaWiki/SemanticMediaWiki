<?php

namespace SMW\MediaWiki;

use RuntimeException;
use SMW\ApplicationFactory;
use SMW\Store;
use Title;
use SMW\Property\DeclarationExaminerFactory;
use SMW\MediaWiki\Page\PropertyPage;
use SMW\MediaWiki\Page\ConceptPage;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PageFactory {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 *
	 * @return PageView
	 * @throws RuntimeException
	 */
	public function newPageFromTitle( Title $title ) {

		if ( $title->getNamespace() === SMW_NS_PROPERTY ) {
			return $this->newPropertyPage( $title );
		} elseif ( $title->getNamespace() === SMW_NS_CONCEPT ) {
			return $this->newConceptPage( $title );
		}

		throw new RuntimeException( 'Not supported page instance for namespace ' . $title->getNamespace() );
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 *
	 * @return PropertyPage
	 */
	public function newPropertyPage( Title $title ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$propertyPage = new PropertyPage(
			$title,
			$this->store,
			new DeclarationExaminerFactory()
		);

		$propertyPage->setOption(
			'smwgSemanticsEnabled',
			$settings->get( 'smwgSemanticsEnabled' )
		);

		$propertyPage->setOption(
			'pagingLimit',
			$settings->dotGet( 'smwgPagingLimit.property' )
		);

		$propertyPage->setOption(
			'smwgPropertyListLimit',
			$settings->get( 'smwgPropertyListLimit' )
		);

		$propertyPage->setOption(
			'smwgMaxPropertyValues',
			$settings->get( 'smwgMaxPropertyValues' )
		);

		return $propertyPage;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 *
	 * @return ConceptPage
	 */
	public function newConceptPage( Title $title ) {

		$conceptPage = new ConceptPage( $title );
		$settings = ApplicationFactory::getInstance()->getSettings();

		$conceptPage->setOption(
			'smwgSemanticsEnabled',
			$settings->get( 'smwgSemanticsEnabled' )
		);

		$conceptPage->setOption(
			'pagingLimit',
			$settings->dotGet( 'smwgPagingLimit.concept' )
		);

		return $conceptPage;
	}

}
