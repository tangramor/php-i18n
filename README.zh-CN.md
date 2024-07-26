# PHP 国际化库

一个简单高效的 PHP 国际化（i18n）库，允许你在应用程序中管理多种语言。

该项目受到 **Philipp Schröer** 的 **[i18n 库](https://github.com/Philipp15b/php-i18n)** 的启发。原始库在运行时不能动态切换语言，这是一个限制。这个分支旨在解决这个问题并提供一个更灵活的解决方案。

如果你不需要动态语言切换功能，你仍然可以使用 **Philipp Schröer** 的原始 **[i18n 库](https://github.com/Philipp15b/php-i18n)**。

## 特性

- 支持语言变体如 "en-US"、"zh-CN" 等。
- 自动检测用户的语言偏好。
- 支持后备语言，以确保所有字符串都有默认翻译。
- 缓存机制以提高性能。
- 在运行时动态更改语言。

## 安装

你可以通过 [Composer](https://getcomposer.org/) 安装库：

```bash
composer require tangramor/php-i18n
```

或者你可以下载 i18n.php 文件并将其包含在你的项目中。

## 如何使用这个库

### 配置

语言文件应该放在指定的 `filePath` 目录中，并遵循命名约定 `{LANGUAGE}.ini`。例如，美国英语为 `en-US.ini`。

这个分支禁用了 YAML 格式，仅支持 **INI** 格式。因为要支持 YAML，我们需要添加依赖 "mustangostang/spyc" 用于 YAML，这对这个项目来说是不必要的。

#### 语言文件示例 (en-US.ini)

```ini
greeting = "Hello, World!"

[category]
somethingother = "Something other..."
```

#### 设置

- 语言文件路径（默认：`./lang/{LANGUAGE}.ini`）
- 缓存文件路径（默认：`./langcache/`）
- 保留语言区域变体：如果设置为 true，语言代码字符串中的区域变体如 en-US 和 en-GB 将被保留，否则将被截断为 en（默认：`true`，注意在原始 i18n 库中这个值默认为 *false* ）
- 后备语言，如果没有用户语言可用（默认：`en-US`，注意在原始 i18n 库中这个值默认为 *en* ）
- 强制语言，如果你想强制使用一种语言（默认：无）
- 节分隔符：这用于分隔语言类中的节。如果你将分隔符设置为 _abc_ ，如果你在你的 ini 中使用了类别，你可以使用 `$L->t('category_abc_stringname')` 访问你的本地化字符串。（默认：`_`）
- 将后备语言的键合并到当前语言中。（默认：`false`）

### 使用方法

#### 初始化：创建 i18n 类的实例并用你希望的设置初始化它。

```php
$i18n = new i18n();
$i18n->init();  //使用默认设置
```

#### 设置语言偏好：可选地，你可以设置强制语言或后备语言。

```php
$i18n->setForcedLang('zh-CN');
// 或
$i18n->setFallbackLang('en-US');

$i18n->init();
```

#### 其他设置：你也可以设置其他设置，如文件路径、缓存路径等。

```php
$i18n->setCachePath('/tmp/langcache');  //缓存文件路径（默认：./langcache/）
$i18n->setFilePath('/app/lang/{LANGUAGE}.ini'); // 语言文件路径（默认：./lang/{LANGUAGE}.ini）
$i18n->setMergeFallback(false); // 使后备语言的键可用（默认：false）
$i18n->setLangVariantEnabled(false);    //允许区域变体如 "en-us", "en-gb" 等。如果设置为 false，将提供 "en"。（默认：true）
$i18n->setSectionSeparator('');    //这用于分隔语言类中的节（默认：）

$i18n->init();
```

#### 翻译：使用 LangManager 类的 translate 方法获取翻译后的字符串。

```php
$L = LangManager::getInstance($i18n->getAppliedLang());

echo $L->translate('greeting');
// 或使用简写形式
echo $L->t('greeting');
// 输出：Hello, World!

echo $L->t('category_somethingother');
// 输出：Something other...
```

你可以在运行时通过调用 `LangManager::getInstance($otherLang)` 动态更改翻译语言。

查看[测试代码](./test/tests/DefaultSettingTest.php)中的示例：

```php
$L1 = LangManager::getInstance('en-US');
$this->assertEquals("Hello, World!", $L1->t('greeting'));

$L2 = LangManager::getInstance('zh-CN');
$this->assertEquals("世界，你好！", $L2->t('greeting'));
```

## 缓存

该库使用缓存机制存储编译后的语言文件。确保缓存路径目录可写。

## 用户语言检测的工作原理

此类尝试通过以下顺序检测用户的语言：

- 强制语言（如果设置）
- HTTP 头 'CURRENT_LANGUAGE'
- GET 参数 'lang'（`$_GET['lang']`）
- SESSION 参数 'lang'（`$_SESSION['lang']`）
- HTTP_ACCEPT_LANGUAGE（可以是多种语言）（`$_SERVER['HTTP_ACCEPT_LANGUAGE']`）
- COOKIE 存储的变量 'lang'（`$_COOKIE['lang']`）
- 后备语言

php-i18n 将删除所有不是以下字符之一的字符：A-Z, a-z 或 0-9，以防止[任意文件包含](https://en.wikipedia.org/wiki/File_inclusion_vulnerability)。之后，该类会搜索语言文件。例如，如果你将 GET 参数 'lang' 设置为 'en-US' 而没有设置强制语言，该类会尝试查找文件 `lang/en-US.ini`（如果设置 `langFilePath` 为默认（`lang/{LANGUAGE}.ini`））。如果这个文件不存在，php-i18n 会尝试查找会话变量中定义的语言文件，依此类推。

### 在 JavaScript 中设置 'CURRENT_LANGUAGE' 的示例

你可以在你的 axios 请求中设置 'CURRENT_LANGUAGE' 头以将语言传递给服务器。

以下示例拦截 axios 请求以添加 'CURRENT_LANGUAGE' 头，你可以将其更改为你自己的方式。

```js
import axios from 'axios';
import i18n from './i18n';

axios.interceptors.request.use(function (config) {
    config.headers['CURRENT_LANGUAGE'] = i18n.locale;
    return config
}, function (error) {
    return Promise.reject(error);
});
```

### 如何更改此实现

你可以通过扩展 i18n 类并覆盖 `getUserLangs()` 方法来更改用户检测：

```php
<?php
	require_once 'i18n.php';
	class My_i18n extends i18n {

		public function getUserLangs() {
			$userLangs = new array();

			$userLangs[] = $_GET['language'];

			$userLangs[] = $_SESSION['userlanguage'];

			return $userLangs;
		}

	}

	$i18n = new My_i18n();
	// [...]
?>
```

这个非常基本的扩展仅使用 GET 参数 'language' 和会话参数 'userlanguage'。你可以看到这个方法必须返回一个数组。

## 测试

要运行测试，你需要在测试文件夹下安装 `phpunit/phpunit` 包。

```bash
cd test
composer install

#或

composer require --dev phpunit/phpunit
```

然后在测试文件夹下运行以下命令进行测试：

```bash
./vendor/bin/phpunit --testdox tests
```

## 许可证

该项目根据 MIT 许可证授权 - 详情请参阅 [LICENSE](LICENSE) 文件。

## 贡献

欢迎贡献！请随时 fork 项目并提交 pull requests。

## 联系方式

如有任何问题或问题，请打开问题或直接联系作者。
