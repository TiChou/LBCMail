<?php
$dirname = dirname(__FILE__);
require $dirname."/lib/lbc.php";
require $dirname."/ConfigManager.php";

$config = ConfigManager::loadConfigIni();

if (isset($_GET["key"]) && isset($config['key']) && $_GET["key"] != $config['key']) {
    return;
}

function mail_utf8($to, $subject = '(No subject)', $message = '', $headers = '', $parameters = '')
{
    $subject = "=?UTF-8?B?".base64_encode($subject)."?=";

    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";

    return mail($to, $subject, $message, $headers, $parameters);
}

$files = scandir(dirname(__FILE__)."/configs");
foreach ($files AS $file) {
    if (false === strpos($file, ".csv")) {
        continue;
    }
    ConfigManager::setConfigName(str_replace(".csv", "", $file));
    $alerts = ConfigManager::getAlerts();
    if (count($alerts) == 0) {
        continue;
    }
    foreach ($alerts AS $i => $alert) {
        $currentTime = time();
        if (!isset($alert->time_updated)) {
            $alert->time_updated = 0;
        }
        if (((int)$alert->time_updated + (int)$alert->interval*60) > $currentTime) {
            continue;
        }
        $alert->time_updated = $currentTime;
        $content = file_get_contents($alert->url);
        $content = mb_convert_encoding($content, "ISO-8859-15", "WINDOWS-1252");
        $ads = Lbc_Parser::process($content, array(
            "price_min" => $alert->price_min,
            "price_max" => $alert->price_max,
            "cities" => $alert->cities,
            "price_strict" => (bool)$alert->price_strict
        ));
        if (count($ads) == 0) {
            ConfigManager::saveAlert($alert);
            continue;
        }
        $newAds = array();
        $time_last_ad = (int)$alert->time_last_ad;
        foreach ($ads AS $ad) {
            if ($time_last_ad < $ad->getDate()) {
                $newAds[] = require $dirname."/views/mail-ad.phtml";
                if ($alert->time_last_ad < $ad->getDate()) {
                    $alert->time_last_ad = $ad->getDate();
                }
            }
        }
        if ($newAds) {
            $subject = "Alerte Leboncoin.fr : ".$alert->title;
            $headers = "";
            if (isset($config['mail_from']) && !empty($config['mail_from'])) {
                $headers .= "From: " . $config['mail_from'] . "\r\n";
            }
            $message  = '<h2>Alerte générée le '.date("d/m/Y à H:i", $currentTime).'</h2><p>';
            if (isset($config['site']) && !empty($config['site'])) {
                $message .= '<a href="' . $config['site'] . '?a=form&amp;id=' . $alert->id . '">modifier</a> | ';
                $message .= '<a href="' . $config['site'] . '?a=form-delete&amp;id=' . $alert->id . '">supprimer</a> | ';
            }
            $message .= '<a href="' . $alert->url . '">lien recherche</a>';
            $message .= '</p><p>Liste des nouvelles annonces :</p><hr /><br />';
            $message .= implode("<br /><hr /><br />", $newAds).'<br /><hr /><br />';
            $parameters = "";
            if (isset($config['return_path']) && !empty($config['return_path'])) {
                $parameters .= "-f " . $config['return_path'];
            }
            mail_utf8($alert->email, $subject, $message, $headers, $parameters);
        }
        ConfigManager::saveAlert($alert);
    }
}