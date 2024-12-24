<?php

namespace SMW\Tests\Elastic\Indexer\Attachment;

use SMW\Elastic\Indexer\Attachment\ScopeMemoryLimiter;

/**
 * @covers \SMW\Elastic\Indexer\Attachment\ScopeMemoryLimiter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ScopeMemoryLimiterTest extends \PHPUnit\Framework\TestCase {

	private $testCaller;
	private $memoryLimitFromCallable;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ScopeMemoryLimiter::class,
			new ScopeMemoryLimiter()
		);
	}

	public function runCallable() {
		$this->memoryLimitFromCallable = ini_get( 'memory_limit' );
		$this->testCaller->calledFromCallable();
	}

	/**
	 * @dataProvider toIntProvider
	 */
	public function testToInt( $string, $expected ) {
		$instance = new ScopeMemoryLimiter();

		$this->assertEquals(
			$expected,
			$instance->toInt( $string )
		);
	}

	public static function toIntProvider() {
		yield 'Empty string' => [
			'',
			-1,
		];

		yield 'String of spaces' => [
			'     ',
			-1,
		];

		yield 'One kb uppercased' => [
			'1K',
			1024
		];

		yield 'One kb lowercased' => [
			'1k',
			1024
		];

		yield 'One meg uppercased' => [
			'1M',
			1024 * 1024
		];

		yield 'One meg lowercased' => [
			'1m',
			1024 * 1024
		];

		yield 'One gig uppercased' => [
			'1G',
			1024 * 1024 * 1024
		];

		yield 'One gig lowercased' => [
			'1g',
			1024 * 1024 * 1024
		];
	}

	public function testExecute() {
		$memoryLimitBefore = $originalMemoryLimitBefore = ini_get( 'memory_limit' );
		$converter = new ScopeMemoryLimiter();

		if ( $memoryLimitBefore === "-1" ) {
			$memoryLimitBefore = memory_get_usage() + $converter->toInt( '10M' );
			ini_set( 'memory_limit', $memoryLimitBefore );
		}
		$this->testCaller = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'calledFromCallable' ] )
			->getMock();

		$this->testCaller->expects( $this->once() )
			->method( 'calledFromCallable' );

		$memoryLimit = $memoryLimitBefore + $converter->toInt( '1M' );

		$instance = new ScopeMemoryLimiter(
			$memoryLimit
		);

		$instance->execute( [ $this, 'runCallable' ] );

		$this->assertEquals(
			$memoryLimit,
			$this->memoryLimitFromCallable,
			"Limit we expected got set."
		);

		$this->assertEquals(
			$memoryLimitBefore,
			$instance->getMemoryLimit(),
			"Limit was reset successsfully."
		);

		ini_set( 'memory_limit', $originalMemoryLimitBefore );
	}

}
