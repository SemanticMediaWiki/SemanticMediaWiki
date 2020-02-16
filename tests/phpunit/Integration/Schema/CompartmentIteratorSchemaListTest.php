<?php

namespace SMW\Tests\Integration\Schema;

use SMW\Schema\SchemaList;
use SMW\Schema\SchemaDefinition;

/**
 * @group semantic-mediawiki-integration
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CompartmentIteratorSchemaListTest extends \PHPUnit_Framework_TestCase {

	private $schemaList;

	protected function setUp() : void {
		parent::setUp();

		$this->schemaList = new SchemaList( [] );

		$schema = new SchemaDefinition(
			'fake_iterator_schema',
			json_decode( file_get_contents( SMW_PHPUNIT_DIR . '/Fixtures/Schema/fake_iterator_schema.json' ), true )
		);

		$this->schemaList->add( $schema );

		$schema = new SchemaDefinition(
			'fake_iterator_schema_extra',
			json_decode( file_get_contents( SMW_PHPUNIT_DIR . '/Fixtures/Schema/fake_iterator_schema.json' ), true )
		);

		$this->schemaList->add( $schema );
	}

	public function testCompartmentIterator_Find() {

		$expected = [
			'89ee4354fb0d6b0a6db5ac190299ded38f25a1e2' => [ 'bar_3_a', 'fake iterator schema' ],
			'14d81e4f959865b6d4f9ac80c7b614f750823470' => [ 'bar_3_b', 'fake iterator schema' ],
			'b7ef06d9350a183804407138a11a07f09ec85110' => [ 'bar_3_a_a', 'fake iterator schema' ],
			'8fa43b21d8a64bbf7fe57e5022736decc494f7df' => [ 'bar_3_b_a', 'fake iterator schema' ],

			'058b7b732f685f4aaa5617304b3f92afcd5dc8ec' => [ 'bar_3_a', 'fake iterator schema extra' ],
			'62f18e32ec2987e75a996db1f945e45938cf3a44' => [ 'bar_3_b', 'fake iterator schema extra' ],
			'f3c9599fb5f51996dc6fa72e119f7cdc13d06647' => [ 'bar_3_a_a', 'fake iterator schema extra' ],
			'afcc001dba63d97fa69e7978d92c2690291a75db' => [ 'bar_3_b_a', 'fake iterator schema extra' ]
		];

		$compartmentIterator = $this->schemaList->newCompartmentIteratorByKey(
			'filter_2'
		);

		foreach ( $compartmentIterator->find( 'bar_3_2' ) as $compartment ) {
			$fingerprint = $compartment->getFingerprint();

			if (
				$expected[$fingerprint][0] === $compartment->get( SchemaDefinition::ASSOCIATED_SECTION ) &&
				$expected[$fingerprint][1] === $compartment->get( SchemaDefinition::ASSOCIATED_SCHEMA ) ) {
				unset( $expected[$fingerprint] );
			}
		}

		$this->assertEmpty(
			$expected
		);
	}

	public function testCompartmentIterator_Find_Empty() {

		$compartmentIterator = $this->schemaList->newCompartmentIteratorByKey(
			'filter_2'
		);

		$this->assertCount(
			0,
			$compartmentIterator->find( 'no_exists' )
		);
	}

}
