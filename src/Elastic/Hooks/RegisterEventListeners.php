<?php

namespace SMW\Elastic\Hooks;

use SMW\Elastic\ElasticFactory;

/**
 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Event::RegisterEventListeners
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class RegisterEventListeners {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly ElasticFactory $elasticFactory,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSMW__Event__RegisterEventListeners( $eventListener ): bool {
		// Store resolution is deferred: ElasticFactory::onInvalidateEntityCache (the
		// registered callback) resolves the Store at dispatch time, which keeps this
		// boot-time-fired hook safe from eager Store construction.
		$eventListener->registerCallback(
			'InvalidateEntityCache',
			[ $this->elasticFactory, 'onInvalidateEntityCache' ]
		);

		return true;
	}

}
