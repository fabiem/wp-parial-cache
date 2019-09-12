<?php
/**
* Plugin Name: WP Partial Cache
* Plugin URI: https://github.com/fabiem/wp-partial-cache
* Description: WP cache plug for developper
* Version: 1.0
* Author: Fabien MOTTA
* Author URI: https://fabienmotta.com
**/



function has_no_cache($section, $time = 120, $user = false, $page = null)
{

  if(function_exists('get_current_version'))
  {
    $GLOBALS['partialcacheversion'] = get_current_version();
  }
  else
  {
    $GLOBALS['partialcacheversion'] = '';
  }

  if($page == null)
  {
    $page = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
  }

  $folder = hash('sha256', $page);

  $file = $section;
  if($user)
  {
    if(is_user_logged_in()) $file = $section.'-'.get_current_user_id();
    else $file = $section.'-0';
  }

  $file = hash('sha256', $file);


  $GLOBALS['pathtopartialcachedir'] = get_home_path().'wp-content/partialcache/'.$folder;
  $GLOBALS['pathtopartialcache'] = $GLOBALS['pathtopartialcachedir'].'/'.$file.$GLOBALS['partialcacheversion'];

  if(isset($_GET['clearcache']))
  {
    ob_start();
    return true;
  }

  if(file_exists($GLOBALS['pathtopartialcache']))
  {
    if($time > 0)
    {
      $mdate = date("YmdHi", filemtime($GLOBALS['pathtopartialcache']));
      $date  = date("YmdHi", time()-$time*60);
      if ($mdate < $date)
      {
        ob_start();
        return true;
      }
    }
    $cache = file_get_contents($GLOBALS['pathtopartialcache']);
    echo '<!-- from cache '.$page.' : '.$folder.'/'.$file.$GLOBALS['partialcacheversion'].' -->';
    echo $cache;
    echo '<!-- /from cache -->';
    return false;
  }
  else
  {
    ob_start();
    return true;
  }

}

function deleteDirectory($dir) {
    if (!file_exists($dir)) {

        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }

    }

    return rmdir($dir);
}


function purge_cache($url)
{

  $folder = hash('sha256', $url);
  $folder = get_home_path().'wp-content/partialcache/'.$folder;
  deleteDirectory($folder);
}


function save_cache()
{
  $cache = ob_get_clean();
  echo '<!-- no cache -->';
  echo $cache;
  if(!is_dir(get_home_path().'wp-content/partialcache')) mkdir(get_home_path().'wp-content/partialcache');
  if (!is_dir($GLOBALS['pathtopartialcachedir'])) mkdir($GLOBALS['pathtopartialcachedir']);
  file_put_contents($GLOBALS['pathtopartialcache'], $cache);
  echo '<!-- cache saved -->';
}


function menu_mec_bo()
{
    add_submenu_page( 'options-general.php', 'Partial cache', 'Partial cache', 'manage_options', 'partial-cache', 'partial_cache_admin' );
}
add_action("admin_menu", "menu_mec_bo");

function partial_cache_admin()
{

    echo '<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/themes/redmond/jquery-ui.min.css" />';
    echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br /></div><h2>Partial cache</h2><br /><br />';
    echo '<form action="" method="post"> URL to pruge : <input type="text" name="urltopurge" /><input type="submit" value="Purge" class="button" /></form>';

    if(isset($_POST['urltopurge']))
    {
      purge_cache($_POST['urltopurge']);
      echo 'The page cache has been purged';
    }

}

function purge_cache_post($post_ID, $post_after, $post_before)
{

    $url = get_permalink($post_ID);
    purge_cache($url);
}

add_action( 'post_updated', 'purge_cache_post', 10, 3 );
