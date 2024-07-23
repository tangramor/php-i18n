<?php

/*
 * Fork this project on GitHub!
 * https://github.com/tangramor/php-i18n
 *
 * License: MIT
 */

// $cachePath;

class i18n {

    /**
     * Language file path
     * This is the path for the language files. You must use the '{LANGUAGE}' placeholder for the language or the script wont find any language files.
     *
     * @var string
     */
    protected $filePath = './lang/{LANGUAGE}.ini';

    /**
     * Cache file path
     * This is the path for all the cache files. Best is an empty directory with no other files in it.
     *
     * @var string
     */
    protected $cachePath = './langcache/';

    /**
     * Enable region variants
     * Allow region variants such as "en-us", "en-gb" etc. If set to false, "en" will be provided.
     * Defaults to true.
     *
     * @var bool
     */
    protected $isLangVariantEnabled = true;

    /**
     * Fallback language
     * This is the language which is used when there is no language file for all other user languages. It has the lowest priority.
     * Remember to create a language file for the fallback!!
     *
     * @var string
     */
    protected $fallbackLang = 'en-US';

    /**
     * Merge in fallback language
     * Whether to merge current language's strings with the strings of the fallback language ($fallbackLang).
     *
     * @var bool
     */
    protected $mergeFallback = false;

    /**
     * Forced language
     * If you want to force a specific language define it here.
     *
     * @var string
     */
    protected $forcedLang = NULL;

    /**
     * This is the separator used if you use sections in your ini-file.
     * For example, if you have a string 'greeting' in a section 'welcomepage' you will can access it via 'L::welcomepage_greeting'.
     * If you changed it to 'ABC' you could access your string via 'L::welcomepageABCgreeting'
     *
     * @var string
     */
    protected $sectionSeparator = '_';


    /*
     * The following properties are only available after calling init().
     */

    /**
     * User languages
     * These are the languages the user uses.
     * Normally, if you use the getUserLangs-method this array will be filled in like this:
     * 1. Forced language
     * 2. HTTP header 'current_language'
     * 3. Language in $_GET['lang']
     * 4. Language in $_SESSION['lang']
     * 5. HTTP_ACCEPT_LANGUAGE
     * 6. Language in $_COOKIE['lang']
     * 7. Fallback language
     *
     * @var array
     */
    protected $userLangs = array();

    /**
     * Translation languages
     * These are the languages which are available in the ini-files.
     *
     * @var array
     */
    protected $langs = array();

    protected $appliedLang = NULL;
    protected $langFilePath = NULL;
    protected $cacheFilePath = NULL;
    protected $isInitialized = false;


    /**
     * Constructor
     * The constructor sets all important settings. All params are optional, you can set the options via extra functions too.
     *
     * @param string [$filePath] This is the path for the language files. You must use the '{LANGUAGE}' placeholder for the language.
     * @param string [$cachePath] This is the path for all the cache files. Best is an empty directory with no other files in it. No placeholders.
     * @param string [$fallbackLang] This is the language which is used when there is no language file for all other user languages. It has the lowest priority.
     */
    public function __construct($filePath = NULL, $cachePath = NULL, $fallbackLang = NULL) {
        // Apply settings
        if ($filePath != NULL) {
            $this->filePath = $filePath;
        }
        
        if ($cachePath != NULL) {
            $this->cachePath = $cachePath;
        }

        if ($fallbackLang != NULL) {
            $this->fallbackLang = $fallbackLang;
        }
    }

    public function getTranslations($filePath) {
        $i18nPath = dirname($filePath);
        $translations = [];
        $i18nFiles = array_diff(scandir($i18nPath), [".", ".."]);
        foreach ($i18nFiles as $key => $value) {
            $langCode = pathinfo($value, PATHINFO_FILENAME);
            $className = 'Lang' . ucfirst(str_replace('-', '', $langCode)); //Class name is like 'LangEnUS'
            $translations[$langCode] = $className;
        }
        return $translations;
    }

    public function init() {     
        $this->langs = $this->getTranslations($this->filePath);

        global $cachePath;
        $cachePath = $this->cachePath;

        $this->isInitialized = true;

        $this->userLangs = $this->getUserLangs();
        
        // search for language file
        $this->appliedLang = NULL;
        foreach ($this->userLangs as $priority => $langcode) {
            $this->langFilePath = $this->getConfigFilename($langcode);

            if (file_exists($this->langFilePath)) {
                $this->appliedLang = $langcode;
                break;
            }
        }
        if ($this->appliedLang == NULL) {
            throw new RuntimeException('No language file was found.');
        }

        if( ! is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }

        foreach ($this->langs as $lang => $langClass) {
            $this->cacheFilePath = $this->cachePath . '/php_i18n_' . md5_file(__FILE__) . '_' . $lang . '.cache.php';
            $langFilePath = $this->getConfigFilename($lang);
            // whether we need to create a new cache file
            $outdated = !file_exists($this->cacheFilePath) || filemtime($this->cacheFilePath) < filemtime($langFilePath) || // the language config was updated
            ($this->mergeFallback && filemtime($this->cacheFilePath) < filemtime($this->getConfigFilename($this->fallbackLang))); // the fallback language config was updated

            if ($outdated) {
                $config = $this->load($langFilePath);
                if ($this->mergeFallback)
                    $config = array_replace_recursive($this->load($this->getConfigFilename($this->fallbackLang)), $config);

                $compiled = "<?php class " . $langClass . " {\n"
                    . $this->compile($config)
                    . 'public static function translate($string, $args) {' . "\n"
                    . '    $return = constant("self::".$string);'."\n"
                    . '    return $args ? vsprintf($return, $args) : $return;'
                    . "\n}\n}\n";

                if (file_put_contents($this->cacheFilePath, $compiled) === FALSE) {
                    throw new Exception("Could not write cache file to path '" . $this->cacheFilePath . "'. Is it writable?");
                }
                chmod($this->cacheFilePath, 0755);
            }

            require_once $this->cacheFilePath;
        }

    }

    public function isInitialized() {
        return $this->isInitialized;
    }

    public function getAppliedLang() {
        return $this->appliedLang;
    }

    public function getCachePath() {
        return $this->cachePath;
    }

    public function getLangVariantEnabled() {
        return $this->isLangVariantEnabled;
    }

    public function getForcedLang() {
        return $this->forcedLang;
    }

    public function getFallbackLang() {
        return $this->fallbackLang;
    }

    public function setFilePath($filePath) {
        $this->filePath = $filePath;
    }

    public function setCachePath($cachePath) {
        $this->cachePath = $cachePath;
    }

    public function setLangVariantEnabled($isLangVariantEnabled) {

        $this->isLangVariantEnabled = $isLangVariantEnabled;
    }

    public function setFallbackLang($fallbackLang) {
        $this->fallbackLang = $fallbackLang;
    }

    public function setMergeFallback($mergeFallback) {
        $this->mergeFallback = $mergeFallback;
    }

    public function setForcedLang($forcedLang) {
        $this->forcedLang = $forcedLang;
    }

    public function setSectionSeparator($sectionSeparator) {
        $this->sectionSeparator = $sectionSeparator;
    }

    /**
     * @deprecated Use setSectionSeparator.
     */
    public function setSectionSeperator($sectionSeparator) {
        $this->setSectionSeparator($sectionSeparator);
    }

    /**
     * getUserLangs()
     * Returns the user languages
     * Normally it returns an array like this:
     * 1. Forced language
     * 2. HTTP header 'current_language'
     * 3. Language in $_GET['lang']
     * 4. Language in $_SESSION['lang']
     * 5. HTTP_ACCEPT_LANGUAGE
     * 6. Language in $_COOKIE['lang']
     * 7. Fallback language
     * Note: duplicate values are deleted.
     *
     * @return array with the user languages sorted by priority.
     */
    public function getUserLangs() {
        $userLangs = array();

        // Highest priority: forced language
        if ($this->forcedLang != NULL) {
            $userLang = $this->forcedLang;
            
            if (!$this->isLangVariantEnabled) {
                $userLang = explode('-', $userLang)[0];
            }

            $userLangs[] = $userLang;
        }

        // 2nd highest priority: HTTP header 'current_language'
        if (!function_exists('apache_request_headers')) {
            eval('
                function apache_request_headers() {
                    $out = [];
                    foreach($_SERVER as $key=>$value) {
                        if (substr($key,0,5)=="HTTP_" || substr($key,0,5)=="http_") {
                            $key=strtolower(substr($key,5));
                            $out[$key]=$value;
                        } else if (strtolower($key) == "current_language") {
                            $out["current_language"]=$value;
                        }
                    }
                    return $out; 
                } 
            ');
        }
        $headers = apache_request_headers();
        if (isset($headers['current_language']) && is_string($headers['current_language'])) {
            $userLang = $headers['current_language'];
            if (!$this->isLangVariantEnabled) {
                $userLang = explode('-', $userLang)[0];
            }

            $userLangs[] = $userLang;
        }

        
        // 3rd highest priority: GET parameter 'lang'
        if (isset($_GET['lang']) && is_string($_GET['lang'])) {
            $userLang = $_GET['lang'];
            if (!$this->isLangVariantEnabled) {
                $userLang = explode('-', $userLang)[0];
            }
            $userLangs[] = $userLang;
        }

        // 4th highest priority: SESSION parameter 'lang'
        if (isset($_SESSION['lang']) && is_string($_SESSION['lang'])) {
            $userLang = $_SESSION['lang'];
            if (!$this->isLangVariantEnabled) {
                $userLang = explode('-', $userLang)[0];
            }

            $userLangs[] = $userLang;
        }
    
        // 5th highest priority: HTTP_ACCEPT_LANGUAGE
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $part) {
                $userLang = strtolower(explode(';q=', $part)[0]);

                // Trim language variant section if not configured to allow
                if (!$this->isLangVariantEnabled) {
                    $userLang = explode('-', $userLang)[0];
                }
                $userLangs[] = $userLang;
            }
        }

        // 6th highest priority: COOKIE
        if (isset($_COOKIE['lang'])) {
            $userLang = $_COOKIE['lang'];
            if (!$this->isLangVariantEnabled) {
                $userLang = explode('-', $userLang)[0];
            }
            $userLangs[] = $userLang;
        }

        // Lowest priority: fallback
        $userLang = $this->fallbackLang;
        if (!$this->isLangVariantEnabled) {
            $userLang = explode('-', $userLang)[0];
        }
        $userLangs[] = $userLang;

        // remove duplicate elements
        $userLangs = array_values(array_unique($userLangs));

        // remove illegal userLangs
        $userLangs2 = array();
        foreach ($userLangs as $key => $value) {
            // only allow a-z, A-Z and 0-9 and _ and -
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $value) === 1)
                $userLangs2[$key] = $value;
        }

        return array_values($userLangs2);
    }

    protected function getConfigFilename($langcode) {
        return str_replace('{LANGUAGE}', $langcode, $this->filePath);
    }

    protected function load($filename) {
        $ext = substr(strrchr($filename, '.'), 1);
        switch ($ext) {
            case 'properties':
            case 'ini':
                $config = parse_ini_file($filename, true);
                break;
            // case 'yml':
            // case 'yaml':
            //     $config = spyc_load_file($filename);
            //     break;
            case 'json':
                $config = json_decode(file_get_contents($filename), true);
                break;
            default:
                throw new InvalidArgumentException($ext . " is not a valid extension!");
        }
        return $config;
    }

    /**
     * Recursively compile an associative array to PHP code.
     */
    protected function compile($config, $prefix = '') {
        $code = '';
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $code .= $this->compile($value,  $prefix . $key . $this->sectionSeparator);
            } else {
                $fullName = $prefix . $key;
                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $fullName)) {
                    throw new InvalidArgumentException(__CLASS__ . ": Cannot compile translation key " . $fullName . " because it is not a valid PHP identifier.");
                }
                $code .= 'const ' . $fullName . ' = \'' . str_replace('\'', '\\\'', $value) . "';\n";
            }
        }
        return $code;
    }

    protected function fail_after_init() {
        if ($this->isInitialized()) {
            throw new BadMethodCallException('This ' . __CLASS__ . ' object is already initalized, so you can not change any settings.');
        }
    }
}


class LangManager {
    private $langClass;
    private static $instance;

    private function __construct($lang) {
        $this->langClass = $lang;
    }

    public static function getInstance($lang = 'en-US') {
        if (self::$instance === null || self::$instance->langClass !== $lang) {
            global $cachePath;
            $cacheFilePath = $cachePath . '/php_i18n_' . md5_file(__FILE__) . '_' . $lang . '.cache.php';
            require_once $cacheFilePath;
            $className = "Lang" . ucfirst(str_replace('-', '', $lang));
            self::$instance = new self($className);
        }
        return self::$instance;
    }

    public function translate($name, $args = null) {
        $class = $this->langClass;
        return $class::translate($name, $args);
    }

    public function t($name, $args = null) {
        return $this->translate($name, $args);
    }
}