<?php
/**
 *
 * Template test to be filled with PHP using Selenium, e.g., IDE:
 * @author b-kaempgen
 *
 */
class BuiltInTypeString extends SeleniumTestCase {

	/**
	 * Done up-front for setup and not testing
	 */
	public function testSetup()
	{
		$this->open("/bka/SeleniumSMW_AUT/SeleniumSMW/index.php/Main_Page");
		$this->type("searchInput", "ABoldString");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=ABoldString");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[TestAnyString::This is a longer text with some '''bold''' characters| ]] __SHOWFACTBOX__");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isElementPresent("link=This is a longer text with some bold characters"));
		
		$this->click("link=TestAnyString");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[Has type::String]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
	}

	/**
	 * Each of these methods...
	 * * should have one assertion.
	 * * should start with test, e.g., testTest().
	 */
	public function testTest()
	{
		$this->open("/bka/SeleniumSMW_AUT/SeleniumSMW/index.php/ABoldString");

			$this->assertTrue($this->isTextPresent("This is a longer text with some bold characters"));
		

			$this->assertFalse($this->isElementPresent("link=This is a longer text with some bold characters"));
		
	}

	/**
	 * Done at the end to purge the test data.
	 */
	public function testTeardown()
	{
		$this->open("/bka/SeleniumSMW_AUT/SeleniumSMW/index.php/ABoldString");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "Property:TestAnyString");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("10000");
	}
}
