<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SMW\Exception\RemovedNamespaceIndexException;

/**
 * @covers \SMW\Exception\RemovedNamespaceIndexException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class RemovedNamespaceIndexExceptionTest extends TestCase {

	public function testExtendsRuntimeException(): void {
		$this->assertInstanceOf( RuntimeException::class, new RemovedNamespaceIndexException( 100 ) );
	}

	public function testMessageMentionsSmw70AndDefineMechanism(): void {
		$exception = new RemovedNamespaceIndexException( 200 );
		$message = $exception->getMessage();

		$this->assertStringContainsString( 'smwgNamespaceIndex', $message );
		$this->assertStringContainsString( '7.0', $message );
		$this->assertStringContainsString( 'wfLoadExtension', $message );
		$this->assertStringContainsString( 'LocalSettings.php', $message );
	}

	public function testMessageContainsDefineSnippetsCalculatedFromOldValue(): void {
		$exception = new RemovedNamespaceIndexException( 200 );
		$message = $exception->getMessage();

		// 200 + offsets (2, 3, 8, 9, 12, 13) = 202, 203, 208, 209, 212, 213
		$this->assertStringContainsString( "define( 'SMW_NS_PROPERTY', 202 )", $message );
		$this->assertStringContainsString( "define( 'SMW_NS_PROPERTY_TALK', 203 )", $message );
		$this->assertStringContainsString( "define( 'SMW_NS_CONCEPT', 208 )", $message );
		$this->assertStringContainsString( "define( 'SMW_NS_CONCEPT_TALK', 209 )", $message );
		$this->assertStringContainsString( "define( 'SMW_NS_SCHEMA', 212 )", $message );
		$this->assertStringContainsString( "define( 'SMW_NS_SCHEMA_TALK', 213 )", $message );
	}

}
