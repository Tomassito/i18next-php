<?php
/**
 * Created by PhpStorm.
 * User: pkly
 * Date: 27.09.2019
 * Time: 10:37
 */

namespace Pkly\I18Next;

use Psr\Log\LoggerInterface;

class TranslationLoadManager {
    /**
     * @var array
     */
    private $_options                           =   [];

    /**
     * @var ResourceStore|null
     */
    private $_store                             =   null;

    /**
     * @var Loader|null
     */
    private $_loader                            =   null;

    /**
     * @var LanguageUtil|null
     */
    private $_languageUtils                     =   null;

    /**
     * @var LoggerInterface|null
     */
    private $_logger                            =   null;

    /**
     * @var array
     */
    private $_queue                             =   [];

    /**
     * @var array
     */
    private $_state                             =   [];

    public function __construct(Loader &$loader, ResourceStore &$store, &$services, array $options = []) {
        $this->_loader = &$loader;
        $this->_store = &$store;
        $this->_options = $options;
        $this->_languageUtils = &$services->_languageUtils;
        $this->_logger = &$services->_logger;
    }

    public function queueLoad(array $languages, array $namespaces, array $options = []) {
        $toLoad = [];
        $pending = [];
        $toLoadLanguages = [];
        $toLoadNamespaces = [];

        foreach ($languages as $lng) {
            $hasAllNamespaces = true;

            foreach ($namespaces as $ns) {
                $name = $lng . '|' . $ns;

                if (!$options['reload'] ?? false && $this->_store->hasResourceBundle($lng, $ns)) {
                    $this->_state[$name] = 2; // loaded
                }
                else if ($this->_state[$name] ?? 0 < 0) {
                    // nothing?
                }
                else if ($this->_state[$name] ?? 0 === 1) {
                    if (!in_array($name, $pending))
                        $pending[] = $name;
                }
                else {
                    $this->_state[$name] = 1;

                    $hasAllNamespaces = false;

                    if (!in_array($name, $pending))
                        $pending[] = $name;

                    if (!in_array($name, $toLoad))
                        $toLoad[] = $name;

                    if (!in_array($ns, $toLoadNamespaces))
                        $toLoadNamespaces[] = $ns;
                }
            }

            if (!$hasAllNamespaces)
                $toLoadLanguages[] = $lng;
        }

        if (count($toLoad) || count($pending))
            $this->_queue[] = [
                'pending'       =>  $pending,
                'loaded'        =>  [],
                'errors'        =>  []
            ];

        return [
            'toLoad'                =>  $toLoad,
            'pending'               =>  $pending,
            'toLoadLanguages'       =>  $toLoadLanguages,
            'toLoadNamespaces'      =>  $toLoadNamespaces
        ];
    }

    public function loaded($name, $data) {
        list($lng, $ns) = explode("|", $name);

        if ($data) {
            $this->_store->addResourceBundle($lng, $ns, $data);
        }

        $this->_state[$name] = 2;
    }

    public function read($lng, $ns, $fcName) {
        if (!$lng)
            return null;

        if (!is_callable([$this->_loader, $fcName])) {
            $this->_logger->warning('No valid loader was found when trying to read data in TranslationLoadManager');
            return null;
        }

        return $this->_logger->{$fcName}($lng, $ns);
    }

    public function prepareLoading($languages, $namespaces, array $options = []) {
        if ($this->_loader === null) {
            $this->_logger->warning('No loader was added via i18next.useModule. Will not load resources.');
            return;
        }

        if (is_string($languages))
            $languages = $this->_languageUtils->toResolveHierarchy($languages);

        if (is_string($namespaces))
            $namespaces = [$namespaces];


    }

    public function load($languages, $namespaces) {
        $this->prepareLoading($languages, $namespaces);
    }

    public function reload($languages, $namespaces) {
        $this->prepareLoading($languages, $namespaces, ['reload' => true]);
    }

    public function loadOne($name, string $prefix = '') {
        list($lng, $ns) = explode("|", $name);

        try {
            $data = $this->read($lng, $ns, 'read');
            if ($data !== null)
                $this->_logger->info($prefix . 'Loaded namespace ' . $ns . ' for language ' . $lng, (array)$data);

            $this->loaded($name, $data);
        }
        catch (\Exception $e) {
            $this->_logger->warning($prefix . 'Loading namespace ' . $ns . ' for language '. $lng . ' failed', (array)$e);
            $this->_state[$name] = -1;
        }
    }
}