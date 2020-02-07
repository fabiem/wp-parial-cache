<?php


function has_no_cache($section, $time = 120, $user = false, $page = null)
{
  if(isset($_GET['nocache'])) return true;

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
    $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
    $uri = $uri_parts[0];
    $page = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$uri}";
  }
  elseif($page === true)
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


  $GLOBALS['pathtopartialcachedir'] = partial_cache_dir().'/'.$folder;
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

function partial_cache_dir()
{
  if(!isset($GLOBALS['partialcachedirectory']))
  {
    $dir = wp_upload_dir();

    $dir = str_replace('uploads', 'wp-partial-cache', $dir['basedir']);
    $GLOBALS['partialcachedirectory'] = $dir;
  }

  return $GLOBALS['partialcachedirectory'];
}

function purge_cache($url, $section = '*')
{
  if(function_exists('get_current_version'))
  {
    $GLOBALS['partialcacheversion'] = get_current_version();
  }
  else
  {
    $GLOBALS['partialcacheversion'] = '';
  }

  if($url == '*')
  {
    foreach (scandir($dir) as $item)
    {
        if ($item == '.' || $item == '..')
        {
            continue;
        }
        $folder = $dir . DIRECTORY_SEPARATOR . $item;
        $file = hash('sha256', $section).$GLOBALS['partialcacheversion'];
        deleteDirectory($folder.'/'.$file);
      }

  }
  elseif($section == '*')
  {
    $folder = hash('sha256', $url);
    $folder = partial_cache_dir().'/'.$folder;
    deleteDirectory($folder);

  }
  else
  {
    $folder = hash('sha256', $url);
    $folder = partial_cache_dir().'/'.$folder;
    $file = hash('sha256', $section).$GLOBALS['partialcacheversion'];
    deleteDirectory($folder.'/'.$file);

    //
  }

}


function save_cache()
{
  if(isset($_GET['nocache'])) return false;
  $cache = ob_get_clean();
  echo '<!-- no cache -->';
  echo $cache;
  if(!is_dir(partial_cache_dir())) mkdir(partial_cache_dir());
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

    if(isset($GLOBALS['wppcachepurge']['id'][$post_ID]))
    {
      foreach($GLOBALS['wppcachepurge']['id'][$post_ID] as $topurge)
      {
        purge_cache($topurge[0], $topurge[1]);
      }
    }

    $ptype = get_post_type($post_ID);

    if(isset($GLOBALS['wppcachepurge']['type'][$ptype]))
    {
      foreach($GLOBALS['wppcachepurge']['type'][$ptype] as $topurge)
      {
        purge_cache($topurge[0], $topurge[1]);
      }
    }

}

add_action( 'post_updated', 'purge_cache_post', 10, 3 );
