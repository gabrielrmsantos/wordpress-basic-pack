<?php
/*
Plugin Name: Internal Links Generator
Plugin URI: https://makong.kiev.ua/plugins/internal-links-generator/
Description: Simple way to automatically link a certain word or phrase in your post/page/custom content to a URL you specify.
Version: 3.51
Author: Makong
Author URI: http://makong.kiev.ua/
License: GPL2
*/

if(!class_exists('Internal_Links_Generator')){
	
    class Internal_Links_Generator{

        public function __construct(){
            // Initialize Settings
            require_once(sprintf("%s/settings.php", dirname(__FILE__)));
            $ilgen_setting_object = new Internal_Links_Generator_Settings();

            $plugin = plugin_basename(__FILE__);
            add_filter("plugin_action_links_$plugin", array( $this, 'plugin_settings_link' ));
        }

        public static function activate(){
            global $wpdb;
            $wpdb->query(
                "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."internalinks` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `keyword` VARCHAR(100) NOT NULL,
                `target` VARCHAR(255) NOT NULL,
                `count` INT(11) DEFAULT '0',
                `limit` INT(11) DEFAULT '0',
                `linked` INT(11) DEFAULT '0',
                `posts` TEXT NULL,
                `terms` TEXT NULL,
                `tag` VARCHAR(20) NULL,
                PRIMARY KEY (`id`)) 
                CHARACTER SET utf8 COLLATE utf8_general_ci"
            );
            if(!$wpdb->query("SHOW COLUMNS FROM {$wpdb->prefix}internalinks LIKE 'tag'")){
                $wpdb->query("ALTER TABLE {$wpdb->prefix}internalinks ADD tag VARCHAR(20) CHARACTER SET utf8 NULL");
            }
            if(!$wpdb->query("SHOW COLUMNS FROM {$wpdb->prefix}internalinks LIKE 'terms'")){
                $wpdb->query("ALTER TABLE {$wpdb->prefix}internalinks ADD terms TEXT CHARACTER SET utf8 NULL");
            }
            if(!get_option('ilgen_options')){
                add_option('ilgen_options', array(
                    'numlinks'   => 0,
                    'allowed_pt' => array(),
                    'allowed_tx' => array(),
                    'bugfixer'   => ''
                ));
            }
        }

        public static function deactivate(){
            // Do nothing
        }

        // Add the settings link to the plugins page
        function plugin_settings_link($links){
            $settings_link = '<a href="options-general.php?page=internal_links_generator">Settings</a>';
            array_unshift($links, $settings_link);
            return $links;
        }
    }
}

if(class_exists('Internal_Links_Generator')){
	
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('Internal_Links_Generator', 'activate'));
    register_deactivation_hook(__FILE__, array('Internal_Links_Generator', 'deactivate'));

    // instantiate the plugin class
    $ilgen_object = new Internal_Links_Generator();
}
