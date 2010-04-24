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
    var $adminOptionsName = "TrampPluginAdminOptions";
    function __construct() {
      // construct a class
    }


    /*
     * addHeaderCode -- adds a comment to the header
     */
    function addHeaderCode() { 
      echo '<!-- TrampPlugin Installed -->' . "\n";
    }
    
    /*
     * addContent -- adds an RFC822 date to the bottom of a post
     */
    function addContent($content='') {
      $s_date = date(DATE_RFC822);
      $content .= "<p>$s_date</p>";
      return $content;
    } 

    function authorUpperCase($author='') {
      return strtoupper($author);
    } 


    function getAdminOptions() {
      $trampAdminOptions = array('show_header' => 'true',
        'add_content'=> 'true',
        'comment_author' => 'true',
        'content' => ''
      );
      $trampOptions = get_options($this->adminOptionsName);
      if (!empty($trampOptions)) {
        foreach($trampOptions as $key => $option)
          $trampAdminOptions[$key] = $option;
      }

      // store options in WordPress database
      update_option($this->adminOptionsName, $trampAdminOptions);
      return $trampAdminOptions;

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
  add_filter('get_comment_author', array(&$tr_plugin, 'authorUpperCase'));
}

// notes: be sure to use
// wp_remote_get()
// 
?>
