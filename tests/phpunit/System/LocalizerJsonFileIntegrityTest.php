<?php

namespace SMW\Tests\System;

use SMW\Localizer\DataTypeLocalizer;
use SMW\JsonFileReader;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-system
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class LocalizerJsonFileIntegrityTest extends \PHPUnit_Framework_TestCase {

	/**
	 *@dataProvider localizerFileProvider
	 */
	public function testCanReadJsonFile( $file ) {

		$instance = new JsonFileReader( $file );
		$this->assertInternalType( 'array', $instance->getContents() );
	}

	public function localizerFileProvider() {

		$provider = array();

		$provider[] = array(
			__DIR__ . '/../../../includes/src/Localizer/' . DataTypeLocalizer::FILE
		);

		return $provider;
	}

}
