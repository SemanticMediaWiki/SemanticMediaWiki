<?php

namespace SMW\Property\DeclarationExaminer;

use SMW\DIProperty;
use SMW\Property\DeclarationExaminer as IDeclarationExaminer;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\PropertyRegistry;
use SMW\Message;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PredefinedPropertyExaminer extends DeclarationExaminer {

	/**
	 * @since 3.1
	 *
	 * @param DeclarationExaminer $declarationExaminer
	 */
	public function __construct( IDeclarationExaminer $declarationExaminer ) {
		$this->declarationExaminer = $declarationExaminer;
	}

	/**
	 * @see DeclarationExaminer::validate
	 *
	 * {@inheritDoc}
	 */
	protected function validate( DIProperty $property ) {

		if ( $property->isUserDefined() ) {
			return;
		}

		$this->checkMessages( $property );
		$this->checkTypeDeclaration( $property );
		$this->checkGeoProperty( $property );
	}

	private function checkMessages( $property ) {

		if ( Message::exists( 'smw-property-introductory-message-special' ) ) {
			$this->messages[] = [ 'info', 'smw-property-introductory-message-special', $property->getLabel() ];
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$property
		);

		$propertyName = $dataValue->getFormattedLabel();
		$key = $property->getKey();

		// In order to enable a more detailed description for a specific predefined
		// property a concatenated message key can be used (e.g
		// 'smw-property-predefined' + <internal property key> => '_asksi' ) but
		// because translatewiki.net doesn't handle `_` well, convert `_` to `-`
		// resulting in 'smw-property-predefined-asksi' as translatable key
		if ( ( $messageKey = PropertyRegistry::getInstance()->findPropertyDescriptionMsgKeyById( $key ) ) !== '' ) {
			$messageKeyLong = $messageKey . '-long';
		} else {
			$messageKey = 'smw-property-predefined' . str_replace( '_', '-', strtolower( $key ) );
			$messageKeyLong = 'smw-property-predefined-long' . str_replace( '_', '-', strtolower( $key ) );
		}

		$messages = [];

		if ( Message::exists( $messageKey ) ) {
			$messages[] = [ $messageKey, $propertyName ];
		} else {
			$messages[] = [ 'smw-property-predefined-default', $propertyName ];
		}

		if ( Message::exists( $messageKeyLong ) ) {
			$messages[] = [ $messageKeyLong ];
		}

		$messages[] = [ 'smw-property-predefined-common' ];

		$this->messages[] = [ 'plain', '_merge' => $messages ];
	}

	private function checkTypeDeclaration( $property ) {

		$semanticData = $this->getSemanticData();

		if ( !$semanticData->hasProperty( new DIProperty( '_TYPE' ) ) ) {
			return;
		}

		$typeValues = $semanticData->getPropertyValues(
			new DIProperty( '_TYPE' )
		);

		if ( $typeValues !== [] ) {
			list( $url, $type ) = explode( "#", end( $typeValues )->getSerialization() );
		}

		if ( DataTypeRegistry::getInstance()->isEqualByType( $type, $property->findPropertyTypeID() ) ) {
			return;
		}

		$prop = new DIProperty( $type );

		// A violation occurs when a predefined property contains a `Has type`
		// annotation that is incompatible with the default type.
		$this->messages[] = [
			'error',
			'smw-property-req-violation-predefined-type',
			$property->getCanonicalLabel(),
			$prop->getCanonicalLabel()
		];
	}

	private function checkGeoProperty( $property ) {

		if ( $property->getKey() !== '_geo' || defined( 'SM_VERSION' ) ) {
			return;
		}

		$this->messages[] = [
			'error',
			'smw-property-req-violation-missing-maps-extension',
			$property->getLabel()
		];
	}

}
