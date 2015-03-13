<?php

namespace SMW\Tests;

use SMW\ControlledVocabularyContentMapper;

/**
 * @covers \SMW\ControlledVocabularyContentMapper
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ControlledVocabularyContentMapperTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\ControlledVocabularyContentMapper',
			new ControlledVocabularyContentMapper()
		);
	}

	/**
	 * @dataProvider validContent
	 */
	public function testMapValidContent( $content, $uri, $name, $list ) {

		$instance = new ControlledVocabularyContentMapper();

		$instance->parse( $content );

		$this->assertEquals(
			$uri,
			$instance->getUri()
		);

		$this->assertEquals(
			$name,
			$instance->getName()
		);

		$this->assertEquals(
			$list,
			$instance->getList()
		);

		$this->assertEmpty(
			$instance->getTypeForTerm( 'alwaysEmpty' )
		);

		foreach ( $list as $name => $type ) {
			$this->assertEquals(
				$type,
				$instance->getTypeForTerm( $name )
			);
		}
	}

	/**
	 * @dataProvider invalidContent
	 */
	public function testTryMappingInvalidContent( $content, $uri, $name, $list ) {

		$instance = new ControlledVocabularyContentMapper();

		$instance->parse( $content );

		$this->assertEquals(
			$uri,
			$instance->getUri()
		);

		$this->assertEquals(
			$name,
			$instance->getName()
		);

		$this->assertEquals(
			$list,
			$instance->getList()
		);

		$this->assertEmpty(
			$instance->getTypeForTerm( 'alwaysEmpty' )
		);
	}

	public function validContent() {

		$provider[] = array(
			"http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n name|Type:Text\n",
			'http://xmlns.com/foaf/0.1/',
			'[http://www.foaf-project.org/ Friend Of A Friend]',
			array( 'name' => 'Type:Text' )
		);

		$provider[] = array(
			" http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n   name|Type:Text\n",
			'http://xmlns.com/foaf/0.1/',
			'[http://www.foaf-project.org/ Friend Of A Friend]',
			array( 'name' => 'Type:Text' )
		);

		return $provider;
	}

	public function invalidContent() {

		$provider[] = array(
			'',
			'',
			'',
			array()
		);

		// Missing head
		$provider[] = array(
			"Foo\n name|Type:Text\n",
			'',
			'',
			array()
		);

		// Missing type
		$provider[] = array(
			"http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n name",
			'http://xmlns.com/foaf/0.1/',
			'[http://www.foaf-project.org/ Friend Of A Friend]',
			array()
		);

		return $provider;
	}

}
