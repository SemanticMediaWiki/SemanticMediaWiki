<?php

namespace SMW\Property\DeclarationExaminer;

use SMW\DataItems\Property;
use SMW\DataModel\SemanticData;
use SMW\DataValueFactory;
use SMW\Localizer\Message;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class CommonExaminer extends DeclarationExaminer {

	/**
	 * @var array
	 */
	private $namespacesWithSemanticLinks = [];

	/**
	 * @var array
	 */
	private $propertyReservedNameList = [];

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly Store $store,
		private readonly ?SemanticData $semanticData = null,
	) {
	}

	/**
	 * @since 3.2
	 *
	 * @param array $namespacesWithSemanticLinks
	 */
	public function setNamespacesWithSemanticLinks( array $namespacesWithSemanticLinks ): void {
		$this->namespacesWithSemanticLinks = $namespacesWithSemanticLinks;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $propertyReservedNameList
	 */
	public function setPropertyReservedNameList( array $propertyReservedNameList ): void {
		foreach ( $propertyReservedNameList as $name ) {

			if ( strpos( $name, 'smw-property-reserved' ) !== false ) {
				$name = Message::get( $name, Message::TEXT, Message::CONTENT_LANGUAGE );
			}

			$this->propertyReservedNameList[$name] = true;
		}
	}

	/**
	 * @see ExaminerDecorator::check
	 *
	 * {@inheritDoc}
	 */
	public function check( Property $property ): void {
		$this->validate( $property );
	}

	/**
	 * @see ExaminerDecorator::validate
	 *
	 * {@inheritDoc}
	 */
	protected function validate( Property $property ) {
		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$property
		);

		$propertyName = $dataValue->getFormattedLabel();

		$this->checkNamespace();
		$this->checkReservedName( $propertyName );
		$this->checkUniqueness( $property );
		$this->checkErrorMessages();
		$this->checkTypeDeclaration();
		$this->checkCommonMessage( $propertyName );
	}

	private function checkNamespace(): void {
		if (
			isset( $this->namespacesWithSemanticLinks[SMW_NS_PROPERTY] ) &&
			$this->namespacesWithSemanticLinks[SMW_NS_PROPERTY] ) {
			return;
		}

		$this->messages[] = [ 'error', 'smw-property-namespace-disabled' ];
	}

	private function checkReservedName( $propertyName ): void {
		if ( !isset( $this->propertyReservedNameList[$propertyName] ) ) {
			return;
		}

		$this->messages[] = [ 'error', 'smw-property-name-reserved', $propertyName ];
	}

	private function checkUniqueness( $property ): void {
		if ( $this->store->getObjectIds()->isUnique( $property ) ) {
			return;
		}

		$this->messages[] = [ 'error', 'smw-property-label-uniqueness', $property->getLabel() ];
	}

	private function checkErrorMessages(): void {
		$property = new Property( '_ERRC' );

		if ( $this->semanticData === null || !$this->semanticData->hasProperty( $property ) ) {
			return;
		}

		$pv = $this->semanticData->getPropertyValues( new Property( '_ERRC' ) );
		$messages = [];

		foreach ( $pv as $v ) {
			$subSemanticData = $this->semanticData->findSubSemanticData(
				$v->getSubobjectName()
			);

			foreach ( $subSemanticData->getPropertyValues( new Property( '_ERRT' ) ) as $error ) {
				$messages[] = Message::decode( $error->getString(), Message::PARSE, Message::USER_LANGUAGE );
			}
		}

		$this->messages[] = [ 'error', '_msgkey' => 'smw-property-req-error-list', '_list' => $messages ];
	}

	private function checkTypeDeclaration(): void {
		$property = new Property( '_TYPE' );

		if ( $this->semanticData === null ) {
			return;
		}

		$props = $this->semanticData->getPropertyValues( $property );

		if ( count( $props ) < 2 ) {
			return;
		}

		$this->messages[] = [ 'warning', 'smw-property-req-violation-type' ];
	}

	private function checkCommonMessage( $propertyName ): void {
		if ( Message::exists( 'smw-property-introductory-message' ) ) {
			$this->messages[] = [ 'info', 'smw-property-introductory-message', $propertyName ];
		}

		$label = mb_strtolower( str_replace( ' ', '-', $propertyName ) );

		if ( Message::exists( "smw-property-message-$label" ) ) {
			$this->messages[] = [ 'plain', "smw-property-message-$label", $propertyName ];
		}
	}

}
