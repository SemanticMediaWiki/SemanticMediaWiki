<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\EngineOptions;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\EngineOptions
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class EngineOptionsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\EngineOptions',
			new EngineOptions()
		);
	}

	public function testDefaultProperties() {

		$instance = new EngineOptions();

		$this->assertEquals(
			$instance->ignoreQueryErrors,
			$GLOBALS['smwgIgnoreQueryErrors']
		);

		$this->assertEquals(
			$instance->sortingSupport,
			$GLOBALS['smwgQSortingSupport']
		);

		$this->assertEquals(
			$instance->randomSortingSupport,
			$GLOBALS['smwgQRandSortingSupport']
		);
	}

}

