<?php

namespace SMW\Page;

use SMW\Page\PropertyPage;
use SMW\Page\ConceptPage;
use SMW\PropertySpecificationReqMsgBuilder;
use SMW\ApplicationFactory;
use SMW\PropertySpecificationReqExaminer;
use SMW\Store;
use SMW\Settings;
use Title;
use RuntimeException;

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

		throw new RuntimeException( 'No supported ContentPage instance for namespace ' . $title->getNamespace() );
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 *
	 * @return PropertyPageView
	 */
	public function newPropertyPage( Title $title ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$propertySpecificationReqExaminer = new PropertySpecificationReqExaminer(
			$this->store,
			$applicationFactory->singleton( 'ProtectionValidator' )
		);

		$propertySpecificationReqExaminer->setChangePropagationProtection(
			$settings->get( 'smwgChangePropagationProtection' )
		);

		$propertySpecificationReqMsgBuilder = new PropertySpecificationReqMsgBuilder(
			$this->store,
			$propertySpecificationReqExaminer
		);

		$propertyPage = new PropertyPage(
			$title,
			$this->store,
			$propertySpecificationReqMsgBuilder
		);

		$propertyPage->setOption(
			'smwgSemanticsEnabled',
			$settings->get( 'smwgSemanticsEnabled' )
		);

		$propertyPage->setOption(
			'smwgPropertyPagingLimit',
			$settings->get( 'smwgPropertyPagingLimit' )
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
	 * @return ConceptPageView
	 */
	public function newConceptPage( Title $title ) {

		$ConceptPage = new ConceptPage( $title );
		$settings = ApplicationFactory::getInstance()->getSettings();

		$ConceptPage->setOption(
			'smwgSemanticsEnabled',
			$settings->get( 'smwgSemanticsEnabled' )
		);

		$ConceptPage->setOption(
			'smwgConceptPagingLimit',
			$settings->get( 'smwgConceptPagingLimit' )
		);

		return $ConceptPage;
	}

}
