<?php
/**
 * @package WP2App
 * @version 1.0
 */
/*
Plugin Name: WP 2 App
Plugin URI: http://wordpress.org/plugins/wp2app/
Description: Convert your WordPress site into a mobile app... the easy way!
Author: Rappidly WordPress
Version: 1.0
Author URI: http://wp2app.rappidly.net
*/

/*  Copyright 2015  Rappidly  (email : wp2app@rappidly.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

ini_set("display_errors", 1);
error_reporting(E_ALL);

defined("ABSPATH") or die("No script kiddies please!");

define("wp2app_siteurl", "http://wp2app.rappidly.net");
define("wp2app_apiurl", "http://wp2app.rappidly.net/api/");

class wp2app_core
{
    private static $pluginsUrl = "";
    private static $runcount = 0;

    /**
     * @param string $appendToUri
     * @param array $post_params
     *
     * @return string
     */
    private static function post_api($appendToUri, $post_params = array())
    {
        $postdata = http_build_query($post_params);

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context = stream_context_create($opts);
        $result = file_get_contents(wp2app_apiurl.$appendToUri, false, $context);

        return $result;
    }

    public static function activate()
    {
        // Set ID
        if (get_option("wp2app_id") === false) {
            add_option("wp2app_id", self::make_id(), "", false);
        }

        // Call API first to fetch or add site
        if (self::$runcount == 0) {
            $result = file_get_contents(wp2app_apiurl . "site/activate/" . get_option("wp2app_id") . "?siteUrl=" . urlencode(site_url()));
        }

        // Set status option if not yet set
        if (get_option("wp2app_status") === false) {
            add_option("wp2app_status", "new", "", false);
        }

        self::$runcount++;
    }

    /**
     *
     */
    public static function deactivate()
    {
        // Call API first to fetch or add site
        $result = file_get_contents(wp2app_apiurl . "site/deactivate/" . get_option("wp2app_id") . "?siteUrl=" . urlencode(site_url()));

        // Cleanup stuff
        delete_option("wp2app_status");
    }

    /**
     *
     */
    public static function uninstall()
    {
        // Call API first to fetch or add site
        $result = file_get_contents(wp2app_apiurl . "site/uninstall/" . get_option("wp2app_id") . "?siteUrl=" . urlencode(site_url()));

        // cleanup stuff
        delete_option("wp2app_status");
    }

    /**
     * @return string
     */
    public static function make_id()
    {
        return md5(uniqid().site_url().microtime());
    }

    /**
     * Init
     */
    public static function init()
    {
        self::$pluginsUrl = plugins_url("wp2app");

        // Local detection
        if (preg_match("/localhost/i", $_SERVER["SERVER_NAME"])) {
            //echo "You seem to be running a local wordpress installation... your app will not work on a local installation";
            //exit();
        }

        // When in iframe, disable admin navbar
        if (isset($_GET["wp2app_preview"])) {
            add_action("wp_footer", function() {
                echo '<link href="'.plugins_url("wp2app").'/css/wp2app_preview.css" type="text/css" rel="stylesheet">';
            });
        // When in app, disable admin navbar
        } elseif (isset($_GET["wp2app_inapp"])) {

            add_action("wp_head", function() {
                echo '<link href="'.self::$pluginsUrl.'/css/wp2app_inapp.css" type="text/css" rel="stylesheet">';
                echo '<script src="'.self::$pluginsUrl.'/vendors/fastclick.js" type="text/javascript"></script>';
                wp_enqueue_script('jquery');
                echo '<script src="'.self::$pluginsUrl.'/js/app.js" type="text/javascript"></script>';
            });

            add_action("wp_footer", function() {
            });
        // WP Admin area
        } else {

            register_activation_hook(__FILE__, array("wp2app_core", "activate"));
            register_deactivation_hook(__FILE__, array("wp2app_core", "deactivate"));
            register_uninstall_hook(__FILE__, array("wp2app_core", "uninstall"));

            self::add_menu_item();
            self::admin_wrap();
        }
    }

    /**
     * Add head section
     */
    public static function admin_wrap()
    {
        add_action('admin_head', function () {
            echo '<link href="'.self::$pluginsUrl.'/css/wp2app_admin.css" type="text/css" rel="stylesheet">';
            echo '<link href="'.self::$pluginsUrl.'/vendors/semantic/components/button.min.css" type="text/css" rel="stylesheet">';
            echo '<link href="'.self::$pluginsUrl.'/vendors/semantic/components/icon.css" type="text/css" rel="stylesheet">';
            echo '<link href="'.self::$pluginsUrl.'/vendors/semantic/components/form.min.css" type="text/css" rel="stylesheet">';
            echo '<link href="'.self::$pluginsUrl.'/vendors/semantic/components/grid.min.css" type="text/css" rel="stylesheet">';
            echo '<link href="'.self::$pluginsUrl.'/vendors/semantic/components/label.min.css" type="text/css" rel="stylesheet">';
            echo '<link href="'.self::$pluginsUrl.'/vendors/semantic/components/list.min.css" type="text/css" rel="stylesheet">';
            echo '<link href="'.self::$pluginsUrl.'/vendors/semantic/components/loader.min.css" type="text/css" rel="stylesheet">';
            echo '<link href="'.self::$pluginsUrl.'/vendors/semantic/components/message.min.css" type="text/css" rel="stylesheet">';
            echo '<link href="'.self::$pluginsUrl.'/vendors/semantic/components/step.min.css" type="text/css" rel="stylesheet">';
            echo '<link href="'.self::$pluginsUrl.'/vendors/semantic/components/segment.min.css" type="text/css" rel="stylesheet">';

            wp_enqueue_script('jquery');
        });

        add_action("admin_footer", function() {
            echo '<script src="'.self::$pluginsUrl.'/js/admin.js" type="text/javascript"></script>';
        });
    }

    /**
     * Add menu item
     */
    public static function add_menu_item()
    {
        // Register menu item
        add_action("admin_menu", function () {
            add_menu_page("WP2APP", "WP2APP", "activate_plugins", "wp2app_main", array("wp2app_core", "display_index"), "dashicons-smartphone", "25.1337");
            //add_submenu_page("wp2app_main", "Custom Subpage", "Custom subpage", "activate_plugins", "wp2app_preview", array("wp2app_core", "display_iframe"));
            //remove_submenu_page("wp2app_main", "wp2app_preview");
        });
    }

    /**
     * Display index
     */
    public static function display_index()
    {
        if (!current_user_can('activate_plugins')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        $errors = array();
        $success = array();
        $status = array(
            "settings" => false,
            "payment" => false,
            "publication" => false
        );
        $currentstep = 1;

        // Get payment code
        $paymentCode = file_get_contents(wp2app_apiurl . "payment/code/" . get_option("wp2app_id") . "?siteUrl=" . urlencode(site_url()));

        echo '<div class="wrap">';

        // Handle post
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // Update name
            if (!empty($_POST["name"])) {
                if (get_option("wp2app_name") === false) {
                    add_option("wp2app_name", $_POST["name"]);
                } else {
                    update_option("wp2app_name", $_POST["name"]);
                }
            } else {
                $errors[] = "Please fill out your full name.";
            }

            // Update email address
            if (!empty($_POST["email"])) {
                if (get_option("wp2app_email") === false) {
                    add_option("wp2app_email", $_POST["email"]);
                } else {
                    update_option("wp2app_email", $_POST["email"]);
                }
            } else {
                $errors[] = "Fill out your email address.";
            }

            // Update app name
            if (!empty($_POST["appName"])) {
                if (get_option("wp2app_appname") === false) {
                    add_option("wp2app_appname", $_POST["appName"]);
                } else {
                    update_option("wp2app_appname", $_POST["appName"]);
                }
            } else {
                $errors[] = "Please fill out a name for your app";
            }

            // Validate image
            if (isset($_FILES["appIcon"]) && !empty($_FILES["appIcon"]["tmp_name"])) {
                $data = file_get_contents($_FILES["appIcon"]["tmp_name"]);
                $im = imagecreatefromstring($data);
                if ($im) {
                    if (!preg_match("/\.png$/i", $_FILES["appIcon"]["name"])) {
                        $errors[] = "Invalid filetype for icon image (should be PNG).";
                    } elseif (imagesx($im) != 1024 || imagesy($im) != 1024) {
                        $errors[] = "Invalid image dimensions for icon image (should be 1024 x 1024).";
                    }
                    imagedestroy($im);
                } else {
                    $errors[] = "Invalid image file given.";
                }
                if (count($errors) === 0) {
                    $return = wp_upload_bits("wp2app_appicon.png", "", $data);
                    if (get_option("wp2app_iconurl") === false) {
                        add_option("wp2app_iconurl", $return["url"]);
                    } else {
                        update_option("wp2app_iconurl", $return["url"]);
                    }
                }
            } elseif (get_option("wp2app_iconurl") === false) {
                $errors[] = "Missing icon image.";
            }

            // Succesfull save
            if (count($errors) === 0) {
                $success[] = "Settings saved... almost there!";
                $currentstep++;

                $result = self::post_api("site/update/".get_option("wp2app_id"), array(
                    "siteUrl" => site_url(),
                    "appName" => $_POST["appName"],
                    "appIconUrl" => get_option("wp2app_iconurl"),
                    "adminName" => $_POST["name"],
                    "adminEmail" => $_POST["email"]
                ));
            }
        }
        $iconUrl = get_option("wp2app_iconurl");
        $iconImg = "";
        if ($iconUrl !== false) {
            $iconImg = '<img src="'.$iconUrl.'" width="64" height="64" class="wp2app_thumb">';
        }


        echo '
        <div class="ui grid">
            <div class="row">
                <div class="twelve wide column">

                    <h1>WP 2 App</h1>
                    <h2>The easiest way from wordpress to app! Works on Android and iOs (Apple) phones and tablets!</h2>
                    <br>
                    <input type="hidden" id="wp2app_apiurl" value="'.wp2app_apiurl.'">
                    <input type="hidden" id="wp2app_wpsiteurl" value="'.urlencode(site_url()).'">
                    <input type="hidden" id="wp2app_id" value="'.get_option("wp2app_id").'">
        ';

        // Error message
        if (count($errors) > 0) {
            echo '
            <div class="ui red message">
                <div class="header">Something went wrong...</div>
                <ul class="list">
                    <li>' . implode("</li><li>", $errors) . '
                </ul>
            </div>
            ';
        }

        // Success messages
        if (count($success) > 0) {
            echo '
            <div class="ui green message">
                <div class="header">Well done</div>
                <ul class="list">
                    <li>' . implode("</li><li>", $success) . '
                </ul>
            </div>
            ';
        }

        // Check for settings status
        if (get_option("wp2app_name") != "" && get_option("wp2app_email") != "" && get_option("wp2app_appname") != "" && get_option("wp2app_iconurl") != "") {
            $status["settings"] = true;
        }

        echo '
                    <div class="ui fluid steps">
                        <a class="ui '.($currentstep == 1 ? 'active' : '').' step" data-step="1">
                            <i class="setting icon"></i>
                            <div class="content">
                                <div class="title">App settings</div>
                                <div class="description">Fill out your details</div>
                            </div>
                        </a>
                        <a class="ui '.($currentstep == 2 ? 'active' : '').' step" data-step="2">
                            <i class="credit card icon"></i>
                            <div class="content">
                                <div class="title">Payment</div>
                                <div class="description">Completely safe via Paypal</div>
                            </div>
                        </a>
                        <a class="ui '.($currentstep == 3 ? 'active' : '').' step" data-step="3">
                            <i class="cloud upload icon"></i>
                            <div class="content">
                                <div class="title">Publication status</div>
                                <div class="description">Check your app status</div>
                            </div>
                        </a>
                    </div>
                    ';

        echo '
                    <div class="ui segment wp2app_step'.($currentstep != 1 ? ' hidden' : '').'" data-step="1">

                        <div class="ui blue message">
                            <p>
                                <i class="info icon"></i>
                                Enter some basic settings so that we can publish your app for you.
                            </p>
                        </div>

                        <h3>App settings</h3>

                        <form action="" class="ui form" method="post" enctype="multipart/form-data">
                            <div class="field">
                                <label>
                                    App name
                                    <small>
                                        This will be used for your store listings.
                                    </small>
                                </label>
                                <input type="text" name="appName" value="'.get_option("blogname").'" placeholder="Choose a unique name for your app">
                            </div>
                            <div class="field">
                                '.$iconImg.'
                                <label>App icon (1024 x 1024 png file)</label>
                                <input type="file" name="appIcon">
                            </div>
                            <div class="field">
                                <label>
                                    Your name
                                    <small>
                                        It\'s nice to be adressed properly.
                                    </small>
                                </label>
                                <input type="text" name="name" value="'.get_option("wp2app_name").'" placeholder="">
                            </div>
                            <div class="field">
                                <label>
                                    Your email address
                                    <small>
                                    So that we can let you know once your app is published.
                                    </small>
                                </label>
                                <input type="text" name="email" value="'.(get_option("wp2app_email") ? get_option("wp2app_email") : get_option("admin_email")).'" placeholder="">
                            </div>
                            <button type="submit" class="ui icon labeled huge green button">
                                <i class="ui icon chevron right"></i>
                                Save settings
                            </button>
                        </form>
                    </div>
        ';

        echo '
                    <div class="ui segment wp2app_step'.($currentstep != 2 ? ' hidden' : '').'" data-step="2">

                        <div class="ui blue message">
                            <p>
                                <i class="info icon"></i>
                                Choose the platforms you would like to have your app published on and complete payment.<br>
                            </p>
                        </div>

                        <div class="ui grid">
                            <div class="eight wide column">
                                <h3>Complete payment</h3>
                                '.str_replace("[wp2app_id]", get_option("wp2app_id"), $paymentCode).'
                                <p>
                                    Payment is completely safe via PayPal.
                                </p>
                            </div>
                            <div class="eight wide column">
                                <h3>Payment status</h3>
                                <p>
                                    <b>Payment status:</b> <span id="wp2app_payment_status">unknown</span>
                                </p>
                                <p>
                                    <a href="#" id="wp2app_refresh_payment_status" class="ui labeled icon button"><i class="refresh icon"></i> Update status</a>
                                </p>
                            </div>
                        </div>
                    </div>
        ';

        echo '
                    <div class="ui segment wp2app_step'.($currentstep != 3 ? ' hidden' : '').'" data-step="3">
                        <h3>Publication status</h3>

                        <p>
                            Your app status is: <b id="wp2app_status">unknown - click the button to renew</b>
                        </p>
                        <p>
                            Your Google Play Store URL is: <a href="" id="wp2app_playstore_url"></a>
                        </p>
                        <p>
                            Your Apple App Store URL is: <a href="" id="wp2app_appstore_url"></a>
                        </p>
                        <p>
                            Your site ID is: <b>'.get_option("wp2app_id").'</b>
                        </p>
                        <p>
                            <a href="#" id="wp2app_refresh_status" class="ui labeled icon button"><i class="refresh icon"></i> Update status</a>
                        </p>
                    </div>
        ';

        echo '
                    <div class="ui segment">
                        <h3>How it works</h3>
                        <ol class="ui list">
                            <li class="item">Fill in some settings</li>
                            <li class="item">Choose platforms and complete payment</li>
                            <li class="item">We will build your app, publish it and place your play/app store URL\'s in the <b>Publication status</b> tab</li>
                            <li class="item">That\'s all... there is no easier way!</li>
                        </ol>

                        <h3>Support &amp; contact information</h3>
                        <p>
                            For any feedback or questions, please visit the plugin page (<a href="http://wordpress.org/plugins/wp2app/">http://wordpress.org/plugins/wp2app/</a>) on wordpress.org
                             or go to our website: <a href="'.wp2app_siteurl.'" target="_blank">'.wp2app_siteurl.'</a>
                            and include your site id (<b>'.get_option("wp2app_id").'</b>).
                        </p>
                    </div>
        ';

        echo '
                </div>
                <div class="four wide column">
                    <div id="wp2app_iframewrap">
                    <h3>App Preview</h3>
                    <div id="wp2app_iframemobile">
                        <iframe id="wp2app_iframe" src="' . site_url() . '?wp2app_preview=1" width="256" height="380"></iframe>
                    </div>
                    <p>
                        <small>Not looking good? Make sure that your wordpress theme is <a href="http://themeforest.net/tags/responsive?category=wordpress&ref=rappidly" target="_blank"><b>responsive</b></a>.</small>
                    </p>
                    </div>
                </div>
            </div>
        </div>';

        echo '</div>';
    }

}

wp2app_core::init();
