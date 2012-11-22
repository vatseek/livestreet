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

    protected static $fixturesLoader = null;

    /**
     * Get fixtures loader
     * @return LoadFixtures
     */
    protected static function getFixturesLoader()
    {
        if (is_null(self::$fixturesLoader)) {
            self::$fixturesLoader = new LoadFixtures();
        }

        return self::$fixturesLoader;
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
    public static function prepare($event){
        $fixturesLoader = self::getFixturesLoader();
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
     * @Given /^I am activated plugin "([^"]*)"$/
     */
    public function ActivatedPlugin($sPlugin)
    {
        $pluginActivation =  new LoadFixtures();
        $pluginActivation->activationPlugin($sPlugin);
    }

    /**
     * @Given /^I am deactivate plugin "([^"]*)"$/
     */
    public function DeactivatedPlugin($sPlugin)
    {
        $pluginActivation =  new LoadFixtures();
        $pluginActivation->deactivatePlugin($sPlugin);
    }

    /**
     * @Then /^I wait "([^"]*)"$/
     */
    public function iWait($time_wait)
    {
        $this->getMinkContext()->getSession()->wait($time_wait);
    }


    /**
     * @Then /^I want to login administrator$/
     */
    public function iWantToLoginAdministrator()
    {
        // Заполняем форму
        $this->getMinkContext()->getSession()->getPage()->findById("login")->setValue("admin@admin.adm");
        $this->getMinkContext()->getSession()->getPage()->findById("password")->setValue("qwerty");

        // Сабмитим форму
        $this->pressButton("login-form-submit");
    }

}
