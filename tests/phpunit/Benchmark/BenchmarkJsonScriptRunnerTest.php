<?php

namespace SMW\Tests\Benchmark;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\Utils\JSONScript\JsonTestCaseFileHandler;
use SMW\Tests\JSONScriptTestCaseRunner;

/**
 * @group semantic-mediawiki-benchmark
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class BenchmarkJsonScriptRunnerTest extends JSONScriptTestCaseRunner {

	/**
	 * @var PageImportBenchmarkRunner
	 */
	private $pageImportBenchmarkRunner;

	/**
	 * @var PageContentCopyBenchmarkRunner
	 */
	private $pageContentCopyBenchmarkRunner;

	/**
	 * @var PageEditCopyBenchmarkRunner
	 */
	private $pageEditCopyBenchmarkRunner;

	/**
	 * @var JobQueueBenchmarkRunner
	 */
	private $jobQueueBenchmarkRunner;

	/**
	 * @var MaintenanceBenchmarkRunner
	 */
	private $maintenanceBenchmarkRunner;

	/**
	 * @var QueryBenchmarkRunner
	 */
	private $queryBenchmarkRunner;

	/**
	 * @var array
	 */
	private $benchmarkReports = [];

	/**
	 * @see JSONScriptTestCaseRunner::$deletePagesOnTearDown
	 */
	protected $deletePagesOnTearDown = true;

	protected function setUp() : void {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();
		$benchmarker = new Benchmarker();

		$this->pageImportBenchmarkRunner = new PageImportBenchmarkRunner(
			$utilityFactory->newRunnerFactory()->newXmlImportRunner(),
			$benchmarker
		);

		$this->pageContentCopyBenchmarkRunner = new PageContentCopyBenchmarkRunner(
			$this->pageImportBenchmarkRunner,
			$benchmarker,
			$utilityFactory->newPageCreator(),
			$utilityFactory->newPageReader()
		);

		// Variable set via phpunit.xml
		$this->pageContentCopyBenchmarkRunner->setCopyCount(
			isset( $GLOBALS['benchmarkPageCopyCount'] ) ? $GLOBALS['benchmarkPageCopyCount'] : null
		);

		$this->pageEditCopyBenchmarkRunner = new PageEditCopyBenchmarkRunner(
			$this->pageImportBenchmarkRunner,
			$benchmarker,
			$utilityFactory->newPageCreator(),
			$utilityFactory->newPageReader()
		);

		// Variable set via phpunit.xml
		$this->pageEditCopyBenchmarkRunner->setEditRepetitionCount(
			isset( $GLOBALS['benchmarkPageEditRepetitionCount'] ) ? $GLOBALS['benchmarkPageEditRepetitionCount'] : null
		);

		$this->jobQueueBenchmarkRunner = new JobQueueBenchmarkRunner(
			ApplicationFactory::getInstance()->newJobFactory(),
			$utilityFactory->newRunnerFactory()->newJobQueueRunner(),
			$benchmarker
		);

		$this->maintenanceBenchmarkRunner = new MaintenanceBenchmarkRunner(
			$utilityFactory->newRunnerFactory(),
			$benchmarker
		);

		$this->queryBenchmarkRunner = new QueryBenchmarkRunner(
			$this->getStore(),
			ApplicationFactory::getInstance()->getQueryFactory()->newQueryParser(),
			$benchmarker
		);
	}

	/**
	 * @see JSONScriptTestCaseRunner::getTestCaseLocation
	 */
	protected function getTestCaseLocation() {
		return __DIR__ . '/TestCases';
	}

	/**
	 * @see JSONScriptTestCaseRunner::getTestCaseLocation
	 */
	protected function getRequiredJsonTestCaseMinVersion() {
		return '1';
	}

	/**
	 * @see JSONScriptTestCaseRunner::getAllowedTestCaseFiles
	 */
	protected function getAllowedTestCaseFiles() {
		return [];
	}

	/**
	 * @see JSONScriptTestCaseRunner::runTestCaseFile
	 *
	 * @param JsonTestCaseFileHandler $jsonTestCaseFileHandler
	 */
	protected function runTestCaseFile( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$this->pageImportBenchmarkRunner->setTestCaseLocation(
			$this->getTestCaseLocation()
		);

		$this->doRunImportBenchmarks( $jsonTestCaseFileHandler );
		$this->doRunContentCopyBenchmarks( $jsonTestCaseFileHandler );
		$this->doRunEditCopyBenchmarks( $jsonTestCaseFileHandler );
		$this->doRunJobQueueBenchmarks( $jsonTestCaseFileHandler );
		$this->doRunMaintenanceBenchmarks( $jsonTestCaseFileHandler );
		$this->doRunQueryBenchmarks( $jsonTestCaseFileHandler );

		$this->assertNotEmpty(
			$this->benchmarkReports
		);

		$report = [
			'mediawiki' => MW_VERSION,
			'semantic-mediawiki' => SMW_VERSION,
			'environment' => $this->getStore()->getInfo(),
			'benchmarks' => $this->benchmarkReports
		];

		$cliOutputFormatter = new CliOutputFormatter(
			CliOutputFormatter::FORMAT_JSON
		);

		return print "\n\n" . $cliOutputFormatter->format( $report );
	}

	private function doRunImportBenchmarks( $jsonTestCaseFileHandler ) {
		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'import' ) as $case ) {
			$this->pageImportBenchmarkRunner->run( $case );
			$this->benchmarkReports[md5(json_encode( $case ) )] = $this->pageImportBenchmarkRunner->getBenchmarkReport();
		}
	}

	private function doRunContentCopyBenchmarks( $jsonTestCaseFileHandler ) {
		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'contentCopy' ) as $case ) {
			$this->pageContentCopyBenchmarkRunner->run( $case );
			$this->benchmarkReports[md5(json_encode( $case ) )] = $this->pageContentCopyBenchmarkRunner->getBenchmarkReport();
		}
	}

	private function doRunEditCopyBenchmarks( $jsonTestCaseFileHandler ) {
		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'editCopy' ) as $case ) {
			$this->pageEditCopyBenchmarkRunner->run( $case );
			$this->benchmarkReports[md5(json_encode( $case ) )] = $this->pageEditCopyBenchmarkRunner->getBenchmarkReport();
		}
	}

	private function doRunJobQueueBenchmarks( $jsonTestCaseFileHandler ) {
		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'job' ) as $case ) {
			$this->jobQueueBenchmarkRunner->run( $case );
			$this->benchmarkReports[md5(json_encode( $case ) )] = $this->jobQueueBenchmarkRunner->getBenchmarkReport();
		}
	}

	private function doRunMaintenanceBenchmarks( $jsonTestCaseFileHandler ) {
		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'maintenance' ) as $case ) {
			$this->maintenanceBenchmarkRunner->run( $case );
			$this->benchmarkReports[md5(json_encode( $case ) )] = $this->maintenanceBenchmarkRunner->getBenchmarkReport();
		}
	}

	private function doRunQueryBenchmarks( $jsonTestCaseFileHandler ) {
		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'query' ) as $case ) {
			$this->queryBenchmarkRunner->run( $case );
			$this->benchmarkReports[md5(json_encode( $case ) )] = $this->queryBenchmarkRunner->getBenchmarkReport();
		}
	}

}
