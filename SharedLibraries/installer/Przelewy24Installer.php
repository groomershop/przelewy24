<?php

if (!class_exists('Przelewy24Installer', false)) {
    class Przelewy24Installer implements Przelewy24Interface
    {
        private $translations;
        private $sliderEnabled = true;
        private $pages = [];

        public function __construct($sliderEnabled = true, array $translations = [])
        {
            $this->sliderEnabled = $sliderEnabled;
            $this->setTranslations($translations);
        }

        public function setTranslations(array $translations = [])
        {
            $this->translations = $translations;
            // set default values
            $defaultTranslations = [
                'php_version' => 'Wersja PHP min. 5.5',
                'curl_enabled' => 'Włączone rozszerzenie PHP cURL (php_curl.dll)',
                'soap_enabled' => 'Włączone rozszerzenie PHP SOAP (php_soap.dll)',
                'merchant_id' => 'ID sprzedawcy',
                'shop_id' => 'ID sklepu',
                'crc_key' => 'Klucz CRC',
                'api_key' => 'Klucz API'
            ];

            foreach ($defaultTranslations as $translationKey => $translationValue) {
                if (empty($this->translations[$translationKey])) {
                    $this->translations[$translationKey] = $translationValue;
                }
            }
        }

        public function addPages(array $pages = [])
        {
            $this->pages = array_values($pages);
        }

        public function renderInstallerSteps()
        {
            if (!$this->sliderEnabled || empty($this->pages) || !is_array($this->pages)) {
                return '';
            }

            $requirements = $this->checkRequirements();
            $params = [
                'requirements' => $requirements,
                'translations' => $this->translations
            ];
            $maxSteps = 0;
            $data = [
                'steps' => []
            ];

            foreach ($this->pages as $page) {
                $page = (int)$page;
                if ($page > 0) {
                    $step = $this->loadStep($page, $params);
                    $data['steps'][$page] = $step;
                    $maxSteps++;
                }
            }

            if ($maxSteps === 0) {
                return '';
            }

            $data['maxSteps'] = $maxSteps;

            return $this->loadTemplate('installer', $data);
        }

        private function loadStep($number, $params = null)
        {
            $step = $this->loadTemplate('step' . $number, $params);
            $step = $this->removeNewLines($step);
            return $step;
        }

        private function removeNewLines($string)
        {
            return trim(str_replace(PHP_EOL, ' ', $string));
        }

        private function loadTemplate($view, $data = null)
        {
            extract(['content' => $data]);
            ob_start();
            $viewFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR . "$view.tpl.php";

            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                throw new Exception('View not exist in ' . get_class($this));
            }

            $content = ob_get_clean();
            return $content;
        }

        private function checkRequirements()
        {
            $data = [
                'php' => [
                    'test' => (version_compare(PHP_VERSION, '5.5.0') > 0),
                    'label' => $this->translations['php_version']
                ],
                'curl' => [
                    'test' => function_exists('curl_version'),
                    'label' => $this->translations['curl_enabled']
                ],
                'soap' => [
                    'test' => class_exists('SoapClient'),
                    'label' => $this->translations['soap_enabled']
                ]
            ];

            return $data;
        }
    }
}