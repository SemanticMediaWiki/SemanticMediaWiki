<?php
/**
 *
 * Template test to be filled with PHP using Selenium, e.g., IDE:
 * @author b-kaempgen
 *
 */
class BuildInTypeNumber extends SeleniumTestCase {

	/**
	 * Done up-front for setup and not testing
	 */
	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "Property:AnyNewNumber");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=exact:Property:AnyNewNumber");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[Has type::Number]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "TestAnyNewNumber1");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=TestAnyNewNumber1");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[AnyNewNumber::445000]] __SHOWFACTBOX__");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "TestAnyNewNumber2");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=TestAnyNewNumber2");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[AnyNewNumber::445 000]] __SHOWFACTBOX__");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
	}

	/**
	 * Each of these methods...
	 * * should have one assertion.
	 * * should start with test, e.g., testTest().
	 */
	public function test01()
	{
		$this->open($this->getUrl() ."index.php/TestAnyNewNumber2");

			$this->assertTrue($this->isTextPresent("445,000"));
		
		$this->open($this->getUrl() ."index.php/TestAnyNewNumber1");

			$this->assertTrue($this->isTextPresent("445,000"));
		
	}

	public function test02()
	{
		$this->open($this->getUrl() ."index.php/Special:Ask");
		$this->type("q", "[[AnyNewNumber::445,000]]");
		$this->click("//input[@value='Find results']");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isElementPresent("link=TestAnyNewNumber1"));
		

			$this->assertTrue($this->isElementPresent("link=TestAnyNewNumber2"));
		
	}

	/**
	 * Done at the end to purge the test data.
	 */
	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/TestAnyNewNumber1");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "TestAnyNewNumber2");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "Property:AnyNewNumber");
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
