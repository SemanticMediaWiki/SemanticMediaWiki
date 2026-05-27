<?php

namespace SMW\Tests\Unit\Utils;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use SMW\Tests\Utils\SMWDeclarativeHookReseater;
use stdClass;
use Wikimedia\ScopedCallback;

/**
 * @covers \SMW\Tests\Utils\SMWDeclarativeHookReseater
 *
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SMWDeclarativeHookReseaterTest extends TestCase {

	private HookContainer $hookContainer;
	private SMWDeclarativeHookReseater $reseater;

	protected function setUp(): void {
		parent::setUp();
		$this->hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$this->reseater = new SMWDeclarativeHookReseater( $this->hookContainer );
	}

	public function testBuildSmwHandlerForDeclaredHookReturnsCallable(): void {
		$callable = $this->reseater->buildSmwHandlerFor( 'ParserFirstCallInit' );

		$this->assertIsCallable( $callable );
		$this->assertIsArray( $callable );
		$this->assertCount( 2, $callable );
		$this->assertIsObject( $callable[0] );
		$this->assertSame( 'onParserFirstCallInit', $callable[1] );
	}

	public function testBuildSmwHandlerForUndeclaredHookThrows(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'DefinitelyNotAnSmwHook' );
		$this->reseater->buildSmwHandlerFor( 'DefinitelyNotAnSmwHook' );
	}

	public function testReseatDeclarativeHandlersRebuildsSmwHandlers(): void {
		$beforeIds = $this->snapshotSmwHandlerObjectIds( 'ParserFirstCallInit' );

		$this->reseater->reseatDeclarativeHandlers();

		$afterIds = $this->snapshotSmwHandlerObjectIds( 'ParserFirstCallInit' );

		$this->assertNotEmpty( $beforeIds, 'SMW must have a ParserFirstCallInit handler before reseat' );
		$this->assertNotEmpty( $afterIds, 'SMW must have a ParserFirstCallInit handler after reseat' );
		$this->assertNotEquals(
			$beforeIds,
			$afterIds,
			'reseatDeclarativeHandlers must replace handler instances with fresh ones'
		);
	}

	public function testReseatDeclarativeHandlersPreservesNonSmwHandlers(): void {
		$marker = new stdClass();
		$marker->called = false;
		$externalHandler = static function () use ( $marker ): void {
			$marker->called = true;
		};

		$reset = $this->hookContainer->scopedRegister( 'ParserFirstCallInit', $externalHandler );
		try {
			$this->reseater->reseatDeclarativeHandlers();

			$parser = $this->createMock( Parser::class );
			$this->hookContainer->run( 'ParserFirstCallInit', [ $parser ], [ 'abortable' => false ] );
			$this->assertTrue(
				$marker->called,
				'Non-SMW handler must survive reseatDeclarativeHandlers()'
			);
		} finally {
			ScopedCallback::consume( $reset );
		}

		// The ScopedCallback cleanup must actually release the handler.
		// scopedRegister stores entries under string keys like
		// 'TemporaryHook_N'; the reseater must preserve those keys so the
		// cleanup's `unset` lands on the right entry. Without that the
		// closure leaks into subsequent tests.
		$marker->called = false;
		$this->hookContainer->run(
			'ParserFirstCallInit',
			[ $this->createMock( Parser::class ) ],
			[ 'abortable' => false ]
		);
		$this->assertFalse(
			$marker->called,
			'ScopedCallback cleanup must unregister the handler after reseat'
		);
	}

	/**
	 * @return int[]
	 */
	private function snapshotSmwHandlerObjectIds( string $hook ): array {
		$prop = new ReflectionProperty( HookContainer::class, 'handlers' );
		$all = $prop->getValue( $this->hookContainer );
		$ids = [];
		foreach ( $all[$hook] ?? [] as $entry ) {
			$cb = $entry['callback'] ?? null;
			if ( is_array( $cb ) && is_object( $cb[0] ) && str_starts_with( $cb[0]::class, 'SMW\\' ) ) {
				$ids[] = spl_object_id( $cb[0] );
			}
		}
		return $ids;
	}

}
