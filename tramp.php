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
    var $adminUsersName   = "TrampPluginAdminUsersOptions";
    function __construct() {
      // construct a class
    }

    function init() { 
      $this->getAdminOptions(); 
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
      $trampOptions = $this->getAdminOptions();

      $json = wp_remote_get('http://search.twitter.com/trends.json');
      $trends = json_decode($json['body']);

      $s_date = date(DATE_RFC822);
      $content .= "<p>$s_date &mdash; {$trampOptions['content']}</p>";
      $a_list = $trends->trends;
      $content .= "<ul>\n";
      foreach ($a_list as $trend) {
        $content .= "<li>{$trend->name}</li>\n";
      }
      $content .= "</ul>\n";
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
      $trampOptions = get_option($this->adminOptionsName);
      if (!empty($trampOptions)) {
        foreach($trampOptions as $key => $option)
          $trampAdminOptions[$key] = $option;
      }

      // store options in WordPress database
      update_option($this->adminOptionsName, $trampAdminOptions);
      return $trampAdminOptions;

    }



    /*
     * printAdminOptions -- show form for managing adminOptions
     */
    function printAdminPage() {

      $trampOptions = $this->getAdminOptions();
      if (isset($_POST['update_trampPluginSettings'])) {
        if (isset($_POST['trampHeader'])) {
          $trampOptions['show_header'] = $_POST['trampHeader'];
        }
        if (isset($_POST['trampAddContent'])) {
          $trampOptions['add_content'] = $_POST['trampAddContent'];
        }
        if (isset($_POST['trampAuthor'])) {
          $trampOptions['comment_author'] = $_POST['trampAuthor'];
        }
        if (isset($_POST['trampContent'])) {
          $trampOptions['content'] = apply_filters('content_save_pre', $_POST['trampContent']);
        }
        update_option($this->adminOptionsName, $trampOptions);

        echo  '<div class="updated"><p><strong>';
        _e("Settings Updated.", "TrampPlugin");
        echo '</strong></p></div>';

      } // end if $_POST

?>


<div class=wrap> 
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>"> 
<h2>Tramp Plugin</h2> 
<h3>Content to Add to the End of a Post</h3> 
<textarea name="trampContent" style="width: 80%; height: 100px;"><?php 
_e(apply_filters('format_to_edit',$trampOptions['content']), 
'TrampPlugin') ?></textarea> 
<h3>Allow Comment Code in the Header?</h3> 
<p>Selecting "No" will disable the comment code inserted in the header.</p> 
<p><label for="trampHeader_yes"><input type="radio" 
id="trampHeader_yes" name="trampHeader" value="true" <?php if 
($trampOptions['show_header'] == "true") { _e('checked="checked"', 
"TrampPlugin"); }?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label 
for="trampHeader_no"><input type="radio" id="trampHeader_no" 
name="trampHeader" value="false" <?php if ($trampOptions['show_header'] 
== "false") { _e('checked="checked"', "TrampPlugin"); }?>/> 
No</label></p> 

<h3>Allow Content Added to the End of a Post?</h3> 
<p>Selecting "No" will disable the content from being added into the end of a 
post.</p>
<p><label for="trampAddContent_yes"><input type="radio" 
id="trampAddContent_yes" name="trampAddContent" value="true" 
<?php if ($trampOptions['add_content'] == "true") { _e('checked="checked"', 
"TrampPlugin"); }?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label 
for="trampAddContent_no"><input type="radio" 
id="trampAddContent_no" name="trampAddContent" value="false" 
<?php if ($trampOptions['add_content'] == "false") { _e('checked="checked"', 
"TrampPlugin"); }?>/> No</label></p> 
   
<h3>Allow Comment Authors to be Uppercase?</h3> 
<p>Selecting "No" will leave the comment authors alone.</p> 
<p><label for="trampAuthor_yes"><input type="radio" 
id="trampAuthor_yes" name="trampAuthor" value="true" <?php if 
($trampOptions['comment_author'] == "true") { _e('checked="checked"', 
"TrampPlugin"); }?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label 
for="trampAuthor_no"><input type="radio" id="trampAuthor_no" 
name="trampAuthor" value="false" <?php if 
($trampOptions['comment_author'] == "false") { _e('checked="checked"', 
"TrampPlugin"); }?>/> No</label></p> 

<div class="submit"> 
<input type="submit" name="update_trampPluginSettings" 
value="<?php _e('Update Settings', 'TrampPlugin') ?>" /></div> 
</form> 
</div> 






<?php
    } // end printAdminPage


  } // End class TrampPlugin

}


if (class_exists("TrampPlugin")) { 
  $tr_plugin = new TrampPlugin(); 
}  

//Initialize the admin panel 
if (!function_exists("TrampPlugin_ap")) { 
  function TrampPlugin_ap() { 
    global $tr_plugin; 
    if (!isset($tr_plugin)) { 
      return; 
    } 
    if (function_exists('add_options_page')) { 
    add_options_page('Tramp Plugin', 'Tramp Plugin', 9, basename(__FILE__), array(&$tr_plugin, 'printAdminPage')); 
    }
  }
}

//Actions and Filters    
if (isset($tr_plugin)) { 




  //Actions 
  add_action('activate_tramp/tramp.php',  array(&$tr_plugin, 'init'));  
  add_action('admin_menu', 'TrampPlugin_ap');
  add_action('wp_head', array(&$tr_plugin, 'addHeaderCode'), 1);

  //Filters 
  add_filter('the_content', array(&$tr_plugin, 'addContent'));
  add_filter('get_comment_author', array(&$tr_plugin, 'authorUpperCase'));
}

// notes: be sure to use
// wp_remote_get()
// wp_enqueue_script()
// 
?>
