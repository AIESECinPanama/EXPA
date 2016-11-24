<?php
/*
Plugin Name: AIESEC EXPA Registration 
Description: Plugin based on gis_curl_registration script by Dan Laush upgraded to Wordpress plugin by Krzysztof Jackowski, updated and optimized for WP 
podio and getResponse by Enrique Suarez
Version: 0.2.1
Author: Enrique Suarez 
Author URI: https://www.linkedin.com/profile/view?id=AAIAABf8S30Bu64oKEuBPfCG5ZYEUJC_-zyYli4&trk=nav_responsive_tab_profile_pic
License: GPL 
TEST: YES
*/
wp_enqueue_script('jquery');
defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );

// [expa-form program="gt"]

///GENERAL
function expa_form() {
    /*$a = shortcode_atts( array(
        'program' => '',
    ), $atts );
    */

    //$configs = include('config.php');
    //$configs_external = include('wp_login_config.php');

    $form = file_get_contents('form.html',TRUE);

    //$form = str_replace("{path-expa--styles}",plugins_url('expa-form-styles.css', __FILE__ ),$form);
    //$form = str_replace("{path-gis_reformg_process}",plugins_url('gis_reg_process.php', __FILE__ ),$form);

    /*
    if($_GET["thank_you"]==="true"){
        return $configs["thank-you-message"]; 
    } elseif ($_GET["error"]!=""){
        
        $form = str_replace('<div id="error" class="error"><p></p></div>','<div id="error" class="error"><p>'.$_GET["error"].'</p></div>',$form);
        return $form;    
    }*/
    //var_dump( plugins_url('gis_reg_process.php', __FILE__ ));
    return $form;
}
add_shortcode( 'expa-form', 'expa_form' );

?>