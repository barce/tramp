<?php
/*
Plugin Name: tramp
Plugin URI: http://github.com/barce/tramp
Version: 0.1
Author: Barce
Author URI: http://twitter.com/barce
Description: Tramp stands for traffic amplifier. This plug-in amplifies traffic on a WordPress about page by applying keywords that are trending from Twitter.com. 
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
     * addTrendFooter -- adds a footer with Twitter trends that much the post
     */
    function addTrendFooter($content='') {
      $trampOptions = $this->getAdminOptions();
      if ($trampOptions['add_content'] == 'false')
        return $content;

		global $wp_query;
      $s_original = $content;

      // for all words in the post create one string from two words 
      // for all words in the post create one string from three words 
      // for all words in the post create one string from four words 
      preg_match_all('/\b[A-Za-z]+\b/', $s_original, $a_matches);
      $a_words = $a_matches[0];
      for ($i = 0; $i < count($a_words); $i++) {
        $s_new_word = $a_words[$i] . $a_words[$i+1]; 
        $s_original .= " $s_new_word ";
        $s_new_word = $a_words[$i] . $a_words[$i+1] . $a_words[$i+2]; 
        $s_original .= " $s_new_word ";
        $s_new_word = $a_words[$i] . $a_words[$i+1] . $a_words[$i+3]; 
        $s_original .= " $s_new_word ";
      }

      $post_date = the_date('Y-m-d', '', '', FALSE);

      if ($trampOptions['by_week'] == 'true') {
        $url = 'http://search.twitter.com/trends/weekly.json?date=' . 
               $post_date;
      } else {
        $url = 'http://search.twitter.com/trends.json';
      }

		// cache data here
		$post_ID = $wp_query->post->ID;
		$json['body'] = get_post_meta($post_id, "tramp_$post_ID", 1);
		if (strlen($json['body']) > 0) {
		  // use cached data
		} else {
		  $json = wp_remote_get($url);
		  $meta_key = "tramp_$post_ID";
		   add_post_meta($post_id, $meta_key, $json['body'], 1);
		}
      $trends = json_decode($json['body']);



      $content .= "<p>{$trampOptions['content']}</p>";
		// $content .= "($post_ID)";
		// $content .= "******<p>$s_original</p>*****";

      if ($trampOptions['by_week'] == 'true') {
        $a_names = array();
        for ($i = 0; $i <= 6; $i++) {
          $my_date = date('Y-m-d', strtotime("$post_date -{$i} days"));
          $a_list = $trends->trends->{$my_date};
          if (is_array($a_list)) {
            foreach ($a_list as $trend) {
              $s_trend = str_replace("#", "", $trend->name);
              if (preg_match("/.*{$s_trend}.*/i", $s_original)) {
                if (in_array(strtolower($trend->name), $a_names)) {
                  // don't print
                } else {
                  $s_encoded_query = urlencode($trend->query);
                  $content .= "&raquo;<a href='http://search.twitter.com/search?q={$s_encoded_query}'>";
                  $content .= "{$trend->name}</a>&nbsp;\n";
                }
                $a_names[] = strtolower($trend->name);
              }
            }
          }
        }
/*
        ob_start();
        print_r($trends->trends->{$my_date});
        print_r($trends->trends);
        $o_this = ob_get_contents();
        ob_end_clean();
        $content .= "<pre>[[$o_this]]</pre>\n";
*/

      }

      if ($trampOptions['by_week'] == 'false') {
        $a_list = $trends->trends;
        foreach ($a_list as $trend) {
          $s_trend = str_replace("#", "", $trend->name);
          if (preg_match("/.*{$s_trend}.*/i", $s_original)) {
            $content .= "&raquo;<a href='{$trend->url}'>{$trend->name}</a>&nbsp;\n";
          }
        }
      }
      return $content;
    } 

    function authorUpperCase($author='') {
      $trampOptions = $this->getAdminOptions();
      if ($trampOptions['comment_author'] == 'true') {
        return strtoupper($author);
      } else {
        return $author;
      }
    } 


    function getAdminOptions() {
      $trampAdminOptions = array('show_header' => 'true',
        'add_content'=> 'true',
        'comment_author' => 'false',
        'by_week' => 'true',
        'content' => '',
		  'consumer_key' => '',
		  'consumer_secret' => '',
		  'access_token' => '',
		  'access_secret' => ''
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
        if (isset($_POST['trampByWeek'])) {
          $trampOptions['by_week'] = $_POST['trampByWeek'];
        }
        if (isset($_POST['trampAuthor'])) {
          $trampOptions['comment_author'] = $_POST['trampAuthor'];
        }
        if (isset($_POST['trampContent'])) {
          $trampOptions['content'] = apply_filters('content_save_pre', $_POST['trampContent']);
        }
        if (isset($_POST['trampConsumerKey'])) {
          $trampOptions['consumer_key'] = apply_filters('content_save_pre', $_POST['trampConsumerKey']);
        }
        if (isset($_POST['trampConsumerSecret'])) {
          $trampOptions['consumer_secret'] = apply_filters('content_save_pre', $_POST['trampConsumerSecret']);
        }
        if (isset($_POST['trampAccessToken'])) {
          $trampOptions['access_token'] = apply_filters('content_save_pre', $_POST['trampAccessToken']);
        }
        if (isset($_POST['trampAccessSecret'])) {
          $trampOptions['access_secret'] = apply_filters('content_save_pre', $_POST['trampAccessSecret']);
        }
        update_option($this->adminOptionsName, $trampOptions);

        echo  '<div class="updated"><p><strong>';
        _e("Settings Updated.", "TrampPlugin");
        echo '</strong></p></div>';

      } // end if $_POST

      if (isset($_POST['generate_oauth'])) {

		} // end if $_POST for generate_oauth

		
?>


<style>
#wpfooter {
  visibility: hidden;
}
</style>
<div class=wrap> 
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>"> 
<h2>Tramp Plugin</h2> 
<h3>Content to Add to the End of a Post</h3> 
<textarea name="trampContent" style="width: 80%; height: 100px;"><?php 
_e(apply_filters('format_to_edit',$trampOptions['content']), 
'TrampPlugin') ?></textarea> 

<h3>Twitter Credentials</h3>
<p><label for="trampConsumerKey">Consumer Key:</label><input type="text" id="trampConsumerKey" name="trampConsumerKey" value="<?php print $trampOptions['consumer_key']; ?>"/></p>
<p><label for="trampConsumerSecret">Consumer Secret:</label><input type="text" id="trampConsumerSecret" name="trampConsumerSecret" value="<?php print $trampOptions['consumer_secret']; ?>"/></p>
<p><label for="trampAccessToken">Access Token:</label><input type="text" id="trampAccessToken" name="trampAccessToken" value="<?php print $trampOptions['access_token']; ?>"/></p>
<p><label for="trampAccessSecret">Access Secret:</label><input type="text" id="trampAccessSecret" name="trampAccessSecret" value="<?php print $trampOptions['access_secret']; ?>"/></p>
<input type="submit" name="generate_oauth" 
value="<?php _e('Generate OAuth', 'TrampPlugin') ?>" /></div> 




<br/>
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

<h3>Get Twitter Trends by Week?</h3>
<p>Selecting "No" will get most current trends.</p>
<p><label for="trampByWeek_yes"><input type="radio" 
id="trampByWeek_yes" name="trampByWeek" value="true" <?php if 
($trampOptions['by_week'] == "true") { _e('checked="checked"', 
"TrampPlugin"); }?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label 
for="trampByWeek_no"><input type="radio" id="trampByWeek_no" 
name="trampByWeek" value="false" <?php if ($trampOptions['by_week'] 
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
  add_filter('the_content', array(&$tr_plugin, 'addTrendFooter'));
  add_filter('get_comment_author', array(&$tr_plugin, 'authorUpperCase'));
}

// notes: be sure to use
// wp_remote_get()
// wp_enqueue_script()
// 
?>
