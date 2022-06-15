<?php
namespace sergiosgc\config;
trait Config {
    public function getConfig($key, $configArray = null) {
        if (is_null($configArray)) {
            if (is_null($this->config)) {
                // Include configuration
                {
                    $configFiles = [$this->paths['config'] ];
                    if ( "cli" == php_sapi_name() ) {
                        $configFiles[] = $this->paths['config'] . '/' . (isset($_SERVER['LOCAL_CONFIG']) ? $_SERVER['LOCAL_CONFIG'] : 'cli');
                    } else $configFiles[] = $this->paths['config'] . '/' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'http');
                    array_map(
                        fn($f) => include($f),
                        array_filter(
                            array_reduce(
                                array_filter( $configFiles, "is_dir" ),
                                function ($acc, $d) {
                                    $dir = opendir($d);
                                    while (($file = readdir($dir)) !== false) $acc[] = $d . '/' . $file;
                                    closedir($dir);
                                    return $acc;
                                },
                                []
                            ),
                            fn($f) => preg_match('_\.php$_', $f)
                        )
                    );
                }
                if (is_null($this->config)) throw new Exception('Configuration is still null after including config dir');
            }
            return $this->getConfig($key, $this->config);
        }
        if (strpos($key, '.') === FALSE) {
            if (isset($configArray[$key])) return $configArray[$key];
            throw new Exception(sprintf("Configuration key %s not found", $key));
        } else {
            $pos = strpos($key, '.');
            $left = substr($key, 0, $pos);
            $right = substr($key, $pos+1);
            if (!isset($configArray[$left])) throw new Exception(sprintf("Configuration key %s not found", $key));
            try {
                return $this->getConfig($right, $configArray[$left]);
            } catch (Exception $e) {
                throw new Exception(sprintf("Configuration key %s not found", $key));
            }
        }
    }
    public function getConfigWithDefault($key, $default) {
        try {
            return $this->getConfig($key);
        } catch (Exception $e) { 
            return $default;
        }
    }
    public function setConfig($key, $value, &$configArray = null) {
        if (is_null($configArray)) {
            if (is_null($this->config)) $this->config = array();
            return $this->setConfig($key, $value, $this->config);
        }
        if (strpos($key, '.') === FALSE) {
            if (is_array($value)) {
                if (!isset($configArray[$key])) $configArray[$key] = array();
                foreach($value as $innerKey => $innerValue) $this->setConfig($innerKey, $innerValue, $configArray[$key]);
                return;
            } 
            $configArray[$key] = $value;
            return;
        } 
        $pos = strpos($key, '.');
        $left = substr($key, 0, $pos);
        $right = substr($key, $pos+1);
        if (!isset($configArray[$left])) $configArray[$left] = array();
        return $this->setConfig($right, $value, $configArray[$left]);
    }
}
