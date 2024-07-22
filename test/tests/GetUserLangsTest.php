<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../i18n.php';
require_once 'func.php';

class GetUserLangsTest extends TestCase
{
    private $backupGet;
    private $backupSession;
    private $backupServer;
    private $backupCookie;

    protected function setUp(): void
    {
        // Backup globals
        $this->backupGet = $_GET;
        $this->backupSession = $_SESSION;
        $this->backupServer = $_SERVER;
        $this->backupCookie = $_COOKIE;
    }

    protected function tearDown(): void
    {
        // Restore globals
        $_GET = $this->backupGet;
        $_SESSION = $this->backupSession;
        $_SERVER = $this->backupServer;
        $_COOKIE = $this->backupCookie;
    }

    public function testGetUserLangsWithForcedLang()
    {
        $i18n = new i18n();
        $i18n->setLangVariantEnabled(false);
        $i18n->setForcedLang('es');
        $result = $i18n->getUserLangs();
        
        $this->assertEquals(['es', 'en'], $result);
    }

    public function testGetUserLangsWithHttpCurrentLanguageHeader()
    {
        $i18n = new i18n();
        $i18n->setLangVariantEnabled(false);
        $_SERVER['HTTP_CURRENT_LANGUAGE'] = 'de';
        $result = $i18n->getUserLangs();
        
        $this->assertEquals(['de', 'en'], $result);
    }

    public function testGetUserLangsWithCurrentLanguageHeader()
    {
        $i18n = new i18n();
        $i18n->setLangVariantEnabled(false);
        $_SERVER['CURRENT_LANGUAGE'] = 'de';
        $result = $i18n->getUserLangs();
        
        $this->assertEquals(['de', 'en'], $result);
    }

    public function testGetUserLangsWithGetParameter()
    {
        $i18n = new i18n();
        $i18n->setLangVariantEnabled(false);
        $_GET['lang'] = 'fr';
        $result = $i18n->getUserLangs();
        $this->assertEquals(['fr', 'en'], $result);
    }

    public function testGetUserLangsWithSessionParameter()
    {
        $i18n = new i18n();
        $i18n->setLangVariantEnabled(false);
        $_SESSION['lang'] = 'it';
        $result = $i18n->getUserLangs();
        $this->assertEquals(['it', 'en'], $result);
    }

    public function testGetUserLangsWithHttpAcceptLanguage()
    {
        $i18n = new i18n();
        $i18n->setLangVariantEnabled(false);
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'nl,fr;q=0.8,en-us;q=0.5,en;q=0.3';
        $result = $i18n->getUserLangs();
        $this->assertEquals(['nl', 'fr', 'en'], $result);
    }

    public function testGetUserLangsWithCookieLang()
    {
        $i18n = new i18n();
        $i18n->setLangVariantEnabled(false);
        $_COOKIE['lang'] = 'pt';
        $result = $i18n->getUserLangs();
        $this->assertEquals(['pt', 'en'], $result);
    }

    public function testGetUserLangsWithAllSources()
    {
        $i18n = new i18n();
        $i18n->setLangVariantEnabled(false);
        $i18n->setForcedLang('ja');
        $_SERVER['HTTP_CURRENT_LANGUAGE'] = 'de';
        $_GET['lang'] = 'fr';
        $_SESSION['lang'] = 'it';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'nl,fr;q=0.8,en-us;q=0.5,en;q=0.3';
        $_COOKIE['lang'] = 'pt';

        $result = $i18n->getUserLangs();
        
        $expected = ['ja', 'de', 'fr', 'it', 'nl', 'en', 'pt'];
        
        $this->assertEquals($expected, $result);
    }

    public function testGetUserLangsWithIllegalEntries()
    {
        $i18n = new i18n();
        $i18n->setLangVariantEnabled(false);
        $_GET['lang'] = 'fr&<>';
        $_SESSION['lang'] = 'it';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'nl,de<>';

        $result = $i18n->getUserLangs();

        $expected = ['it', 'nl', 'en'];
        $this->assertEquals($expected, $result);
    }
}