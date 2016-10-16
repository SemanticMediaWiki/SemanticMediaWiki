<?php

namespace SMW\Tests\ParserFunctions;

use ParserOutput;
use ReflectionClass;
use SMW\MessageFormatter;
use SMW\ParserData;
use SMW\ParserParameterProcessor;
use SMW\ParserFunctions\RecurringEventsParserFunction;
use SMW\Subobject;
use Title;

/**
 * @covers \SMW\ParserFunctions\RecurringEventsParserFunction
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RecurringEventsParserFunctionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ParserFunctions\RecurringEventsParserFunction',
			new RecurringEventsParserFunction(
				$parserData,
				$subobject,
				$messageFormatter
			)
		);
	}

	/**
	 * @dataProvider recurringEventsDataProvider
	 */
	public function testParse( array $params, array $expected ) {

		$title = Title::newFromText( __METHOD__ );

		$instance = new RecurringEventsParserFunction(
			new ParserData( $title, new ParserOutput() ),
			new Subobject( $title ),
			new MessageFormatter( \Language::factory( 'en' ) )
		);

		$instance->setDefaultNumRecurringEvents( 100 );
		$instance->setMaxNumRecurringEvents( 100 );

		$result = $instance->parse(
			new ParserParameterProcessor( $params )
		);

		$this->assertTrue( $result !== '' ? $expected['errors'] : !$expected['errors'] );

		$reflector = new ReflectionClass( '\SMW\ParserFunctions\RecurringEventsParserFunction' );
		$events = $reflector->getProperty( 'recurringEvents' );
		$events->setAccessible( true );

		$this->assertEquals(
			$expected['parameters'],
			$events->getValue( $instance )->getParameters()
		);
	}

	public function recurringEventsDataProvider() {

		$provider = array();

		// #0
		// {{#set_recurring_event:property=Has birthday
		// |start=01 Feb 1970
		// |has title= Birthday
		// |unit=year
		// |period=12
		// |limit=3
		// }}
		$provider[] = array(
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
				'dates' => array(
					'1 February 1970',
					'1 February 1971',
					'1 February 1972',
					'1 February 1973'
				),
				'property' => array(
					'Has birthday',
					'Has title'
				),
				'parameters' => array(
					'has title' => array( 'Birthday' )
				)
			)
		);

		// #1
		// {{#set_recurring_event:property=Has birthday
		// |start=01 Feb 1972 02:00
		// |has title=Test 12
		// |unit=week
		// |period=4
		// |limit=3
		// }}
		$provider[] = array(
			array(
				'property=Has birthday',
				'start=01 Feb 1972 02:00',
				'has title=Test 2',
				'unit=week',
				'period=4',
				'limit=3'
			),
			array(
				'errors' => false,
				'dates' => array(
					'1 February 1972 02:00:00',
					'29 February 1972 02:00:00',
					'28 March 1972 02:00:00',
					'25 April 1972 02:00:00'
				),
				'property' => array(
					'Has birthday',
					'Has title'
				),
				'parameters' => array(
					'has title' => array( 'Test 2' )
				)
			)
		);

		// #2
		// {{#set_recurring_event:property=Has date
		// |start=January 4, 2010
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010
		// |exclude=January 18, 2010;January 25, 2010
		// }}
		$provider[] = array(
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
				'dates' => array(
					'4 January 2010',
					'11 January 2010',
					'1 February 2010',
					'March 16, 2010',
					'March 23, 2010'
				),
				'property' => 'Has date',
				'parameters' => array()
			)
		);


		// #3
		// {{#set_recurring_event:property=Has date
		// |start=January 4, 2010
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010|+sep=;
		// |exclude=January 18, 2010;January 25, 2010|+sep=;
		// }}
		$provider[] = array(
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
				'dates' => array(
					'4 January 2010',
					'11 January 2010',
					'1 February 2010',
					'March 16, 2010',
					'March 23, 2010'
				),
				'property' => 'Has date',
				'parameters' => array()
			)
		);

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
		$provider[] = array(
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
				'dates' => array(
					'4 January 2010',
					'11 January 2010',
					'1 February 2010',
					'March 16, 2010',
					'March 23, 2010'
				),
				'property' => 'Has birthday',
				'parameters' => array()
			)
		);

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
		$provider[] = array(
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
		);

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
		$provider[] = array(
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
		);

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
		$provider[]  = array(
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
		);

		// #8 Simulate start date has wrong type
		// {{#set_recurring_event:property=Has date
		// |start=???
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010
		// |exclude=January 18, 2010;January 25, 2010
		// }}
		$provider[] = array(
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
		);

		// #9 Simulate missing start date
		// {{#set_recurring_event:property=Has date
		// |start=
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010
		// |exclude=January 18, 2010;January 25, 2010
		// }}
		$provider[] = array(
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
		);

		// #10 Simulate missing property
		// {{#set_recurring_event:property=
		// |start=January 4, 2010
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010|+sep=;
		// |exclude=January 18, 2010;January 25, 2010|+sep=;
		// }}
		$provider[] = array(
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
		);

		return $provider;
	}

}
