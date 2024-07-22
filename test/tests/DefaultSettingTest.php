<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../i18n.php';
require_once 'func.php';

final class DefaultSettingTest extends TestCase
{
    protected $i18n;

    protected function setUp(): void {
        $this->i18n = new i18n();
        $this->i18n->init();
    }

    public function testDefaultSetting()
    {
        $L1 = LangManager::getInstance('en-US');
        $this->assertEquals("Hello, World!", $L1->t('greeting'));

        $L2 = LangManager::getInstance('zh-CN');
        $this->assertEquals("世界，你好！", $L2->t('greeting'));

        rrmdir($this->i18n->getCachePath());
    }

    public function testSection()
    {
        $L1 = LangManager::getInstance('en-US');
        $this->assertEquals("Item 1", $L1->t('menu_item1'));

        $L2 = LangManager::getInstance('zh-CN');
        $this->assertEquals("菜单 2", $L2->t('menu_item2'));

        rrmdir($this->i18n->getCachePath());
    }
}