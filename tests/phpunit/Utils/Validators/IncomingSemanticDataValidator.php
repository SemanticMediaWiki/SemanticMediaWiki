<?php

namespace SMW\Tests\Utils\Validators;

use SMW\DIWikiPage;
use SMW\Store;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class IncomingSemanticDataValidator extends \PHPUnit_Framework_Assert {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $incomingSemanticData [description]
	 * @param DIWikiPage $subject
	 * @param string $message
	 */
	public function assertThatIncomingDataAreSet( array $incomingSemanticData, DIWikiPage $subject, $message ) {

		if ( !isset( $incomingSemanticData['propertyKeys'] ) ) {
			return;
		}

		$incomingProperties = $this->store->getInProperties( $subject );

		$this->assertCount(
			count( $incomingSemanticData['propertyKeys'] ),
			$incomingProperties,
			"Failed asserting count for incoming `propertyKeys` on " . $message . '"'
		);

		$this->doAssertPropertiesAndValues(
			$incomingSemanticData,
			$incomingProperties,
			$subject,
			$message
		);
	}

	private function doAssertPropertiesAndValues( $incomingSemanticData, $incomingProperties, $subject, $message ) {

		$incomingPropertyValues = [];

		foreach ( $incomingProperties as $property ) {

			$key = $property->getKey();

			$this->assertContains(
				$key,
				$incomingSemanticData['propertyKeys'],
				$this->createMessage( 'propertyKeys', $key, $message, $incomingSemanticData['propertyKeys'] )
			);

			if ( !isset( $incomingSemanticData['propertyValues'] ) ) {
				continue;
			}

			$propertySubjects = $this->store->getPropertySubjects( $property, $subject );

			foreach ( $propertySubjects as $propertySubject ) {
				$incomingPropertyValues[] = $propertySubject->getSerialization();
			}
		}

		foreach ( $incomingPropertyValues as $value ) {
			$this->assertContains(
				$value,
				$incomingSemanticData['propertyValues'],
				$this->createMessage( 'propertyValues', $value, $message, $incomingSemanticData['propertyValues'] )
			);
		}
	}

	private function createMessage( $section, $key, $message, array $data ) {
		return "Failed asserting that '{$key}' for incoming `$section` on \"" . $message . '" is listed in [ ' . implode( ',', $data ) . ' ]';
	}

}
