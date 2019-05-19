<?php

namespace SMW;

use SMWContainerSemanticData as ContainerSemanticData;
use SMWDataValue as DataValue;
use SMWDIBlob as DIBlob;
use SMWDIContainer as DIContainer;

/**
 * The handler encodes errors into a representation that can be retrieved from
 * the back-end and turn it into a string representation at a convenient time.
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
	 * @since 3.0
	 *
	 * @param string $message
	 *
	 * @return DIProperty|null
	 */
	public static function grepPropertyFromRestrictionErrorMsg( $message ) {
		return PropertyRestrictionExaminer::grepPropertyFromRestrictionErrorMsg( $message );
	}

	/**
	 * Turns an encoded array of messages or text elements into a compacted array
	 * with msg keys and arguments.
	 *
	 * @since 2.5
	 *
	 * @param array $messages
	 * @param integer|null $type
	 * @param integer|null $language
	 *
	 * @return array
	 */
	public static function normalizeAndDecodeMessages( array $messages, $type = null, $language = null ) {

		$normalizedMessages = [];

		if ( $type === null ) {
			$type = Message::TEXT;
		}

		if ( $language === null ) {
			$language = Message::USER_LANGUAGE;
		}

		foreach ( $messages as $message ) {

			if ( is_array( $message ) ) {
				foreach ( self::normalizeAndDecodeMessages( $message ) as $msg ) {
					if ( is_string( $msg ) ) {
						$normalizedMessages[md5($msg)] = $msg;
					} else {
						$normalizedMessages[] = $msg;
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
				$normalizedMessages[md5($message)] = $message;
			} else {
				$normalizedMessages[] = $message;
			}
		}

		return array_values( $normalizedMessages );
	}

	/**
	 * @since 2.5
	 *
	 * @param array $messages
	 * @param integer|null $type
	 * @param integer|null $language
	 *
	 * @return string
	 */
	public static function getMessagesAsString( array $messages, $type = null, $language = null ) {

		$normalizedMessages = self::normalizeAndDecodeMessages( $messages, $type, $language );
		$msg = [];

		foreach ( $normalizedMessages as $message ) {

			if ( !is_string( $message ) ) {
				continue;
			}

			$msg[] = $message;
		}

		return implode( ',', $msg );
	}

	/**
	 * @since 2.5
	 *
	 * @param SemanticData $semanticData
	 * @param DIContainer|null $container
	 */
	public function addToSemanticData( SemanticData $semanticData, DIContainer $container = null ) {

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
	 * @return DIContainer
	 */
	public function newErrorContainerFromMsg( $error, DIProperty $property = null ) {

		if ( $property !== null && $property->isInverse() ) {
			$property = new DIProperty( $property->getKey() );
		}

		if ( $error instanceof ProcessingError ) {
			$type = $error->getType();
			$error = $error->encode();
		} else {
			$error = Message::encode( $error );
			$type = '';
		}

		$hash = $error;

		if ( $property !== null ) {
			$hash .= $property->getKey();
		}

		$containerSemanticData = $this->newContainerSemanticData(
			$hash
		);

		$this->publishError( $containerSemanticData, $property, $error, $type );

		return new DIContainer( $containerSemanticData );
	}

	/**
	 * @since 2.5
	 *
	 * @param DataValue $dataValue
	 *
	 * @return DIContainer|null
	 */
	public function newErrorContainerFromDataValue( DataValue $dataValue ) {

		if ( $dataValue->getErrors() === [] ) {
			return null;
		}

		$property = $dataValue->getProperty();
		$contextPage = $dataValue->getContextPage();

		if ( $property === null || ( $contextPage !== null && $contextPage->getSubobjectName() !== '' ) ) {
			$hash = $dataValue->getDataItem()->getHash();
		} else {
			$hash = $property->getKey();
		}

		$errorsByType = $this->flip( $dataValue->getErrorsByType() );

		$containerSemanticData = $this->newContainerSemanticData(
			$hash
		);

		foreach ( $dataValue->getErrors() as $hash => $error ) {
			$type = '';

			if ( isset( $errorsByType[$hash] ) ) {
				$type = $errorsByType[$hash];
			}

			$this->publishError( $containerSemanticData, $property, Message::encode( $error ), $type );
		}

		return new DIContainer( $containerSemanticData );
	}

	private function publishError( $containerSemanticData, $property, $error, $type ) {

		// `_INST` is not a real (visible) property to create a reference from
		// and link to
		if ( $property !== null && $property->getKey() !== '_INST' ) {
			$containerSemanticData->addPropertyObjectValue(
				new DIProperty( '_ERRP' ),
				new DIWikiPage( $property->getKey(), SMW_NS_PROPERTY )
			);
		}

		$containerSemanticData->addPropertyObjectValue(
			new DIProperty( '_ERRT' ),
			new DIBlob( $error )
		);

		if ( $type !== '' ) {
			$containerSemanticData->addPropertyObjectValue(
				new DIProperty( '_ERR_TYPE' ),
				new DIBlob( $type )
			);
		}
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

	/**
	 * Flip [ '_type_1' => [ 'a', 'b'], '_type_2' => 'c', 'd' ] ]
	 * to  [ 'a' => '_type_1', 'b' => '_type_1', 'c' => '_type_2', 'd' => '_type_2' ]
	 */
	private function flip( $array ) {
		$flipped = [];

		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $v ) {
					$flipped[$v] = $key;
				}
			} else {
				$flipped[$value] = $key;
			}
		}

		return $flipped;
	}

}
