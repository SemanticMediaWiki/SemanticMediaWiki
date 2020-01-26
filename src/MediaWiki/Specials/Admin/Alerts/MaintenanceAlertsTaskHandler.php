<?php

namespace SMW\MediaWiki\Specials\Admin\Alerts;

use Html;
use SMW\Message;
use SMW\Utils\HtmlTabs;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;

/**
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class MaintenanceAlertsTaskHandler extends TaskHandler {

	/**
	 * @var TaskHandler[]
	 */
	private $taskHandlers = [];

	/**
	 * @since 3.2
	 *
	 * @param TaskHandler[] $taskHandlers
	 */
	public function __construct( array $taskHandlers = [] ) {
		$this->taskHandlers = $taskHandlers;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getName() : string {
		return 'maintenancealerts';
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$contents = '';

		foreach ( $this->taskHandlers as $taskHandler ) {

			$taskHandler->setFeatureSet(
				$this->featureSet
			);

			$contents .= $taskHandler->getHtml();
		}

		if ( $contents === '' ) {
			return '';
		}

		return Html::rawElement(
			'p',
			[],
			$this->msg( 'smw-admin-maintenancealerts-section-intro' )
		) . $contents;
	}

}
