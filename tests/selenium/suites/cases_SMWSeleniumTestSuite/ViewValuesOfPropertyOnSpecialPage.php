<?php
/**
 *
 * Template test to be filled with PHP using Selenium, e.g., IDE:
 * @author b-kaempgen
 *
 */
class ViewValuesOfPropertyOnSpecialPage extends SeleniumTestCase {

	/**
	 * Done up-front for setup and not testing
	 */
	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "FirstPageAnnotated");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=FirstPageAnnotated");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[CheckValuePP::Or get]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "SecondPageAnnotated");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=SecondPageAnnotated");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[CheckValuePP::Get one value]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "ThirdPageAnnotated");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=ThirdPageAnnotated");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[CheckValuePP::Three values]]");
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
		$this->open($this->getUrl() ."index.php/Special:PageProperty");
		$this->type("from", "SecondPageAnnotated");
		$this->type("type", "CheckValuePP");
		$this->click("//input[@value='Find results']");
		$this->waitForPageToLoad("10000");

			$this->assertEquals("SecondPageAnnotated CheckValuePP", $this->getText("firstHeading"));
		

			$this->assertTrue($this->isElementPresent("link=Get one value"));
		

			$this->assertFalse($this->isElementPresent("link=Or get"));
		
		$this->type("from", "");
		$this->type("type", "");
		$this->click("//input[@value='Find results']");
		$this->waitForPageToLoad("10000");
	}

	public function testTest2()
	{
		$this->open($this->getUrl() ."index.php/Special:PageProperty");
		$this->type("type", "CheckValuePP");
		$this->click("//input[@value='Find results']");
		$this->waitForPageToLoad("10000");

			$this->assertEquals("CheckValuePP", $this->getText("firstHeading"));
		

			$this->assertTrue($this->isElementPresent("link=Get one value"));
		

			$this->assertTrue($this->isElementPresent("link=Or get"));
		

			$this->assertTrue($this->isElementPresent("link=Three values"));
		
		$this->type("from", "");
		$this->type("type", "");
		$this->click("//input[@value='Find results']");
		$this->waitForPageToLoad("10000");
	}

	/**
	 * Done at the end to purge the test data.
	 */
	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/FirstPageAnnotated");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "SecondPageAnnotated");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "ThirdPageAnnotated");
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
