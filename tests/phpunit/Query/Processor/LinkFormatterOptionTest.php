<?php

namespace SMW\Tests\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\LinkFormatterOption;

class LinkFormatterOptionTest extends TestCase {

    /**
     * Test the addPrintRequestHandleParams method
     */
    public function testAddPrintRequestHandleParamsWithHashInLabel() {
        $formatter = new LinkFormatterOption();

        // Test case 1: Previous printout exists, with '#' in the label
        $serialization = [
            'printouts' => [
                'Main Image' => [
                    'label' => 'Main Image #40px'
                ],
            ],
        ];
        $result = $formatter->addPrintRequestHandleParams( 'Main Image', '+link=', 'Main Image', $serialization );

        $expectedSerialization = [
            'printouts' => [
                'Main Image' => [
                    'label' => 'Main Image #40px;link', 
                    'params' => []
                ],
            ],
        ];
        $this->assertEquals( $expectedSerialization, $result['serialization'] );
    }
    
    /**
     * Test the addPrintRequestHandleParams method
     */
    public function testAddPrintRequestHandleParamsWithoutHashInLabel() {
        $formatter = new LinkFormatterOption();

        // Test case 2: Previous printout exists, without '#' in the label
        $serialization = [
            'printouts' => [
                'Job Title' => [
                    'label' => 'Job Title'
                ],
            ],
        ];
        $result = $formatter->addPrintRequestHandleParams( 'Job Title', '+link=', 'Job Title', $serialization );

        $expectedSerialization = [
            'printouts' => [
                'Job Title' => [
                    'label' => 'Job Title #link', 
                    'params' => []
                ],
            ],
        ];
        $this->assertEquals( $expectedSerialization, $result['serialization'] );
    }
    
    /**
     * Test the addPrintRequestHandleParams method
     */
    public function testAddPrintRequestHandleParamsWithMultipleParameters() {
        $formatter = new LinkFormatterOption();
        
        // Test case 3: Previous printout exists, without '#' in the label, more then 3 params in query
        $serialization = [
            'printouts' => [
                'Image' => [
                    'label' => 'Image #40x50px;classunsortable'
                ],
            ],
        ];
        $result = $formatter->addPrintRequestHandleParams( 'Image', '+link=', 'Image', $serialization );

        $expectedSerialization = [
            'printouts' => [
                'Image' => [
                    'label' => 'Image #40x50px;classunsortable;link', 
                    'params' => []
                ],
            ],
        ];
        $this->assertEquals( $expectedSerialization, $result['serialization'] );
    }
}
