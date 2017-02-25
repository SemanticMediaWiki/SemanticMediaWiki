<?php

namespace SMW\Content;

use SMW\Store;
use SMW\DIProperty;
use SMW\DataValueFactory;
use SMW\PropertyRegistry;
use SMW\PropertySpecificationReqExaminer;
use Html;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyPageMessageHtmlBuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PropertySpecificationReqExaminer
	 */
	private $propertySpecificationReqExaminer;

	/**
	 * @var boolean
	 */
	private $hasEditProtection = false;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param PropertySpecificationReqExaminer $propertySpecificationReqExaminer
	 */
	public function __construct( Store $store, PropertySpecificationReqExaminer $propertySpecificationReqExaminer ) {
		$this->store = $store;
		$this->propertySpecificationReqExaminer = $propertySpecificationReqExaminer;
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $hasEditLock
	 */
	public function hasEditProtection( $hasEditProtection ) {
		$this->hasEditProtection = $hasEditProtection;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function createMessageBody( DIProperty $property ) {

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$property
		);

		$propertyName = $dataValue->getFormattedLabel();

		$message = $this->createReqViolationMessage(
			$this->propertySpecificationReqExaminer->checkOn( $property )
		);

		if ( $this->hasEditProtection ) {
			$message .= $this->createEditProtectionMessage( $propertyName );
		}

		if ( $property->isUserDefined() && wfMessage( 'smw-property-introductory-message-user' )->exists() ) {
			$message .= $this->createIntroductoryMessage( 'smw-property-introductory-message-user', $propertyName );
		}

		if ( !$property->isUserDefined() && wfMessage( 'smw-property-introductory-message-special' )->exists() ) {
			$message .= $this->createIntroductoryMessage( 'smw-property-introductory-message-special', $propertyName );
		}

		if ( wfMessage( 'smw-property-introductory-message' )->exists() ) {
			$message .= $this->createIntroductoryMessage( 'smw-property-introductory-message', $propertyName );
		}

		if ( $property->isUserDefined() && $this->store->getPropertyTableInfoFetcher()->isFixedTableProperty( $property ) ) {
			return $message . $this->createFixedTableMessage( $propertyName );
		}

		$message .= $this->createPredefinedPropertyMessage( $property, $propertyName );

		return $message;
	}

	private function createReqViolationMessage( $violationMessage ) {

		if ( !is_array( $violationMessage ) ) {
			return '';
		}

		$type = array_shift( $violationMessage );

		return Html::rawElement(
			'div',
			array(
				'id' => 'smw-property-content-violation-message',
				'class' => 'plainlinks ' . ( $type !== '' ? 'smw-callout smw-callout-'. $type : '' )
			),
			call_user_func_array( 'wfMessage', $violationMessage )->parse()
		);
	}

	private function createEditProtectionMessage( $propertyName ) {

		return Html::rawElement(
			'div',
			array(
				'id' => 'smw-property-content-editprotection-message',
				'class' => 'plainlinks smw-callout smw-callout-warning'
			),
			wfMessage( 'smw-edit-protection', $GLOBALS['smwgEditProtectionRight'] )->parse()
		);
	}

	private function createIntroductoryMessage( $msgKey, $propertyName ) {

		$message = wfMessage( $msgKey, $propertyName )->parse();

		if ( $message === '' ) {
			return '';
		}

		return Html::rawElement(
			'div',
			array(
				'id' => 'smw-property-content-introductory-message',
				'class' => 'plainlinks smw-callout smw-callout-info'
			),
			$message
		);
	}

	private function createFixedTableMessage( $propertyName ) {
		return Html::rawElement(
			'div',
			array(
				'id' => 'smw-property-content-fixedtable-message',
				'class' => 'plainlinks smw-callout smw-callout-info'
			),
			wfMessage( 'smw-property-userdefined-fixedtable', $propertyName )->parse()
		);
	}

	/**
	 * Returns an introductory text for a predefined property
	 *
	 * @note In order to enable a more detailed description for a specific
	 * predefined property a concatenated message key can be used (e.g
	 * 'smw-pa-property-predefined' + <internal property key> => '_asksi' )
	 */
	private function createPredefinedPropertyMessage( $property, $propertyName ) {

		$key = $property->getKey();
		$message = '';

		if ( $property->isUserDefined() ) {
			return $message;
		}

		if ( ( $messageKey = PropertyRegistry::getInstance()->findPropertyDescriptionMsgKeyById( $key ) ) !== '' ) {
			$messageKeyLong = $messageKey . '-long';
		} else {
			$messageKey = 'smw-pa-property-predefined' . strtolower( $key );
			$messageKeyLong = 'smw-pa-property-predefined-long' . strtolower( $key );
		}

		if ( wfMessage( $messageKey )->exists() ) {
			$message .= wfMessage( $messageKey, $propertyName )->parse();
		} else {
			$message .= wfMessage( 'smw-pa-property-predefined-default', $propertyName )->parse();
		}

		if ( wfMessage( $messageKeyLong )->exists() ) {
			$message .= ' ' . wfMessage( $messageKeyLong )->parse();
		}

		$message .= ' ' . wfMessage( 'smw-pa-property-predefined-common' )->parse();

		return Html::rawElement(
			'div',
			array(
				'id' => 'smw-property-content-predefined-message',
				'class' => 'smw-property-predefined-intro plainlinks'
			),
			$message
		);
	}

}
