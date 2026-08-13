<?php
/**
 * Plugin Name: UWEBBZ Drive Teacher Console
 * Plugin URI: https://github.com/donteck/uwebbz-drive-portal-integration
 * Description: Adds the UWEBBZ teacher Drive browser, course and lesson publishing controls, student assignments, and scheduled lesson releases to UWEBBZ Drive Portal.
 * Version: 2.0.0
 * Author: UWEBBZ Technology
 * Text Domain: uwebbz-drive-portal
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-uld-teacher-console.php';
ULD_Teacher_Console::init();
