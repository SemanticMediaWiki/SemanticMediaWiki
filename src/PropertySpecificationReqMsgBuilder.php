<?php

namespace SMW;

use SMW\Store;
use SMW\SQLStore\SQLStore;
use SMW\DIProperty;
use SMW\Message;
use SMW\DataValueFactory;
use SMW\PropertyRegistry;
use SMW\PropertySpecificationReqExaminer;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use Html;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertySpecificationReqMsgBuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PropertySpecificationReqExaminer
	 */
	private $propertySpecificationReqExaminer;

	/**
	 * @var array
	 */
	private $propertyReservedNameList = [];

	/**
	 * @var string
	 */
	private $message = '';

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
	 * @param SemanticData|null $semanticData
	 */
	public function setSemanticData( SemanticData $semanticData = null ) {
		$this->propertySpecificationReqExaminer->setSemanticData( $semanticData );
	}

	/**
	 * @since 3.0
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
	 * @since 3.0
	 *
	 * @param boolean
	 */
	public function reqLock() {
		return $this->propertySpecificationReqExaminer->reqLock();
	}

	/**
	 * @since 3.0
	 *
	 * @param string
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 */
	public function check( DIProperty $property ) {

		$subject = $property->getCanonicalDiWikiPage();
		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$property
		);

		$propertyName = $dataValue->getFormattedLabel();

		$this->message = $this->checkUniqueness( $property, $propertyName );

		if ( isset( $this->propertyReservedNameList[$propertyName] ) ) {
			$this->message .= $this->createMessage(
				[
					'error',
					'smw-property-name-reserved',
					$propertyName
				]
			);
		}

		$this->message .= $this->createMessage(
			$this->propertySpecificationReqExaminer->check( $property )
		);

		if ( $this->propertySpecificationReqExaminer->reqLock() === false && ChangePropagationDispatchJob::hasPendingJobs( $subject ) ) {
			$this->message .= $this->createMessage(
				array(
					'warning',
					'smw-property-req-violation-change-propagation-pending',
					ChangePropagationDispatchJob::getPendingJobsCount( $subject )
				)
			);
		}

		if ( $property->isUserDefined() && wfMessage( 'smw-property-introductory-message-user' )->exists() ) {
			$this->message .= $this->createIntroductoryMessage( 'smw-property-introductory-message-user', $propertyName );
		}

		if ( !$property->isUserDefined() && wfMessage( 'smw-property-introductory-message-special' )->exists() ) {
			$this->message .= $this->createIntroductoryMessage( 'smw-property-introductory-message-special', $propertyName );
		}

		if ( wfMessage( 'smw-property-introductory-message' )->exists() ) {
			$this->message .= $this->createIntroductoryMessage( 'smw-property-introductory-message', $propertyName );
		}

		if ( $property->isUserDefined() && $this->store->getPropertyTableInfoFetcher()->isFixedTableProperty( $property ) ) {
			$this->message .= $this->createFixedTableMessage( $propertyName );
		}

		if ( !$property->isUserDefined() ) {
			$this->message .= $this->createPredefinedPropertyMessage( $property, $propertyName );
		}
	}

	private function createMessage( $messsage ) {

		if ( !is_array( $messsage ) ) {
			return '';
		}

		$type = array_shift( $messsage );

		return Html::rawElement(
			'div',
			array(
				'id' => $messsage[0],
				'class' => 'plainlinks ' . ( $type !== '' ? 'smw-callout smw-callout-'. $type : '' )
			),
			Message::get( $messsage, Message::PARSE, Message::USER_LANGUAGE )
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
	 * 'smw-property-predefined' + <internal property key> => '_asksi' ) but
	 * because translatewiki.net doesn't handle `_` well, convert `_` to `-`
	 * resulting in 'smw-property-predefined-asksi' as translatable key
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
			$messageKey = 'smw-property-predefined' . str_replace( '_', '-', strtolower( $key ) );
			$messageKeyLong = 'smw-property-predefined-long' . str_replace( '_', '-', strtolower( $key ) );
		}

		if ( wfMessage( $messageKey )->exists() ) {
			$message .= wfMessage( $messageKey, $propertyName )->parse();
		} else {
			$message .= wfMessage( 'smw-property-predefined-default', $propertyName )->parse();
		}

		if ( wfMessage( $messageKeyLong )->exists() ) {
			$message .= ' ' . wfMessage( $messageKeyLong )->parse();
		}

		$message .= ' ' . wfMessage( 'smw-property-predefined-common' )->parse();

		return Html::rawElement(
			'div',
			array(
				'id' => 'smw-property-content-predefined-message',
				'class' => 'smw-property-predefined-intro plainlinks'
			),
			$message
		);
	}

	private function checkUniqueness( DIProperty $property, $propertyName ) {

		if ( $this->store->getObjectIds()->isUnique( $property ) ) {
			return '';
		}

		return Html::rawElement(
			'div',
			array(
				'id' => 'smw-property-uniqueness',
				'class' => 'smw-callout smw-callout-error plainlinks'
			),
			Message::get( [ 'smw-property-label-uniqueness', $propertyName ], Message::PARSE, Message::USER_LANGUAGE )
		);
	}

}
