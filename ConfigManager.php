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

    public static function getConfigFile()
    {
        return dirname(__FILE__)."/configs/config.csv";
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
        $filename = self::getConfigFile();
        if (!is_file($filename)) {
            $dir = dirname($filename);
            if ($dir == $filename) {
                throw new Exception("Permission d'écrire sur le fichier de configuration non autorisée.");
            }
            if (!is_writable($dir)) {
                throw new Exception("Permission d'écrire sur le fichier de configuration non autorisée.");
            }
        } elseif (!is_writable(self::getConfigFile())) {
            throw new Exception("Permission d'écrire sur le fichier de configuration non autorisée.");
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
    }

    public static function saveAlert(Alert $alert)
    {
        if (empty($alert->id)) {
            $alert->id = md5(uniqid());
        }
        self::$_config[$alert->id] = $alert;
        self::save();
    }

    public static function deleteAlert(Alert $alert)
    {
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
}
