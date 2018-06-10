<?php

namespace SMW\Page;

use RuntimeException;
use SMW\ApplicationFactory;
use SMW\PropertySpecificationReqExaminer;
use SMW\PropertySpecificationReqMsgBuilder;
use SMW\Rule\RuleFactory;
use SMW\Store;
use Title;

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
		} elseif ( $title->getNamespace() === SMW_NS_RULE ) {
			return $this->newRulePage( $title );
		}

		throw new RuntimeException( 'No supported ContentPage instance for namespace ' . $title->getNamespace() );
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

		$propertySpecificationReqMsgBuilder->setPropertyReservedNameList(
			$settings->get( 'smwgPropertyReservedNameList' )
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
			'smwgConceptPagingLimit',
			$settings->get( 'smwgConceptPagingLimit' )
		);

		return $conceptPage;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 *
	 * @return RulePage
	 */
	public function newRulePage( Title $title ) {

		$rulePage = new RulePage( $title, new RuleFactory() );
		$settings = ApplicationFactory::getInstance()->getSettings();

		$rulePage->setOption(
			'smwgSemanticsEnabled',
			$settings->get( 'smwgSemanticsEnabled' )
		);

		return $rulePage;
	}

}
