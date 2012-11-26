<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\MinkExtension\Context\MinkContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

$sDirRoot = dirname(realpath((dirname(__FILE__)) . "/../../../"));
set_include_path(get_include_path().PATH_SEPARATOR.$sDirRoot);

require_once("tests/LoadFixtures.php");

/**
 * LiveStreet custom feature context
 */
class BaseFeatureContext extends BehatContext
{

    protected $fixturesLoader = null;
    protected $oEngine = NULL;

    public function __construct()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    public function getEngine()
    {
        return $this->oEngine;
    }

    public function initEngine()
    {
        if(!$this->oEngine) {
            $this->oEngine = Engine::getInstance();
            $this->oEngine->Init();
        }
    }

    /**
     * Get fixtures loader
     * @return LoadFixtures
     */
    protected function getFixturesLoader()
    {
        if (is_null($this->fixturesLoader)) {
            $this->fixturesLoader = new LoadFixtures($this->getEngine());
        }

        return $this->fixturesLoader;
    }

    public function getMinkContext()
    {
        return $this->getMainContext();
    }

    /**
     * Purge DB and load fixtures before running each test
     *
     * @BeforeScenario
     */
    public function prepare($event)
    {
        $this->initEngine();
        $fixturesLoader = $this->getFixturesLoader();
        $fixturesLoader->purgeDB();
        $fixturesLoader->load();
    }

    /**
     * Loading fixture for plugin
     *
     * @Given /^I load fixtures for plugin "([^"]*)"$/
     */
    public function loadFixturesForPlugin($plugin)
    {
        $fixturesLoader = $this->getFixturesLoader();
        $fixturesLoader->loadPluginFixtures($plugin);
    }

    /**
     * @Then /^I wait "([^"]*)"$/
     */
    public function iWait($time_wait)
    {
        $this->getMinkContext()->getSession()->wait($time_wait);
    }

    /**
     * Check is sets are present in content
     *
     * @Then /^the response have sets:$/
     */
    public function ResponseHaveSets( $table)
    {
        $actual = $this->getMinkContext()->getSession()->getPage()->getContent();

        foreach ($table->getHash() as $genreHash) {
            $regex  = '/'.preg_quote($genreHash['value'], '/').'/ui';
            if (!preg_match($regex, $actual)) {
                $message = sprintf('The string "%s" was not found anywhere in the HTML response of the current page.', $genreHash['value']);
                throw new ExpectationException($message, $this->getMinkContext()->getSession());
            }
        }
    }

    /**
     * @Then /^I should see in element "([^"]*)" values:$/
     */
    public function iShouldSeeInContainerValues($objectId, TableNode $table)
    {
        $element = $this->getMinkContext()->getSession()->getPage()->find('css', "#{$objectId}");

        if ($element) {
            $content = $element->getHtml();

            foreach ($table->getHash() as $genreHash) {
                $regex  = '/'.preg_quote($genreHash['value'], '/').'/ui';
                if (!preg_match($regex, $content)) {
                    $message = sprintf('The string "%s" was not found anywhere in container', $genreHash['value']);
                    throw new ExpectationException($message, $this->getMinkContext()->getSession());
                }
            }
        }
        else {
            throw new ExpectationException('Container not found', $this->getMinkContext()->getSession());
        }
    }


    /**
     * Get content type and compare with set
     *
     * @Then /^content type is "([^"]*)"$/
     */
    public function contentTypeIs($contentType)
    {
        $header = $this->getMinkContext()->getSession()->getResponseHeaders();

        if ($contentType != $header['Content-Type']) {
            $message = sprintf('Current content type is "%s", but "%s" expected.', $header['Content-Type'], $contentType);
            throw new ExpectationException($message, $this->getMinkContext()->getSession());
        }
    }

    /**
     * Try to login user
     *
     * @Then /^I want to login as "([^"]*)"$/
     */
    public function iWantToLoginAs($sUserLogin)
    {
        $moduleUser = $this->getEngine()->GetModuleObject('ModuleUser');

        $oUser = $moduleUser->GetUserByLogin($sUserLogin);
        if (!$oUser) {
            throw new ExpectationException( sprintf('User %s not found', $sUserLogin), $this->getMinkContext()->getSession());
        }

        $moduleUser->Authorization($oUser, true);
        $sSessionKey = $moduleUser->GetSessionByUserId($oUser->getId())->getKey();

        $this->getMinkContext()->getSession()->getDriver()->setCookie("key", $sSessionKey);
    }

    /**
     * Checking for activity of plugin
     *
     * @Then /^check is plugin active "([^"]*)"$/
     */
    public function CheckIsPluginActive($sPluginName)
    {
        $activePlugins = $this->getEngine()->Plugin_GetActivePlugins();

        if (!in_array($sPluginName, $activePlugins)) {
            throw new ExpectationException( sprintf('Plugin %s is not active', $sPluginName), $this->getMinkContext()->getSession());
        }
    }


}
