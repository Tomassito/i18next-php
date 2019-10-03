<?php
/**
 * Created by PhpStorm.
 * User: pkly
 * Date: 27.09.2019
 * Time: 12:13
 */

namespace Pkly\I18Next;

use Psr\Log\LoggerInterface;

require_once __DIR__ . '/Utils.php';

/**
 * Class Loader
 *
 * Base class for loaders to be used in I18n
 *
 * @package Pkly\I18Next
 */
abstract class Loader implements ModuleInterface {
    /**
     * @var array
     */
    protected $_options                         =   [];

    /**
     * @var LoggerInterface|null
     */
    protected $_logger                          =   null;

    /**
     * @var I18n|null
     */
    protected $_i18n                            =   null;

    /**
     * @var \stdClass|null
     */
    protected $_services                        =   null;

    /**
     * @inheritDoc
     */
    public function getModuleType(): string {
        return MODULE_TYPE_LOADER;
    }

    /**
     * @inheritDoc
     */
    public function getModuleName(): string {
        return 'loader-base';
    }

    /**
     * @inheritDoc
     */
    public function init(&$services, array $options, I18n &$instance): void {
        $this->_options = Utils\arrayMergeRecursiveDistinct(Utils\getDefaults(), $options);
        $this->_i18n = &$instance;
        $this->_logger = &$services->_logger;
        $this->_services = &$services;
    }

    /**
     * TODO: Fill out
     *
     * @param $languages
     * @param $namespace
     * @param $key
     * @param $fallbackValue
     * @param array $options
     * @param bool $isUpdate
     */
    public function create($languages, $namespace, $key, $fallbackValue, array $options = [], $isUpdate = false) {}

    /**
     * Read data for requested language and namespace
     *
     * @param $language
     * @param $namespace
     */
    public function read($language, $namespace) {}
}