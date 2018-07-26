<?php

/**
 * CountryBlock plugin
 *
 * @link              https://weekdays.te.ua
 * @since             1.0.0
 * @package           CountryBlock
 *
 * @wordpress-plugin
 * Plugin Name:       CountryBlock Plugin
 * Plugin URI:        #
 * Description:       Simple plugin to hide content by country code.. Use with shortcode [hfc code="IN,UA,RU" ][/hfc] or function 
 * Version:           1.0.0
 * Author:            Olexandr Chimera
 * Author URI:        https://weekdays.te.ua
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
class countryblock {

    private $prefix = null;
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->prefix = 'hfc';
        //add shortcode
        add_shortcode($this->prefix, [&$this, 'ip_stop_code']);
    }

    public function ip_stop_code($atts, $content = null) {
        $a = shortcode_atts(array(
            'code' => NULL
                ), $atts);

        $cc = $this->get_cc_by_ip($this->get_ip());
        if (!in_array($cc, explode(',', $a['code']))) {
            return do_shortcode($content);
        } else {
            return '<span><!-- hide for ' . $cc . '--></span>';
        }
    }

    private function get_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    private function get_cc_by_ip($remote) {

        if (false === ($json = get_transient($this->prefix . $remote))) {
            $service_url = "http://ip-api.com/json/" . $remote;
            try {
                if ($json = file_get_contents($service_url)) {
                    $obj = json_decode($json);
                }
            } catch (Exeption $e) {
                error_log('An error occurred. ' . $e);
            }
            set_transient($this->prefix . $remote, $json, DAY_IN_SECONDS);
        } else {
            $obj = json_decode($json);
        }

        return $obj->countryCode;
    }

    public function hfc_($atts) {

        $cc = $this->get_cc_by_ip($this->get_ip());

        if (!in_array($cc, explode(',', $atts))) {
            return false;
        } else {
            return true;
        }
    }

}

$countryblock = countryblock::get_instance();

function blacklist($attr) {
    $countryblock = countryblock::get_instance();
    return $countryblock->hfc_($attr);
}
