<?php

namespace SMW;

use SMW\DataItems\Blob;
use SMW\DataItems\Container;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\DataModel\SemanticData;
use SMW\DataValues\DataValue;
use SMW\Localizer\Message;
use SMW\Property\RestrictionExaminer;

/**
 * The handler encodes errors into a representation that can be retrieved from
 * the back-end and turn it into a string representation at a convenient time.
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ProcessingErrorMsgHandler {

	private WikiPage $subject;

	/**
	 * @since 2.5
	 *
	 * @param WikiPage $subject
	 */
	public function __construct( WikiPage $subject ) {
		$this->subject = $subject;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $message
	 *
	 * @return Property|null
	 */
	public static function grepPropertyFromRestrictionErrorMsg( $message ): ?Property {
		return RestrictionExaminer::grepPropertyFromRestrictionErrorMsg( $message );
	}

	/**
	 * Turns an encoded array of messages or text elements into a compacted array
	 * with msg keys and arguments.
	 *
	 * @since 2.5
	 *
	 * @param array $messages
	 * @param int|null $type
	 * @param int|null $language
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
						$normalizedMessages[md5( $msg )] = $msg;
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
				$normalizedMessages[md5( $message )] = $message;
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
	 * @param int|null $type
	 * @param int|null $language
	 *
	 * @return string
	 */
	public static function getMessagesAsString( array $messages, $type = null, $language = null ): string {
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
	 * @param Container|null $container
	 */
	public function addToSemanticData( SemanticData $semanticData, ?Container $container = null ): void {
		if ( $container === null ) {
			return;
		}

		$semanticData->addPropertyObjectValue(
			new Property( '_ERRC' ),
			$container
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param array|string $error
	 * @param Property|null $property
	 *
	 * @return Container
	 */
	public function newErrorContainerFromMsg( $error, ?Property $property = null ): Container {
		if ( $property !== null && $property->isInverse() ) {
			$property = new Property( $property->getKey() );
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

		return new Container( $containerSemanticData );
	}

	/**
	 * @since 2.5
	 *
	 * @param DataValue $dataValue
	 *
	 * @return Container|null
	 */
	public function newErrorContainerFromDataValue( DataValue $dataValue ): ?Container {
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

		return new Container( $containerSemanticData );
	}

	private function publishError( $containerSemanticData, $property, $error, $type ): void {
		// `_INST` is not a real (visible) property to create a reference from
		// and link to
		if ( $property !== null && $property->getKey() !== '_INST' ) {
			$containerSemanticData->addPropertyObjectValue(
				new Property( '_ERRP' ),
				new WikiPage( $property->getKey(), SMW_NS_PROPERTY )
			);
		}

		$containerSemanticData->addPropertyObjectValue(
			new Property( '_ERRT' ),
			new Blob( $error )
		);

		if ( $type !== '' ) {
			$containerSemanticData->addPropertyObjectValue(
				new Property( '_ERR_TYPE' ),
				new Blob( $type )
			);
		}
	}

	private function newContainerSemanticData( $hash ) {
		if ( $this->subject === null ) {
			$containerSemanticData = ContainerSemanticData::makeAnonymousContainer();
			$containerSemanticData->skipAnonymousCheck();
		} else {
			$subobjectName = '_ERR' . md5( $hash );

			$subject = new WikiPage(
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
	 * @return mixed[]
	 */
	private function flip( $array ): array {
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
