<?php

namespace SMW;

use SMWContainerSemanticData as ContainerSemanticData;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWDIContainer as DIContainer;
use SMWDIBlob as DIBlob;

/**
 * The handler reformats an error(s) into a representation that can be retrieved from
 * the back-end and turn it into a string at a convenient time, allowing it to
 * display a language dep. message without requiring a context.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ProcessingErrorMsgHandler {

	/**
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * @since 2.5
	 *
	 * @param DIWikiPage $subject
	 */
	public function __construct( DIWikiPage $subject ) {
		$this->subject = $subject;
	}

	/**
	 * Turns encoded array of messages or text elements into a flattened array
	 * with msg keys and arguments being transformed into a string representation.
	 *
	 * @since 2.5
	 *
	 * @param array $messages
	 * @param integer|null $type
	 * @param integer|null $language
	 *
	 * @return array
	 */
	public static function normalizeMessages( array $messages, $type = null, $language = null ) {

		$normalizeMessages = array();

		if ( $type === null ) {
			$type = Message::TEXT;
		}

		if ( $language === null ) {
			$language = Message::USER_LANGUAGE;
		}

		foreach ( $messages as $message ) {

			if ( is_array( $message ) ) {
				foreach ( self::normalizeMessages( $message ) as $msg ) {
					if ( is_string( $msg ) ) {
						$normalizeMessages[md5($msg)] = $msg;
					} else {
						$normalizeMessages[] = $msg;
					}
				}
				continue;
			}

			$exists = false;

			if ( is_string( $message ) && ( $decodedMessage = Message::decode( $message, $type, $language ) ) !== false ) {
				$message = $decodedMessage;
				$exists = true;
			}

			if ( !$exists && is_string( $message ) && wfMessage( $message )->exists() ) {
				$message = Message::get( $message, $type, $language );
			}

			if ( is_string( $message ) ) {
				$normalizeMessages[md5($message)] = $message;
			} else {
				$normalizeMessages[] = $message;
			}
		}

		return array_values( $normalizeMessages );
	}

	/**
	 * @since 2.5
	 *
	 * @param SemanticData $semanticData
	 * @param DIContainer|null $container
	 */
	public function pushTo( SemanticData $semanticData, DIContainer $container = null ) {

		if ( $container === null ) {
			return;
		}

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_ERRC' ),
			$container
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param array|string $errorMsg
	 * @param DIProperty|null $property
	 *
	 * @return DIContainer|null
	 */
	public function getErrorContainerFromMsg( $error, DIProperty $property = null ) {

		if ( $property !== null && $property->isInverse() ) {
			$property = new DIProperty( $property->getKey() );
		}

		$error = Message::encode( $error );
		$hash = $error;

		if ( $property !== null ) {
			$hash .= $property->getKey();
		}

		$containerSemanticData = $this->newContainerSemanticData( $hash );

		$this->addToContainerSemanticData(
			$containerSemanticData,
			$property,
			$error
		);

		return new DIContainer( $containerSemanticData );
	}

	/**
	 * @since 2.5
	 *
	 * @param DataValue $dataValue
	 *
	 * @return DIContainer|null
	 */
	public function getErrorContainerFromDataValue( DataValue $dataValue ) {

		if ( $dataValue->getErrors() === array() ) {
			return null;
		}

		$property = $dataValue->getProperty();
		$hash = '';

		if ( $property !== null ) {
			$hash = $property->getKey();
		}

		$containerSemanticData = $this->newContainerSemanticData( $hash );

		foreach ( $dataValue->getErrors() as $error ) {
			$this->addToContainerSemanticData( $containerSemanticData, $property, Message::encode( $error ) );
		}

		return new DIContainer( $containerSemanticData );
	}

	private function addToContainerSemanticData( $containerSemanticData, $property, $error ) {

		if ( $property !== null ) {
			$containerSemanticData->addPropertyObjectValue(
				new DIProperty( '_ERRP' ),
				new DIWikiPage( $property->getKey(), SMW_NS_PROPERTY )
			);
		}

		$containerSemanticData->addPropertyObjectValue(
			new DIProperty( '_ERRT' ),
			new DIBlob( $error )
		);
	}

	private function newContainerSemanticData( $hash ) {

		if ( $this->subject === null ) {
			$containerSemanticData = ContainerSemanticData::makeAnonymousContainer();
			$containerSemanticData->skipAnonymousCheck();
		} else {
			$subobjectName = '_ERR' . md5( $hash );

			$subject = new DIWikiPage(
				$this->subject->getDBkey(),
				$this->subject->getNamespace(),
				$this->subject->getInterwiki(),
				$subobjectName
			);

			$containerSemanticData = new ContainerSemanticData( $subject );
		}

		return $containerSemanticData;
	}

}
