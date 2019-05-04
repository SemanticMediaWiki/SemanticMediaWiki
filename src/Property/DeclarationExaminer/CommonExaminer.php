<?php

namespace SMW\Property\DeclarationExaminer;

use SMW\DIProperty;
use SMW\Store;
use SMW\SemanticData;
use SMW\Message;
use SMW\DataValueFactory;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CommonExaminer extends DeclarationExaminer {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var array
	 */
	private $propertyReservedNameList = [];

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param SemanticData $semanticData
	 */
	public function __construct( Store $store, SemanticData $semanticData = null ) {
		$this->store = $store;
		$this->semanticData = $semanticData;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $propertyReservedNameList
	 */
	public function setPropertyReservedNameList( array $propertyReservedNameList ) {

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
	public function check( DIProperty $property ) {
		$this->validate( $property );
	}

	/**
	 * @see ExaminerDecorator::validate
	 *
	 * {@inheritDoc}
	 */
	protected function validate( DIProperty $property ) {

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$property
		);

		$propertyName = $dataValue->getFormattedLabel();

		$this->checkReservedName( $propertyName );
		$this->checkUniqueness( $property );
		$this->checkErrorMessages( $property );
		$this->checkTypeDeclaration();
		$this->checkCommonMessage( $propertyName );
	}

	private function checkReservedName( $propertyName ) {

		if ( !isset( $this->propertyReservedNameList[$propertyName] ) ) {
			return;
		}

		$this->messages[] = [ 'error', 'smw-property-name-reserved', $propertyName ];
	}

	private function checkUniqueness( $property ) {

		if ( $this->store->getObjectIds()->isUnique( $property ) ) {
			return;
		}

		$this->messages[] = [ 'error', 'smw-property-label-uniqueness', $property->getLabel() ];
	}

	private function checkErrorMessages() {

		$property = new DIProperty( '_ERRC' );

		if ( $this->semanticData === null || !$this->semanticData->hasProperty( $property ) ) {
			return;
		}

		$pv = $this->semanticData->getPropertyValues( new DIProperty( '_ERRC' ) );
		$messages = [];

		foreach ( $pv as $v ) {
			$subSemanticData = $this->semanticData->findSubSemanticData(
				$v->getSubobjectName()
			);

			foreach ( $subSemanticData->getPropertyValues( new DIProperty( '_ERRT' ) ) as $error ) {
				$messages[] = Message::decode( $error->getString(), Message::PARSE, Message::USER_LANGUAGE );
			}
		}

		$this->messages[] = [ 'error', '_msgkey' => 'smw-property-req-error-list', '_list' => $messages ];
	}

	private function checkTypeDeclaration() {

		$property = new DIProperty( '_TYPE' );

		if ( $this->semanticData === null ) {
			return;
		}

		$props = $this->semanticData->getPropertyValues( $property );

		if ( count( $props ) < 2 ) {
			return;
		}

		$this->messages[] = [ 'warning', 'smw-property-req-violation-type' ];
	}

	private function checkCommonMessage( $propertyName ) {

		if ( Message::exists( 'smw-property-introductory-message' ) ) {
			$this->messages[] = [ 'info', 'smw-property-introductory-message', $propertyName ];
		}

		$label = mb_strtolower( str_replace( ' ', '-', $propertyName ) );

		if ( Message::exists( "smw-property-message-$label" ) ) {
			$this->messages[] = [ 'plain', "smw-property-message-$label", $propertyName ];
		}
	}

}
