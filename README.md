# PHP i18n Library

A simple and efficient internationalization (i18n) library for PHP that allows you to manage multiple languages in your application.

This project is inspired by the **[i18n library](https://github.com/Philipp15b/php-i18n)** by **Philipp Schröer**. The original library cannot switch languages dynamically during runtime, which is a limitation. This fork aims to address this issue and provide a more flexible solution.

## Features

- Supports language variants like "en-US", "zh-CN", etc.
- Automatic detection of user's language preference.
- Fallback language support to ensure all strings have a default translation.
- Caching mechanism to improve performance.
- Change language dynamically during runtime.

## Installation

You can install the library via [Composer](https://getcomposer.org/):

```bash
composer require tangramor/php-i18n
```

Or you can download the `i18n.php` file and include it in your project.

## How to use this lib

### Configuration

Language files should be placed in the specified `filePath` directory and follow the naming convention `{LANGUAGE}.ini`. For example, `en-US.ini` for American English.

This fork disabled YAML format and only support **INI** format. Because to support YAML, we need to add a dependency "mustangostang/spyc" for YAML, which is not necessary for this project.

#### Language File Example (`en-US.ini`)

```ini
greeting = "Hello, World!"

[category]
somethingother = "Something other..."
```

### Useage

1. **Initialization**: Create an instance of the `i18n` class and initialize it with your desired settings.

```php
$i18n = new i18n();
$i18n->init();  //use default settings
```

2. **Set Language Preferences**: Optionally, you can set a forced language or a fallback language.

```php
$i18n->setForcedLang('zh-CN');
// or
$i18n->setFallbackLang('en-US');

$i18n->init();
```
3. **Other Settings**: You can also set other settings like the file path, cache path, etc.

```php
$i18n->setCachePath('/tmp/langcache');  //Cache file path (default: ./langcache/)
$i18n->setFilePath('/app/lang/{LANGUAGE}.ini'); // language file path (default: ./lang/{LANGUAGE}.ini)
$i18n->setMergeFallback(false); // make keys available from the fallback language (default: en-US)
$i18n->setLangVariantEnabled(false);    //Allow region variants such as "en-us", "en-gb" etc. If set to false, "en" will be provided. (default: true)
$i18n->setSectionSeparator('_');    //this is used to seperate the sections in the language class. If you set the separator to _abc_ you could access your localized strings via $L->t('category_abc_stringname') if you use categories in your ini. (default: _)

$i18n->init();
```

4. **Translate**: Use the `translate` method of `LangManager` class to get the translated string.

```php
$L = LangManager::getInstance($i18n->getAppliedLang());
echo $L->translate('greeting');
//or use short form
echo $L->t('greeting');
// Output: Hello, World!

echo $L->t('category_somethingother');
// Output: Something other...
```

You can change the translation language dynamically during runtime by calling `LangManager::getInstance($otherLang)`.


## Caching

The library uses a caching mechanism to store compiled language files. Ensure the `cachePath` directory is writable.

## How the user language detection works

This class tries to detect the user's language by trying the following sources in this order:

1. Forced language (if set)
2. HTTP header 'CURRENT_LANGUAGE'
3. GET parameter 'lang' (`$_GET['lang']`)
4. SESSION parameter 'lang' (`$_SESSION['lang']`)
5. HTTP_ACCEPT_LANGUAGE (can be multiple languages) (`$_SERVER['HTTP_ACCEPT_LANGUAGE']`)
6. COOKIE stored variable 'lang' (`$_COOKIE['lang']`)
7. Fallback language

php-i18n will remove all characters that are not one of the following: A-Z, a-z or 0-9 to prevent [arbitrary file inclusion](https://en.wikipedia.org/wiki/File_inclusion_vulnerability). After that the class searches for the language files. For example, if you set the GET parameter 'lang' to 'en-US' without a forced language set, the class would try to find the file `lang/en-US.ini` (if the setting `langFilePath` was set to default (`lang/{LANGUAGE}.ini`)). If this file doesn't exist, php-i18n will try to find the language file for the language defined in the session variable and so on.

### Example for 'CURRENT_LANGUAGE' in javascript

You can set the 'CURRENT_LANGUAGE' header in your axios request to pass the language to the server.

Following example intercepts the axios request to add the 'CURRENT_LANGUAGE' header, you can change it to your own way.

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

### How to change this implementation

You can change the user detection by extending the i18n class and overriding the getUserLangs() method:

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

This very basic extension only uses the GET parameter 'language' and the session parameter 'userlanguage'. You see that this method must return an array.


## Test

To run the tests, you need to install the `phpunit/phpunit` package under `test` folder.

```bash
cd test
composer install
# or
composer require --dev phpunit/phpunit
```

Then run the tests with the following command under `test` folder:

```bash
./vendor/bin/phpunit --testdox tests
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to fork the project and submit pull requests.

## Contact

For any questions or issues, please open an issue or contact the author directly.