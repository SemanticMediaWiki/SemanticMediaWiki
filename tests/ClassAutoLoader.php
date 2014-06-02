<?php

namespace SMW\Tests;

use Composer\Autoload\ClassLoader ;

/**
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class ClassAutoLoader {

	protected $classLoader = null;

	/**
	 * @since 1.9.3
	 *
	 * @param ClassLoader $classLoader
	 */
	public function __construct( ClassLoader $classLoader ) {
		$this->classLoader = $classLoader;
	}

	/**
	 * @since 1.9.3
	 */
	public function addClassesToRunFullTest() {
		print( "Add SemanticMediaWiki tests ...\n\n" );

		$this->classLoader->addPsr4( 'SMW\\Test\\', __DIR__ . '/phpunit' );
		$this->classLoader->addPsr4( 'SMW\\Tests\\', __DIR__ . '/phpunit' );

		// FIXME
		$this->classLoader->addClassMap( array(
			'SMW\Tests\DataItemTest'                     => __DIR__ . '/phpunit/includes/dataitems/DataItemTest.php',
			'SMW\Tests\Reporter\MessageReporterTestCase' => __DIR__ . '/phpunit/includes/Reporter/MessageReporterTestCase.php',
			'SMW\Maintenance\RebuildConceptCache'        => __DIR__ . '/../maintenance/rebuildConceptCache.php',
			'SMW\Maintenance\RebuildData'                => __DIR__ . '/../maintenance/rebuildData.php',
			'SMW\Maintenance\RebuildPropertyStatistics'  => __DIR__ . '/../maintenance/rebuildPropertyStatistics.php'
		) );
	}

	/**
	 * @note mostly used by external extensions that require to test
	 * integration with SMW
	 *
	 * @since 1.9.3
	 */
	public function addClassesToSupportIntegrationTests() {
		print( "Add SemanticMediaWiki integration tests ...\n\n" );

		$this->classLoader->addPsr4( 'SMW\\Tests\\', __DIR__ . '/phpunit' );

		// FIXME PSR-4 laoding does't work here yet since it uses SMW\Test\....
		$this->classLoader->addClassMap( array(
			'SMW\Test\QueryPrinterRegistryTestCase' => __DIR__ . '/phpunit/QueryPrinterRegistryTestCase.php',
			'SMW\Test\QueryPrinterTestCase' => __DIR__ . '/phpunit/QueryPrinterTestCase.php'
		) );
	}

}
