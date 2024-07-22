<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../i18n.php';
require_once 'func.php';

final class ChangeSettingTest extends TestCase
{
    public function testChangesetCachePath()
    {
        $i18n = new i18n();
        $i18n->setCachePath('/tmp/langcache');
        $this->assertEquals('/tmp/langcache', $i18n->getCachePath());
    }

    public function testMergeFallbackTranslation()
    {
        $i18n = new i18n();
        $i18n->setMergeFallback(true);
        $i18n->init();
        $L = LangManager::getInstance('zh-CN');
        $this->assertEquals("世界，你好！", $L->t('greeting'));
        $this->assertEquals("Goodbye, World!", $L->t('farewell'));

        rrmdir($i18n->getCachePath());
    }
}