<?php

namespace SMW\Importer;

use Onoi\MessageReporter\MessageReporterAware;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
interface ContentCreator extends MessageReporterAware {

	/**
	 * @since 3.0
	 *
	 * @param ImportContents $importContents
	 *
	 * @return boolean
	 */
	public function canCreateContentsFor( ImportContents $importContents );

	/**
	 * @since 3.0
	 *
	 * @param ImportContents $importContents
	 */
	public function create( ImportContents $importContents );

}
