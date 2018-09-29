<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Enum {

	/**
	 * Option that allows to suspend the page purge
	 */
	const OPT_SUSPEND_PURGE = 'smw.opt.suspend.purge';

	/**
	 * Indicates to purge an associated parser cache
	 */
	const PURGE_ASSOC_PARSERCACHE = 'smw.purge.assoc.parsercache';

	/**
	 * Indicates whether to proceed with the cache warming or not
	 */
	const SUSPEND_CACHE_WARMUP = 'smw.suspend.cache.warmup';

}
