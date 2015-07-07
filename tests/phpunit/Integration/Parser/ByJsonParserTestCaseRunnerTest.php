<?php

namespace SMW\Tests\Integration\Parser;

use SMW\Tests\ByJsonTestCaseProvider;
use SMW\Tests\JsonTestCaseFileHandler;

use SMW\Tests\Utils\UtilityFactory;
use SMW\DIWikiPage;

/**
 * @group semantic-mediawiki-integration
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ByJsonParserTestCaseRunnerTest extends ByJsonTestCaseProvider {

	private $semanticDataValidator;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	/**
	 * @see ByJsonTestCaseProvider::getJsonTestCaseVersion
	 */
	protected function getJsonTestCaseVersion() {
		return '0.1';
	}

	/**
	 * @see ByJsonTestCaseProvider::getTestCaseLocation
	 */
	protected function getTestCaseLocation() {
		return __DIR__;
	}

	/**
	 * @see ByJsonTestCaseProvider::runTestCaseFile
	 *
	 * @param JsonTestCaseFileHandler $jsonTestCaseFileHandler
	 */
	protected function runTestCaseFile( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$this->checkEnvironmentToSkipCurrentTest( $jsonTestCaseFileHandler );

		$permittedSettings = array(
			'smwgNamespacesWithSemanticLinks',
			'smwgPageSpecialProperties',
			'wgLanguageCode',
			'wgContLang',
			'wgLang',
			'wgCapitalLinks',
			'smwgEnabledResultFormatsWithRecursiveAnnotationSupport'
		);

		foreach ( $permittedSettings as $key ) {
			$this->changeGlobalSettingTo(
				$key,
				$jsonTestCaseFileHandler->getSettingsFor( $key )
			);
		}

		$this->createPagesFor(
			$jsonTestCaseFileHandler->getListOfProperties(),
			SMW_NS_PROPERTY
		);

		$this->createPagesFor(
			$jsonTestCaseFileHandler->getListOfSubjects(),
			NS_MAIN
		);

		foreach ( $jsonTestCaseFileHandler->findTestCasesFor( 'parser-testcases' ) as $case ) {

			if ( !isset( $case['subject'] ) ) {
				break;
			}

			$this->assertSemanticDataForCase( $case, $jsonTestCaseFileHandler->getDebugMode() );
			$this->assertParserOutputForCase( $case );
		}
	}

	private function assertSemanticDataForCase( $case, $debug ) {

		if ( !isset( $case['store'] ) || !isset( $case['store']['semantic-data'] ) ) {
			return;
		}

		$subject = DIWikiPage::newFromText(
			$case['subject'],
			isset( $case['namespace'] ) ? constant( $case['namespace'] ) : NS_MAIN
		);

		$semanticData = $this->getStore()->getSemanticData( $subject );

		if ( $debug ) {
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

		$inProperties = $this->getStore()->getInProperties( $subject );

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

			$values = $this->getStore()->getPropertySubjects( $property, $subject );

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
