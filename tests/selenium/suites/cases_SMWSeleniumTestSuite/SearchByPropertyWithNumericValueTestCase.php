<?php
/*
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class SearchByPropertyWithNumericValueTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "Property:NumericTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=exact:Property:NumericTest");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[Has type::Number]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "NumericTestPage1");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=NumericTestPage1");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[NumericTest::123]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "NumericTestPage2");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=NumericTestPage2");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[NumericTest::234]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "NumericTestPage3");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=NumericTestPage3");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[NumericTest::345]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function testTest()
	{
		$this->open($this->getUrl() ."index.php/Special:SearchByProperty");
		$this->type("property", "NumericTest");
		$this->type("value", "123");
		$this->click("//input[@value='Find results']");
		$this->waitForPageToLoad("30000");

			$this->assertEquals("NumericTest 123", $this->getText("firstHeading"));
		

			$this->assertTrue($this->isElementPresent("link=NumericTestPage1"));
		

			$this->assertTrue($this->isElementPresent("link=NumericTestPage2"));
		

			$this->assertTrue($this->isElementPresent("link=NumericTestPage3"));
		

			$this->assertTrue($this->isTextPresent("345"));
		

			$this->assertEquals("NumericTest", $this->getValue("property"));
		

			$this->assertEquals("123", $this->getValue("value"));
		
	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/NumericTestPage1");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "NumericTestPage2");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "NumericTestPage3");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Property:NumericTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("30000");
	}
}
