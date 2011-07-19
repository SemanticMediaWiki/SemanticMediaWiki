<?php
/*
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class MakePropertySubpropertyOfAnotherOneTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "Property:TeamMemberTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=exact:Property:TeamMemberTest");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "This describes team members of a project.");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Property:TeamLeaderTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=exact:Property:TeamLeaderTest");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[subproperty of::Property:TeamMemberTest]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Chris");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Chris");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[TeamMemberTest::Subproperty Project]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Ben");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Ben");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[TeamLeaderTest::Subproperty Project]]\n__SHOWFACTBOX__");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function test_factbox_MakePropertySubpropertyOfAnotherOne()
	{
		$this->open($this->getUrl() ."index.php/Ben");

			$this->assertTrue($this->isElementPresent("link=TeamLeaderTest"));
		

			$this->assertFalse($this->isElementPresent("link=TeamMemberTest"));
		
	}

	public function test_semanticsearch_MakePropertySubpropertyOfAnotherOne()
	{
		$this->open($this->getUrl() ."index.php/Special:Ask");
		$this->type("q", "[[TeamMemberTest::Subproperty Project]]");
		$this->click("//input[@value='Find results']");
		$this->waitForPageToLoad("30000");

			$this->assertTrue($this->isElementPresent("link=Ben"));
		
	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/Ben");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Chris");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Property:TeamLeaderTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Property:TeamMemberTest");
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
