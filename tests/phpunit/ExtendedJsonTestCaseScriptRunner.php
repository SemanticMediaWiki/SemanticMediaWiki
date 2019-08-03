<?php

namespace SMW\Tests;

use SMW\ApplicationFactory;
use SMW\Tests\LightweightJsonTestCaseScriptRunner;
use SMW\Tests\Utils\JSONScript\QueryTestCaseProcessor;
use SMW\Tests\Utils\JSONScript\QueryTestCaseInterpreter;
use SMW\Tests\Utils\JSONScript\RdfTestCaseProcessor;
use SMW\Tests\Utils\JSONScript\SpecialPageTestCaseProcessor;
use SMW\Tests\Utils\JSONScript\ApiTestCaseProcessor;
use SMW\Tests\Utils\JSONScript\JsonTestCaseFileHandler;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
abstract class ExtendedJsonTestCaseScriptRunner extends LightweightJsonTestCaseScriptRunner {

	/**
	 * @var ApiFactory
	 */
	private $apiFactory;

	/**
	 * @see JsonTestCaseScriptRunner::$deletePagesOnTearDown
	 */
	protected $deletePagesOnTearDown = true;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();
		$this->apiFactory = $utilityFactory->newMwApiFactory();
	}

	/**
	 * @see JsonTestCaseScriptRunner::runTestCaseFile
	 *
	 * @param JsonTestCaseFileHandler $jsonTestCaseFileHandler
	 */
	protected function runTestCaseFile( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {
		parent::runTestCaseFile( $jsonTestCaseFileHandler );

		$this->doRunRdfTests( $jsonTestCaseFileHandler );
		$this->doRunQueryStackTests( $jsonTestCaseFileHandler );
		$this->doRunApiTests( $jsonTestCaseFileHandler );
	}

	private function doRunRdfTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'rdf' );

		if ( $testCases === [] ) {
			return;
		}

		$rdfTestCaseProcessor = new RdfTestCaseProcessor(
			$this->getStore(),
			$this->validatorFactory->newStringValidator(),
			$this->runnerFactory
		);

		$rdfTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $testCases as $case ) {
			$rdfTestCaseProcessor->process( $case );
		}
	}

	private function doRunQueryStackTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		// Set query parser late to ensure that expected settings are adjusted
		// (language etc.) because the __construct relies on the context language
		$queryParser = ApplicationFactory::getInstance()->getQueryFactory()->newQueryParser();

		$i = 0;
		$count = 0;

		$this->doRunQueryTests( $jsonTestCaseFileHandler, $queryParser, $i, $count );
		$this->doRunConceptTests( $jsonTestCaseFileHandler, $queryParser, $i, $count );
		$this->doRunFormatTests( $jsonTestCaseFileHandler, $queryParser, $i, $count );

		// Avoid tests being marked as risky when all cases were skipped
		if ( $i == 0 && $count > 0 ) {
			$this->markTestSkipped( 'Skipped all assertions for: ' . $this->getName() );
		}
	}

	private function doRunQueryTests( $jsonTestCaseFileHandler, $queryParser, &$i, &$count ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'query' );
		$count += count( $testCases );

		if ( $testCases === [] ) {
			return;
		}

		$queryTestCaseProcessor = new QueryTestCaseProcessor(
			$this->getStore(),
			$this->validatorFactory->newQueryResultValidator(),
			$this->validatorFactory->newStringValidator(),
			$this->validatorFactory->newNumberValidator()
		);

		$queryTestCaseProcessor->setQueryParser(
			$queryParser
		);

		$queryTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $testCases as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$queryTestCaseProcessor->processQueryCase( new QueryTestCaseInterpreter( $case ) );
			$i++;
		}
	}

	private function doRunConceptTests( $jsonTestCaseFileHandler, $queryParser, &$i, &$count ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'concept' );
		$count += count( $testCases );

		if ( $testCases === [] ) {
			return;
		}

		$queryTestCaseProcessor = new QueryTestCaseProcessor(
			$this->getStore(),
			$this->validatorFactory->newQueryResultValidator(),
			$this->validatorFactory->newStringValidator(),
			$this->validatorFactory->newNumberValidator()
		);

		$queryTestCaseProcessor->setQueryParser(
			$queryParser
		);

		$queryTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $testCases as $case ) {
			$queryTestCaseProcessor->processConceptCase( new QueryTestCaseInterpreter( $case ) );
			$i++;
		}
	}

	private function doRunFormatTests( $jsonTestCaseFileHandler, $queryParser, &$i, &$count ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'format' );
		$count += count( $testCases );

		if ( $testCases === [] ) {
			return;
		}

		$queryTestCaseProcessor = new QueryTestCaseProcessor(
			$this->getStore(),
			$this->validatorFactory->newQueryResultValidator(),
			$this->validatorFactory->newStringValidator(),
			$this->validatorFactory->newNumberValidator()
		);

		$queryTestCaseProcessor->setQueryParser(
			$queryParser
		);

		$queryTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $testCases as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$queryTestCaseProcessor->processFormatCase( new QueryTestCaseInterpreter( $case ) );
			$i++;
		}
	}

	private function doRunApiTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'api' );

		if ( $testCases === [] ) {
			return;
		}

		$apiTestCaseProcessor = new ApiTestCaseProcessor(
			$this->apiFactory,
			$this->validatorFactory->newStringValidator()
		);

		$apiTestCaseProcessor->setTestCaseLocation(
			$this->getTestCaseLocation()
		);

		foreach ( $testCases as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$apiTestCaseProcessor->process( $case );
		}
	}

}
