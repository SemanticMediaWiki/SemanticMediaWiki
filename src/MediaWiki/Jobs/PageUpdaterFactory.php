<?php

namespace SMW\MediaWiki\Jobs;

use SMW\MediaWiki\PageUpdater;
use SMW\Services\ServicesFactory;

/**
 * Constructs fresh `PageUpdater` instances on demand.
 *
 * `PageUpdater` accumulates per-use state (page queue, transaction
 * deferrals), so jobs that need one must request a fresh instance per
 * invocation rather than receive a shared singleton via the ObjectFactory
 * spec.
 *
 * Internally delegates to `ServicesFactory::newPageUpdater()` to reuse the
 * existing connection / `TransactionalCallableUpdate` / logger wiring.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class PageUpdaterFactory {

	public function __construct(
		private readonly ServicesFactory $servicesFactory,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function newPageUpdater(): PageUpdater {
		return $this->servicesFactory->newPageUpdater();
	}

}
