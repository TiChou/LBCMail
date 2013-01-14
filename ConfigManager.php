<?php

class Alert {
    public $email;
    public $id;
    public $title;
    public $url;
    public $interval = 30;
    public $time_last_ad = 0;
    public $time_updated = 0;
    public $price_min = -1;
    public $price_max = -1;
    public $price_strict = false;
    public $cities;

    public function fromArray(array $values)
    {
        foreach ($values AS $key => $value) {
            $this->$key = $value;
        }
    }

    public function toArray()
    {
        return array(
            "email" => $this->email,
            "id" => $this->id,
            "title" => $this->title,
            "url" => $this->url,
            "interval" => $this->interval,
            "time_last_ad" => $this->time_last_ad,
            "time_updated" => $this->time_updated,
            "price_min" => $this->price_min,
            "price_max" => $this->price_max,
            "price_strict" => $this->price_strict,
            "cities" => $this->cities
        );
    }
}

class ConfigManager
{
    protected static $_config;
    protected static $_name = "config";
    protected static $_configIni = "config.ini";
    
    public static function getConfigFile()
    {
        return dirname(__FILE__)."/configs/".self::$_name.".csv";
    }

    public static function setConfigName($name)
    {
        self::$_name = $name;
        self::load();
    }

    public static function load()
    {
        if (!is_file(self::getConfigFile())) {
            return array();
        }
        $fp = fopen(self::getConfigFile(), "r");
        if (!$header = fgetcsv($fp, 0, ",", '"')) {
            return array();
        }
        $nb = count($header);
        $config = array();
        while (false !== $a = fgetcsv($fp, 0, ",", '"')) {
            $alert = new Alert();
            for ($i = 0; $i < $nb; $i++) {
                if (isset($a[$i])) {
                    $alert->$header[$i] = $a[$i];
                }
            }
            $config[$alert->id] = $alert;
        }
        fclose($fp);
        self::$_config = $config;
        return $config;
    }

    public static function save()
    {
        if (!is_array(self::$_config)) {
            self::load();
        }
        $filename = self::getConfigFile();
        $setChmod = false;
        if (!is_file($filename)) {
            $dir = dirname($filename);
            if ($dir == $filename) {
                throw new Exception("Permission d'écrire sur le fichier de configuration non autorisée.");
            }
            if (!is_writable($dir)) {
                throw new Exception("Permission d'écrire sur le fichier de configuration non autorisée.");
            }
            $setChmod = true;
        }
        $fp = fopen($filename, "w");
        if (self::$_config && is_array(self::$_config)) {
            $alerts = array_values(self::$_config);
            $keys = array_keys($alerts[0]->toArray());
            fputcsv($fp, $keys, ",", '"');
            foreach (self::$_config AS $alert) {
                fputcsv($fp, array_values($alert->toArray()), ",", '"');
            }
        }
        fclose($fp);
        if ($setChmod) {
            chmod($filename, 0777);
        }
    }

    public static function saveAlert(Alert $alert)
    {
        if (!is_array(self::$_config)) {
            self::load();
        }
        if (empty($alert->id)) {
            $alert->id = md5(uniqid());
        }
        self::$_config[$alert->id] = $alert;
        self::save();
    }

    public static function deleteAlert(Alert $alert)
    {
        if (!is_array(self::$_config)) {
            self::load();
        }
        unset(self::$_config[$alert->id]);
        self::save();
    }

    public static function getAlerts()
    {
        if (!is_array(self::$_config)) {
            self::load();
        }
        return self::$_config;
    }

    public static function getAlertById($id)
    {
        if (!is_array(self::$_config)) {
            self::load();
        }
        if (isset(self::$_config[$id])) {
            return self::$_config[$id];
        }
        return null;
    }

    public static function loadConfigIni()
    {
        $dirname = dirname(__FILE__);
        $ini_array = parse_ini_file(dirname(__FILE__)."/configs/".self::$_configIni);
        if (!is_array($ini_array)) $ini_array = array();
        return $ini_array;
    }
}
