<?php

/**
 * CountryHide plugin
 *
 * @link              https://weekdays.te.ua
 * @since             1.0.0
 * @package           CountryHide
 *
 * @wordpress-plugin
 * Plugin Name:       CountryHide Plugin
 * Plugin URI:        #
 * Description:       Simple plugin to hide content by country code. Use with shortcode [hfc code="IN,UA,RU" ][/hfc] or function blacklist("IN,UA,RU");
 * Version:           1.0.0
 * Author:            org100h
 * Author URI:        https://weekdays.te.ua
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
class countryhide {

    private $prefix = null;
    private $api = null;
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {

        $this->prefix = 'hfc';
        $this->api = 'http://ip-api.com/json/';

        //add shortcode
        add_shortcode($this->prefix, [&$this, 'ip_stop_code']);
    }

    /**
     * 
     * @param string $cc
     * @return boolean
     */
    public function hfc_($cc) {

        $cc = $this->get_cc_by_ip($this->get_ip());

        if (!in_array($cc, explode(',', $cc))) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 
     * @param array $atts
     * @param string $content
     * @return string
     */
    public function ip_stop_code($atts, $content = null) {
        $a = shortcode_atts(array(
            'code' => NULL,
            'show' => 'no'
                ), $atts);

        $cc = $this->get_cc_by_ip($this->get_ip());
        if (!in_array($cc, explode(',', $a['code']))) {
            return do_shortcode($content);
        } else {
            return '<span><!-- hide for ' . $cc . '--></span>';
        }
    }

    /**
     * 
     * @return string ip
     */
    private function get_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * 
     * @param string $ip
     * @return string
     */
    private function get_cc_by_ip($ip) {

        if (false === ($json = get_transient($this->prefix . $ip))) {
            $service_url = $this->api . $ip;
            try {
                if ($json = file_get_contents($service_url)) {
                    $obj = json_decode($json);
                }
            } catch (Exeption $e) {
                error_log('An error occurred. ' . $e);
            }
            set_transient($this->prefix . $ip, $json, DAY_IN_SECONDS);
        } else {
            $obj = json_decode($json);
        }

        return $obj->countryCode;
    }
}

$countryhide = countryhide::get_instance();

function blacklist($attr) {
    $countryhide = countryhide::get_instance();
    return $countryhide->hfc_($attr);
}
