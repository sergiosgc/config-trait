<?php
namespace sergiosgc\config;
trait Config {
    public function getConfig($key, $configArray = null) {
        if (is_null($configArray)) {
            if (is_null($this->config)) {
                // Include configuration
                {
                    foreach(array($this->paths['config'], $this->paths['config'] . '/' . strtolower("cli" == php_sapi_name() ? 'cli' : $_SERVER['HTTP_HOST'])) as $configDir) {
                        if (!is_dir($configDir)) continue;
                        $dir = opendir($configDir);
                        while (($file = readdir($dir)) !== false) {
                            if ($file == '.' || $file == '..') continue;
                            if (is_file($configDir . '/' . $file) && preg_match('_\.php$_', $file)) include($configDir . '/' . $file);
                        }
                        unset($file);
                        unset($dir);
                    }
                    unset($configDir);
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
