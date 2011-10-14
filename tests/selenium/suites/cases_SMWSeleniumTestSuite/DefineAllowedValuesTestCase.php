<?php
/**
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class DefineAllowedValuesTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "Property:YesOrNo");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Property:YesOrNo");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[Allows value::Yes]]\n[[Allows value::No]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "YesTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=YesTest");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[YesOrNo::Yes]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "MaybeTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=MaybeTest");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[YesOrNo::Maybe]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function test_checkmaybe_DefineAllowedValues()
	{
		$this->open($this->getUrl() ."index.php/MaybeTest");

			$this->assertFalse($this->isElementPresent("link=Maybe"));

	}

	public function test_checktooltip_DefineAllowedValues()
	{
		$this->open($this->getUrl() ."index.php/MaybeTest");
		$this->click("//div[@id='bodyContent']/p/span/img");

			$this->assertTrue($this->isTextPresent("\"Maybe\" is not in the list of possible values (Yes, No) for this property."));

	}

	public function test_checkyes_DefineAllowedValues()
	{
		$this->open($this->getUrl() ."index.php/YesTest");

			$this->assertEquals("Yes", $this->getText("link=Yes"));

	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/YesTest");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "MaybeTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Property:YesOrNo");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->click("//div[@id='p-logo']/a");
		$this->waitForPageToLoad("30000");
	}
}
