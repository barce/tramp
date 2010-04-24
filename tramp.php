<?php
/*
Plugin Name: tramp
Plugin URI: http://github.com/barce/tramp
Version: 0.1
Author: Barce
Author URI: http://twitter.com/barce
Description: Tramp stands for traffic amplifier. This plug-in amplifies traffic on a WordPress about page by applying keywords that are trending from Twitter.com or Google's AdSense.
*/

if (!class_exists("TrampPlugin")) {

  class TrampPlugin {
    function __construct() {
      // construct a class
    }


    function addHeaderCode() { 
      echo '<!-- TrampPlugin Installed -->' . "\n";
    }
    
    function addContent($content='') {
      $s_date = date(DATE_RFC822);
      $content .= "<p>$s_date</p>";
      return $content;
    } 

  } // End class TrampPlugin

}


if (class_exists("TrampPlugin")) { 
  $tr_plugin = new TrampPlugin(); 
}  


//Actions and Filters    
if (isset($tr_plugin)) { 
  //Actions 
  add_action('wp_head', array(&$tr_plugin, 'addHeaderCode'), 1);
  //Filters 
  add_filter('the_content', array(&$tr_plugin, 'addContent'));
}

// notes: be sure to use
// wp_remote_get()
// 
?>
