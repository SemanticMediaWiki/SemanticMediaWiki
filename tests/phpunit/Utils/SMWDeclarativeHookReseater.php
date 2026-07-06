<?php

namespace SMW\Tests\Utils;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use ReflectionProperty;
use RuntimeException;

/**
 * Focused test helper for the two SMW-specific hook concerns MediaWiki core
 * does not address: rebuilding declarative SMW handlers so they observe
 * mid-test service swaps, and constructing SMW's own declarative handler for
 * a given hook on demand.
 *
 * Tests that need to register or clear hooks directly should use
 * `MediaWikiIntegrationTestCase::setTemporaryHook` and `clearHook` instead.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SMWDeclarativeHookReseater {

	private HookContainer $hookContainer;
	private ?ReflectionProperty $handlersProperty = null;

	private static ?array $extensionJson = null;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * Rebuild every SMW-declared hook handler from `extension.json`'s
	 * `HookHandlers` block via `ObjectFactory`, replacing cached handler
	 * instances on `HookContainer`. Non-SMW handlers on the same hooks (e.g.
	 * Scribunto's `ParserFirstCallInit`) are preserved across the rebuild via
	 * a reflection round-trip.
	 *
	 * Used by tests that swap `MediaWikiServices` state mid-test: declarative
	 * handlers cached at boot hold references to the pre-swap services; this
	 * forces reconstruction against the current container state.
	 *
	 * Note: removable only once every SMW handler that holds a reference to
	 * `SMW.Store` either resolves it dynamically per call or stops capturing
	 * it at construction time. The current "Complete handler DI" arc is
	 * converting handlers to constructor-injected dependencies, which makes
	 * the staleness problem worse; killing this method requires a deliberate
	 * exception for `Store` (e.g. inject a `StoreFactory` and resolve
	 * per-call). ~15 of 54 declarative handlers would need that treatment.
	 *
	 * @since 7.0.0
	 */
	public function reseatDeclarativeHandlers(): void {
		foreach ( $this->getDeclarativeHookList() as $hook ) {
			if ( !$this->hookContainer->isRegistered( $hook ) ) {
				$this->registerDeclarativeHandler( $hook );
				continue;
			}

			$preserved = $this->collectNonSmwHandlerEntries( $hook );

			$this->hookContainer->clear( $hook );

			if ( $preserved ) {
				$this->writeHandlerEntries( $hook, $preserved );
			}

			$this->registerDeclarativeHandler( $hook );
		}
	}

	/**
	 * Build a callable for SMW's declared handler for `$hook` from
	 * `extension.json`'s `HookHandlers` spec via `ObjectFactory`. Returns
	 * `[$handler, $methodName]` so by-reference hook args dispatch intact
	 * (a variadic closure wrapper would silently downgrade refs to values).
	 *
	 * @since 7.0.0
	 *
	 * @throws RuntimeException When `$hook` is not declared by SMW.
	 */
	public function buildSmwHandlerFor( string $hook ): callable {
		$spec = $this->getHandlerSpecFor( $hook );
		if ( $spec === null ) {
			throw new RuntimeException( "Hook \"$hook\" is not declared by SMW" );
		}

		$method = $this->deriveHookMethodName( $hook );
		$handler = MediaWikiServices::getInstance()
			->getObjectFactory()
			->createObject( $spec );

		return [ $handler, $method ];
	}

	/**
	 * Returns the names of every MediaWiki hook SMW declares in
	 * `extension.json`'s `Hooks` block. Callers can iterate this list against
	 * `MediaWikiIntegrationTestCase::clearHook()` to disable every SMW
	 * declarative handler for the duration of a test (the equivalent of the
	 * legacy `MwHooksHandler::deregisterListedHooks()` shape, but expressed
	 * through MW core primitives).
	 *
	 * @since 7.0.0
	 *
	 * @return string[]
	 */
	public function getDeclarativeHookNames(): array {
		return $this->getDeclarativeHookList();
	}

	private function registerDeclarativeHandler( string $hook ): void {
		$this->hookContainer->register( $hook, $this->buildSmwHandlerFor( $hook ) );
	}

	private function collectNonSmwHandlerEntries( string $hook ): array {
		$handlers = $this->getHandlersProperty()->getValue( $this->hookContainer )[$hook] ?? [];

		// Preserve the original array keys. `HookContainer::scopedRegister`
		// stores temporary handlers under string keys like 'TemporaryHook_N'
		// and its ScopedCallback cleanup does `unset($handlers[$hook][$id])`
		// against that same key. Re-keying preserved entries to 0, 1, ...
		// silently breaks that cleanup and leaks the handler into sibling
		// tests.
		$preserved = [];
		foreach ( $handlers as $key => $entry ) {
			$callback = $entry['callback'] ?? null;
			if ( $callback === null || $this->isSmwHandler( $callback ) ) {
				continue;
			}
			$preserved[$key] = $entry;
		}

		return $preserved;
	}

	private function writeHandlerEntries( string $hook, array $entries ): void {
		$property = $this->getHandlersProperty();
		$handlers = $property->getValue( $this->hookContainer );
		$handlers[$hook] = $entries;
		$property->setValue( $this->hookContainer, $handlers );
	}

	private function getHandlersProperty(): ReflectionProperty {
		if ( $this->handlersProperty === null ) {
			$this->handlersProperty = new ReflectionProperty( HookContainer::class, 'handlers' );
		}
		return $this->handlersProperty;
	}

	private function isSmwHandler( $callback ): bool {
		if ( is_string( $callback ) ) {
			return str_starts_with( $callback, 'SMW\\' );
		}

		if ( is_array( $callback ) && isset( $callback[0] ) && is_object( $callback[0] ) ) {
			return str_starts_with( $callback[0]::class, 'SMW\\' );
		}

		return false;
	}

	private function getHandlerSpecFor( string $hook ): ?array {
		$json = self::getExtensionJson();
		$handlerId = $json['Hooks'][$hook] ?? null;
		if ( $handlerId === null ) {
			return null;
		}
		return $json['HookHandlers'][$handlerId] ?? null;
	}

	private function deriveHookMethodName( string $hook ): string {
		return 'on' . strtr( $hook, ':\\-', '___' );
	}

	private function getDeclarativeHookList(): array {
		return array_keys( self::getExtensionJson()['Hooks'] ?? [] );
	}

	private static function getExtensionJson(): array {
		if ( self::$extensionJson === null ) {
			$path = __DIR__ . '/../../../extension.json';
			self::$extensionJson = json_decode( file_get_contents( $path ), true );
		}
		return self::$extensionJson;
	}

}
