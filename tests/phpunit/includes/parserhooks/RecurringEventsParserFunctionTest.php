<?php

namespace SMW\Test;

use SMW\RecurringEventsParserFunction;
use SMW\ParserParameterFormatter;
use SMW\MessageFormatter;
use SMW\Subobject;

use Title;
use ParserOutput;
use ReflectionClass;

/**
 * Tests for the RecurringEventsParserFunction class.
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\RecurringEventsParserFunction
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class RecurringEventsParserFunctionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\RecurringEventsParserFunction';
	}

	/**
	 * Helper method that returns a RecurringEventsParserFunction object
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return RecurringEventsParserFunction
	 */
	private function getInstance( Title $title, ParserOutput $parserOutput = null ) {
		return new RecurringEventsParserFunction(
			$this->getParserData( $title, $parserOutput ),
			new Subobject( $title ),
			new MessageFormatter( $title->getPageLanguage() ),
			$this->getSettings( array(
				'smwgDefaultNumRecurringEvents' => 100,
				'smwgMaxNumRecurringEvents' => 100
			) )
		);
	}

	/**
	 * @test RecurringEventsParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test RecurringEventsParserFunction::__construct (Test exception)
	 *
	 * @since 1.9
	 */
	public function testConstructorException() {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance = new $this->getInstance( $this->getTitle() );
	}

	/**
	 * @test RecurringEventsParserFunction::parse
	 * @dataProvider getRecurringEventsDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testParse( array $params, array $expected ) {

		$instance = $this->getInstance( $this->getTitle(), $this->getParserOutput() );
		$result = $instance->parse( $this->getParserParameterFormatter( $params ) );

		$this->assertTrue( $result !== '' ? $expected['errors'] : !$expected['errors'] );

		// Access protected property
		$reflection = new ReflectionClass( $this->getClass() );
		$events = $reflection->getProperty( 'events' );
		$events->setAccessible( true );

		$this->assertEquals( $expected['parameters'], $events->getValue( $instance )->getParameters() );

	}

	/**
	 * @test RecurringEventsParserFunction::render
	 *
	 * @since 1.9
	 */
	public function testStaticRender() {
		$parser = $this->getParser( $this->getTitle(), $this->getUser() );
		$result = RecurringEventsParserFunction::render( $parser );
		$this->assertInternalType( 'string', $result );
	}

	/**
	 * Provides data sample normally found in connection with the {{#set_recurring_event}}
	 * parser function. The first array contains parametrized input value while
	 * the second array contains expected return results for the instantiated
	 * object.
	 *
	 * @return array
	 */
	public function getRecurringEventsDataProvider() {
		return array(
			// #0
			// {{#set_recurring_event:property=Has birthday
			// |start=01 Feb 1970
			// |has title= Birthday
			// |unit=year
			// |period=12
			// |limit=3
			// }}
			array(
				array(
					'property=Has birthday',
					'start=01 Feb 1970',
					'has title=Birthday',
					'unit=month',
					'period=12',
					'limit=3'
				),
				array(
					'errors' => false,
					'dates' => array( '1 February 1970', '1 February 1971 00:00:00', '1 February 1972 00:00:00', '1 February 1973 00:00:00' ), 'property' => array( 'Has birthday', 'Has title' ),
					'parameters' => array( 'has title' => array( 'Birthday' ) )
				)
			),

			// #1
			// {{#set_recurring_event:property=Has birthday
			// |start=01 Feb 1972 02:00
			// |has title=Test 12
			// |unit=week
			// |period=4
			// |limit=3
			// }}
			array(
				array(
					'property=Has birthday',
					'start=01 Feb 1972 02:00',
					'has title=Test 2',
					'unit=week',
					'period=4',
					'limit=3'
				),
				array( 'errors' => false,
					'dates' => array( '1 February 1972 02:00:00', '29 February 1972 02:00:00', '28 March 1972 02:00:00', '25 April 1972 02:00:00' ), 'property' => array( 'Has birthday', 'Has title' ),
					'parameters' => array( 'has title' => array( 'Test 2' ) )
				)
			),

			// #2
			// {{#set_recurring_event:property=Has date
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			array(
				array(
					'property=Has date',
					'start=January 4, 2010',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				),
				array(
					'errors' => false,
					'dates' => array( '4 January 2010', '11 January 2010 00:00:00', '1 February 2010 00:00:00', 'March 16, 2010', 'March 23, 2010' ),
					'property' => 'Has date',
					'parameters' => array()
				)
			),

			// #3
			// {{#set_recurring_event:property=Has date
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010|+sep=;
			// |exclude=January 18, 2010;January 25, 2010|+sep=;
			// }}
			array(
				array(
					'property=Has date',
					'start=January 4, 2010',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'+sep=;', 'exclude=January 18, 2010;January 25, 2010',
					'+sep=;'
				),
				array(
					'errors' => false,
					'dates' => array( '4 January 2010', '11 January 2010 00:00:00', '1 February 2010 00:00:00', 'March 16, 2010', 'March 23, 2010' ),
					'property' => 'Has date',
					'parameters' => array()
				)
			),

			// #4 Named page reference pointer
			// {{#set_recurring_event:FooBar
			// |property=Has birthday
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010|+sep=;
			// |exclude=January 18, 2010;January 25, 2010|+sep=;
			// }}
			array(
				array(
					'FooBar',
					'property=Has birthday',
					'start=January 4, 2010',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'+sep=;', 'exclude=January 18, 2010;January 25, 2010',
					'+sep=;'
				),
				array(
					'errors' => false,
					'dates' => array( '4 January 2010', '11 January 2010 00:00:00', '1 February 2010 00:00:00', 'March 16, 2010', 'March 23, 2010' ),
					'property' => 'Has birthday',
					'parameters' => array()
				)
			),

			// #5 Simulate first parameter starting being - raising an error
			// {{#set_recurring_event:-
			// property=Has date
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			array(
				array(
					'-',
					'property=Has date',
					'start=January 4, 2010',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				),
				array(
					'errors' => false,
					'dates' => array(),
					'property' => '',
					'parameters' => array()
				)
			),

			// #6 Simulate first parameter starting with - raising an error
			// {{#set_recurring_event:-Foo
			// property=Has date
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			array(
				array(
					'-Foo',
					'property=Has date',
					'start=January 4, 2010',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				),
				array(
					'errors' => true,
					'dates' => array(),
					'property' => '',
					'parameters' => array()
				)
			),

			// #7 Simulate first parameter starting with a underscore raising an error
			// {{#set_recurring_event:_FooBar
			// property=Has date
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			array(
				array(
					'_FooBar',
					'property=Has date',
					'start=January 4, 2010',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				),
				array(
					'errors' => true,
					'dates' => array(),
					'property' => '',
					'parameters' => array()
				)
			),

			// #8 Simulate start date has wrong type
			// {{#set_recurring_event:property=Has date
			// |start=???
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			array(
				array(
					'property=Has date',
					'start=???',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				),
				array(
					'errors' => true,
					'dates' => array(),
					'property' => '',
					'parameters' => array()
				)
			),

			// #9 Simulate missing start date
			// {{#set_recurring_event:property=Has date
			// |start=
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			array(
				array(
					'property=Has date',
					'start=',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				),
				array(
					'errors' => true,
					'dates' => array(),
					'property' => '',
					'parameters' => array()
				)
			),

			// #10 Simulate missing property
			// {{#set_recurring_event:property=
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010|+sep=;
			// |exclude=January 18, 2010;January 25, 2010|+sep=;
			// }}
			array(
				array(
					'property=',
					'start=January 4, 2010',
					'unit=week', 'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'+sep=;',
					'exclude=January 18, 2010;January 25, 2010',
					'+sep=;'
				),
				array(
					'errors' => true,
					'dates' => array(),
					'property' => '',
					'parameters' => array()
				)
			)
		);
	}

}
