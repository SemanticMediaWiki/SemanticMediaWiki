<?php
/*
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class SelectPagesByPropertyValue extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "TestPageABCDEF");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=TestPageABCDEF");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[ThreeLetters::ABC]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "TestPageCDEFGH");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=TestPageCDEFGH");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[ThreeLetters::ABC]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "TestPageEFGHIJ");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=TestPageEFGHIJ");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[ThreeLetters::GHI]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
	}

	public function testTest()
	{
		$this->open($this->getUrl() ."index.php/Special:Ask");
		$this->type("q", "[[ThreeLetters::ABC]]");
		$this->click("//input[@value='Find results']");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isTextPresent("TestPageABCDEF"));
		

			$this->assertTrue($this->isTextPresent("TestPageCDEFGH"));
		

			$this->assertFalse($this->isTextPresent("TestPageEFGHIJ"));
		
	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "TestPageABCDEF");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "TestPageCDEFGH");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "TestPageEFGHIJ");
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
