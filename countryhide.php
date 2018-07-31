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
        add_shortcode($this->prefix, [$this, 'ip_stop_code']);
    }

    /**
     * 
     * @param string $cc
     * @return boolean
     */
    public function hfc($attr) {

        $cc = $this->get_cc_by_ip($this->get_ip());

        if (!in_array($cc, explode(',', $attr))) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 
     * add_shortcode($this->prefix, [&$this, 'ip_stop_code']);
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
     * Advanced Method to Retrieve Client IP Address
     * @return string ip
     */
    private function get_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    // trim for safety measures
                    $ip = trim($ip);
                    // attempt to validate IP
                    if ($this->validate_ip($ip)) {
                        return $ip;
                    }
                }
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
    }

    /**
     * Ensures an ip address is both a valid IP and does not fall within
     * a private network range.
     * @return boolean 
     */
    private function validate_ip($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        return true;
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

function blacklist($attr = NULL) {
    $countryhide = countryhide::get_instance();
    if ($attr)
        return $countryhide->hfc($attr);
}

blacklist();
