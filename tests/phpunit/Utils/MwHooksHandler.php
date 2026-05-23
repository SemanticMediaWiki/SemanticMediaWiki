<?php

namespace SMW\Tests\Utils;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use ReflectionProperty;
use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since   1.9
 *
 * @author mwjames
 */
class MwHooksHandler {

	private HookContainer $hookContainer;
	private ?ReflectionProperty $handlersProperty = null;

	/**
	 * Lazily-loaded snapshot of `extension.json`'s `Hooks` block (mapping
	 * MW hook name -> HookHandler id) and `HookHandlers` block (HookHandler
	 * id -> object factory spec). Cached as static so repeated test setUp()
	 * calls don't re-parse the file.
	 */
	private static ?array $extensionJson = null;

	private array $wgHooks = [];
	private array $inTestRegisteredHooks = [];

	private array $listOfSmwHooks = [
		'SMW::Store::BeforeDataUpdateComplete',
		'SMW::Store::AfterDataUpdateComplete',

		// Those shoudl not be disabled so that extension used
		// by a test will run the registration in case an instance
		// is cleared
		//	'smwInitDatatypes',
		//	'SMW::DataType::initTypes',

		'SMW::Settings::BeforeInitializationComplete',
		'SMW::Setup::AfterInitializationComplete',
		'SMW::Property::initProperties',
		'SMW::Constraint::initConstraints',
		'SMW::Factbox::BeforeContentGeneration',
		'SMW::SQLStore::updatePropertyTableDefinitions',
		'SMW::Store::BeforeQueryResultLookupComplete',
		'SMW::Store::AfterQueryResultLookupComplete',
		'SMW::SQLStore::BeforeChangeTitleComplete',
		'SMW::SQLStore::BeforeDeleteSubjectComplete',
		'SMW::SQLStore::AfterDeleteSubjectComplete',
		'SMW::Parser::BeforeMagicWordsFinder',
		'SMW::SQLStore::BeforeDataRebuildJobInsert',
		'SMW::SQLStore::AddCustomFixedPropertyTables',
		'SMW::SQLStore::AfterDataUpdateComplete',
		'SMW::Browse::AfterIncomingPropertiesLookupComplete',
		'SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate',

		'SMW::Listener::ChangeListener::RegisterPropertyChangeListeners',
		'SMW::Admin::RegisterTaskHandlers',
		'SMW::Schema::RegisterSchemaTypes',

		'SMW::GetPreferences',
		'SMW::Parser::AfterLinksProcessingComplete',
		'SMW::Parser::ParserAfterTidyPropertyAnnotationComplete',

		'SMW::Maintenance::AfterUpdateEntityCollationComplete',

		'SMW::Indicator::EntityExaminer::RegisterIndicatorProviders',
		'SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders',

		'SMW::RevisionGuard::IsApprovedRevision',
		'SMW::RevisionGuard::ChangeRevisionID',
		'SMW::RevisionGuard::ChangeFile',
		'SMW::RevisionGuard::ChangeRevision',

		'SMWSQLStore3::updateDataBefore',
		'SMW::SQLStore::BeforeDataUpdateComplete',

		'SMW::SQLStore::Installer::BeforeCreateTablesComplete',
		'SMW::SQLStore::Installer::AfterCreateTablesComplete',
		'SMW::SQLStore::Installer::AfterDropTablesComplete'
	];

	public function __construct() {
		$this->hookContainer = MediaWikiServices::getInstance()->getHookContainer();
	}

	/**
	 * @since  2.0
	 *
	 * @return MwHooksHandler
	 */
	public function deregisterListedHooks() {
		// SMW-internal hooks (listOfSmwHooks): clear without preservation so tests
		// see a fresh slate. SMW-shipped wgHooks closures registered through
		// `data/config/*.php` files are expected to be reset here, and existing
		// tests (e.g. HookDispatcherTest) rely on that.
		foreach ( $this->listOfSmwHooks as $hook ) {
			if ( $this->hookContainer->isRegistered( $hook ) ) {
				$this->hookContainer->clear( $hook );
			}
		}

		// Shared MediaWiki hooks (extension.json `Hooks`): preserve non-SMW
		// handlers across the clear. HookContainer::clear() wipes every handler,
		// not just SMW's, so without this snapshot third-party handlers on shared
		// hooks (e.g. Scribunto's ParserFirstCallInit) silently disappear during
		// tests. Preserved handlers are appended back before
		// reregisterAllDeclarative() runs, so SMW's handler ends up last on the
		// chain - a behaviour change versus pre-fix order, but acceptable for the
		// hooks involved (parser function registration, etc., none of which
		// depend on order). See issue #6797.
		foreach ( $this->getDeclarativeHookList() as $hook ) {
			if ( !$this->hookContainer->isRegistered( $hook ) ) {
				continue;
			}

			$preserved = $this->collectNonSmwHandlerEntries( $hook );

			$this->hookContainer->clear( $hook );

			if ( $preserved ) {
				$this->writeHandlerEntries( $hook, $preserved );
			}
		}

		return $this;
	}

	/**
	 * Returns the normalized handler entries currently registered for $hook,
	 * excluding those that target an SMW class. The full entry is kept (not
	 * just the callback) so metadata such as the `args` field for the deprecated
	 * `[$callable, $data...]` registration form survives the round-trip.
	 *
	 * Reflection is used because HookContainer exposes no non-deprecated way
	 * to enumerate handlers (getHandlerCallbacks emits a deprecation warning).
	 *
	 * @return array[] List of normalized handler entries
	 */
	private function collectNonSmwHandlerEntries( string $hook ): array {
		$handlers = $this->getHandlersProperty()->getValue( $this->hookContainer )[$hook] ?? [];

		$preserved = [];
		foreach ( $handlers as $entry ) {
			$callback = $entry['callback'] ?? null;
			if ( $callback === null || $this->isSmwHandler( $callback ) ) {
				continue;
			}
			$preserved[] = $entry;
		}

		return $preserved;
	}

	/**
	 * Writes the given normalized handler entries directly into HookContainer's
	 * cache for $hook. Used to restore preserved entries verbatim after a clear,
	 * since HookContainer::register() would re-normalize them and drop fields
	 * like `args`.
	 */
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

	/**
	 * @since  2.0
	 *
	 * @return MwHooksHandler
	 */
	public function restoreListedHooks() {
		foreach ( $this->inTestRegisteredHooks as $hook ) {
			$this->hookContainer->clear( $hook );
		}

		foreach ( $this->wgHooks as $hook => $definition ) {
			$this->hookContainer->register( $hook, $definition );
			unset( $this->wgHooks[$hook] );
		}

		return $this;
	}

	/**
	 * @since  2.1
	 *
	 * @return MwHooksHandler
	 */
	public function register( $name, callable $callback ) {
		$listOfHooks = array_merge(
			$this->listOfSmwHooks,
			$this->getDeclarativeHookList()
		);

		if ( !in_array( $name, $listOfHooks ) ) {
			throw new RuntimeException( "$name is not listed as registrable hook" );
		}

		$this->inTestRegisteredHooks[] = $name;
		$this->hookContainer->register( $name, $callback );

		return $this;
	}

	/**
	 * Re-registers every MW hook that SMW declares in `extension.json`'s
	 * `Hooks` block. Each handler is built via MediaWikiServices'
	 * ObjectFactory from the matching `HookHandlers` spec, then registered on
	 * HookContainer. This mirrors how MediaWiki itself wires declarative
	 * HookHandlers at boot, and is used by integration tests that need every
	 * SMW handler back on its hook after a `deregisterListedHooks()` call.
	 *
	 * @since  7.0.0
	 *
	 * @return MwHooksHandler
	 */
	public function reregisterAllDeclarative(): self {
		foreach ( $this->getDeclarativeHookList() as $hook ) {
			$this->registerDeclarativeHandler( $hook );
		}

		return $this;
	}

	/**
	 * Returns a callable for the SMW-declared handler of $hook, built from
	 * `extension.json`'s `HookHandlers` spec via ObjectFactory. The returned
	 * callable is `[$handler, $methodName]` where $methodName is derived using
	 * MediaWiki's `HookContainer::getHookMethodName()` rule.
	 *
	 * @since  7.0.0
	 *
	 * @return callable|false False when $hook is not declared by SMW
	 */
	public function getHandlerFor( string $hook ): callable|false {
		$spec = $this->getHandlerSpecFor( $hook );
		if ( $spec === null ) {
			return false;
		}

		$method = $this->deriveHookMethodName( $hook );
		$handler = MediaWikiServices::getInstance()
			->getObjectFactory()
			->createObject( $spec );

		return [ $handler, $method ];
	}

	private function registerDeclarativeHandler( string $hook ): void {
		$spec = $this->getHandlerSpecFor( $hook );
		if ( $spec === null ) {
			return;
		}

		// Build the handler eagerly and register `[$handler, $method]` so
		// MediaWiki's HookContainer dispatches with the original hook
		// arguments intact, including by-reference ones (e.g. the
		// `&$result` slot on SMW::Store::BeforeQueryResultLookupComplete).
		// Wrapping in a closure that takes variadic `...$args` would silently
		// downgrade references to values, breaking handlers that mutate an
		// out-parameter through the reference.
		//
		// Eager construction matches the pre-HookHandlers `Hooks::register()`
		// behaviour. Tests that swap a service AFTER setUp's
		// `reregisterAllDeclarative()` must call `reregisterAllDeclarative()`
		// again to rebuild handlers against the new service.
		$method = $this->deriveHookMethodName( $hook );
		$handler = MediaWikiServices::getInstance()
			->getObjectFactory()
			->createObject( $spec );

		$this->hookContainer->register( $hook, [ $handler, $method ] );
	}

	private function getHandlerSpecFor( string $hook ): ?array {
		$json = self::getExtensionJson();
		$handlerId = $json['Hooks'][$hook] ?? null;
		if ( $handlerId === null ) {
			return null;
		}

		return $json['HookHandlers'][$handlerId] ?? null;
	}

	/**
	 * Mirror of MediaWiki's `HookContainer::getHookMethodName()`: replaces
	 * `:`, `\`, and `-` with `_` and prepends `on`. Kept in sync with core.
	 */
	private function deriveHookMethodName( string $hook ): string {
		return 'on' . strtr( $hook, ':\\-', '___' );
	}

	/**
	 * @return string[] MW hook names that SMW registers declaratively
	 */
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
