<?php

namespace SMW\Tests\Utils;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @covers \SMW\Tests\Utils\MwHooksHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class MwHooksHandlerTest extends TestCase {

	private HookContainer $hookContainer;
	private ReflectionProperty $handlersProperty;

	/** @var array<string, array> */
	private array $handlerSnapshot = [];

	private const PROBED_HOOKS = [
		'ParserFirstCallInit',
	];

	protected function setUp(): void {
		parent::setUp();
		$this->hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$this->handlersProperty = new ReflectionProperty( HookContainer::class, 'handlers' );

		foreach ( self::PROBED_HOOKS as $hook ) {
			$this->hookContainer->isRegistered( $hook );
		}
		$handlers = $this->handlersProperty->getValue( $this->hookContainer );
		foreach ( self::PROBED_HOOKS as $hook ) {
			$this->handlerSnapshot[$hook] = $handlers[$hook] ?? [];
		}
	}

	protected function tearDown(): void {
		$handlers = $this->handlersProperty->getValue( $this->hookContainer );
		foreach ( $this->handlerSnapshot as $hook => $entries ) {
			$handlers[$hook] = $entries;
		}
		$this->handlersProperty->setValue( $this->hookContainer, $handlers );
		parent::tearDown();
	}

	public function testDeregisterListedHooksPreservesNonSmwClosureOnSharedHooks(): void {
		$callback = static function (): bool {
			return true;
		};
		$expectedDescription = '*closure#' . spl_object_hash( $callback ) . '*';

		$this->hookContainer->register( 'ParserFirstCallInit', $callback );
		$this->assertContains(
			$expectedDescription,
			$this->hookContainer->getHandlerDescriptions( 'ParserFirstCallInit' ),
			'precondition: closure should be registered before deregisterListedHooks runs'
		);

		( new MwHooksHandler() )
			->deregisterListedHooks()
			->reregisterAllDeclarative();

		$this->assertContains(
			$expectedDescription,
			$this->hookContainer->getHandlerDescriptions( 'ParserFirstCallInit' ),
			'non-SMW ParserFirstCallInit handler must survive deregisterListedHooks/reregisterAllDeclarative (see issue #6797)'
		);
	}

	public function testDeregisterListedHooksPreservesNonSmwObjectMethodOnSharedHooks(): void {
		// Anonymous class has no SMW\ namespace prefix, so MwHooksHandler::isSmwHandler()
		// classifies it as third-party.
		$handler = new class() {
			public function onParserFirstCallInit(): bool {
				return true;
			}
		};
		$expectedDescription = $handler::class . '::onParserFirstCallInit';

		$this->hookContainer->register( 'ParserFirstCallInit', [ $handler, 'onParserFirstCallInit' ] );
		$this->assertContains(
			$expectedDescription,
			$this->hookContainer->getHandlerDescriptions( 'ParserFirstCallInit' ),
			'precondition: [obj, method] handler should be registered before deregisterListedHooks runs'
		);

		( new MwHooksHandler() )
			->deregisterListedHooks()
			->reregisterAllDeclarative();

		$this->assertContains(
			$expectedDescription,
			$this->hookContainer->getHandlerDescriptions( 'ParserFirstCallInit' ),
			'non-SMW [obj, method] ParserFirstCallInit handler must survive deregisterListedHooks/reregisterAllDeclarative'
		);
	}

	public function testReregisterAllDeclarativeRestoresSmwHandler(): void {
		// Pre-condition: after deregisterListedHooks(), the hook has no
		// handler. After reregisterAllDeclarative(), at least one is back.
		$handler = ( new MwHooksHandler() )->deregisterListedHooks();
		$this->assertEmpty(
			$this->hookContainer->getHandlerDescriptions( 'ParserFirstCallInit' ),
			'precondition: ParserFirstCallInit must have no handler after deregisterListedHooks'
		);

		$handler->reregisterAllDeclarative();
		$this->assertNotEmpty(
			$this->hookContainer->getHandlerDescriptions( 'ParserFirstCallInit' ),
			'ParserFirstCallInit must be re-registered after reregisterAllDeclarative'
		);
	}
}
