<?php

namespace SMW\Tests\Elastic\Indexer\Attachment;

use SMW\Elastic\Indexer\Attachment\ScopeMemoryLimiter;

/**
 * @covers \SMW\Elastic\Indexer\Attachment\ScopeMemoryLimiter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
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
		// Retrieve the original memory limit
		$memoryLimitBefore = $originalMemoryLimitBefore = ini_get( 'memory_limit' );
		$converter = new ScopeMemoryLimiter();

		// Handle unlimited memory limit (-1) by setting a dynamic buffer
		if ( $memoryLimitBefore === "-1" ) {
			$currentUsage = memory_get_usage();
			$buffer = $converter->toInt( '20M' );
			$memoryLimitBefore = $currentUsage + $buffer;

			ini_set( 'memory_limit', $memoryLimitBefore );
		}

		$this->testCaller = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'calledFromCallable' ] )
			->getMock();

		$this->testCaller->expects( $this->once() )
			->method( 'calledFromCallable' );

		// Calculate the new memory limit with an additional buffer
		$additionalBuffer = $converter->toInt( '1M' );
		$memoryLimit = $memoryLimitBefore + $additionalBuffer;

		// Create the ScopeMemoryLimiter instance with the calculated limit
		$instance = new ScopeMemoryLimiter( $memoryLimit );

		// Execute the callable within the memory-limited scope
		$instance->execute( [ $this, 'runCallable' ] );

		// Assert that the callable was executed with the expected memory limit
		$this->assertEquals(
			$memoryLimit,
			$this->memoryLimitFromCallable,
			"Limit we expected got set."
		);

		// Assert that the memory limit was successfully reset to the original value
		$this->assertEquals(
			$memoryLimitBefore,
			$instance->getMemoryLimit(),
			"Limit was reset successfully."
		);

		// Restore the original memory limit
		ini_set( 'memory_limit', $originalMemoryLimitBefore );
	}
}
