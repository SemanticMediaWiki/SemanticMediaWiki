<?php

namespace SMW\Tests\Regression;

use SMW\Tests\Util\MaintenanceRunner;
use SMW\Test\MwRegressionTestCase;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-regression
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class RebuildPropertyStatisticsMaintenanceRegressionTest extends MwRegressionTestCase {

	public function getSourceFile() {
		return __DIR__ . '/data/' . 'GenericLoremIpsumTest-Mw-1-19-7.xml';
	}

	public function acquirePoolOfTitles() {
		return array(
			'Category:Lorem ipsum',
			'Lorem ipsum',
			'Elit Aliquam urna interdum',
			'Platea enim hendrerit',
			'Property:Has Url',
			'Property:Has annotation uri',
			'Property:Has boolean',
			'Property:Has date',
			'Property:Has email',
			'Property:Has number',
			'Property:Has page',
			'Property:Has quantity',
			'Property:Has temperature',
			'Property:Has text'
		);
	}

	public function assertDataImport() {
		$maintenanceRunner = new MaintenanceRunner( 'SMW\Maintenance\RebuildPropertyStatistics' );
		$maintenanceRunner->setQuiet()->run();
	}

}