<?php

namespace SMW\Tests\Integration\ByJsonScript;

use SMW\Tests\ByJsonTestCaseProvider;
use SMW\Tests\JsonTestCaseFileHandler;
use SMW\Tests\Utils\UtilityFactory;
use SMW\DIWikiPage;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ParserTestCaseProcessor extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SemanticDataValidator
	 */
	private $semanticDataValidator;

	/**
	 * @var StringValidator
	 */
	private $stringValidator;

	/**
	 * @var boolean
	 */
	private $debug = false;

	/**
	 * @param Store
	 * @param SemanticDataValidator
	 * @param StringValidator
	 */
	public function __construct( $store, $semanticDataValidator, $stringValidator ) {
		$this->store = $store;
		$this->semanticDataValidator = $semanticDataValidator;
		$this->stringValidator = $stringValidator;
	}

	/**
	 * @since  2.2
	 */
	public function setDebugMode( $debugMode ) {
		$this->debug = $debugMode;
	}

	public function process( array $case ) {

		if ( !isset( $case['subject'] ) ) {
			return;
		}

		$this->assertSemanticDataForCase( $case );
		$this->assertParserOutputForCase( $case );
	}

	private function assertSemanticDataForCase( $case ) {

		if ( !isset( $case['store'] ) || !isset( $case['store']['semantic-data'] ) ) {
			return;
		}

		$subject = DIWikiPage::newFromText(
			$case['subject'],
			isset( $case['namespace'] ) ? constant( $case['namespace'] ) : NS_MAIN
		);

		// Allows for data to be re-read from the DB instead of being fetched
		// from the store-id-cache
		if ( isset( $case['store']['clear-cache'] ) && $case['store']['clear-cache'] ) {
			$this->store->clear();
		}

		$semanticData = $this->store->getSemanticData( $subject );

		if ( $this->debug ) {
			print_r( $semanticData );
		}

		if ( isset( $case['errors'] ) && $case['errors'] !== array() ) {
			$this->assertNotEmpty(
				$semanticData->getErrors()
			);
		}

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$case['store']['semantic-data'],
			$semanticData,
			$case['about']
		);

		$this->assertInProperties(
			$subject,
			$case['store']['semantic-data'],
			$case['about']
		);
	}

	private function assertParserOutputForCase( $case ) {

		if ( !isset( $case['expected-output'] ) || !isset( $case['expected-output']['to-contain'] ) ) {
			return;
		}

		$subject = DIWikiPage::newFromText(
			$case['subject'],
			isset( $case['namespace'] ) ? constant( $case['namespace'] ) : NS_MAIN
		);

		$parserOutput = UtilityFactory::getInstance()->newPageReader()->getEditInfo( $subject->getTitle() )->output;

		$this->stringValidator->assertThatStringContains(
			$case['expected-output']['to-contain'],
			$parserOutput->getText(),
			$case['about']
		);
	}

	private function assertInProperties( DIWikiPage $subject, array $semanticdata, $about ) {

		if ( !isset( $semanticdata['inproperty-keys'] ) ) {
			return;
		}

		$inProperties = $this->store->getInProperties( $subject );

		$this->assertCount(
			count( $semanticdata['inproperty-keys'] ),
			$inProperties,
			'Failed asserting count for "inproperty-keys" in ' . $about . ' ' . implode( ',', $inProperties )
		);

		$inpropertyValues = array();

		foreach ( $inProperties as $property ) {

			$this->assertContains(
				$property->getKey(),
				$semanticdata['inproperty-keys'],
				'Failed asserting key for "inproperty-keys" in ' . $about
			);

			if ( !isset( $semanticdata['inproperty-values'] ) ) {
				continue;
			}

			$values = $this->store->getPropertySubjects( $property, $subject );

			foreach ( $values as $value ) {
				$inpropertyValues[] = $value->getSerialization();
			}
		}

		foreach ( $inpropertyValues as $value ) {
			$this->assertContains(
				$value,
				$semanticdata['inproperty-values'],
				'Failed asserting values for "inproperty-values" in ' . $about
			);
		}
	}

}
