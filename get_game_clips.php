#!/usr/bin/php
<?php

  define("BASE_URI", "https://xboxapi.com/v2");
  define("AUTHCACHE", "/tmp/auth_info");
  define("XUIDCACHE", "/tmp/xuid");
  define("EXPIRES", "/tmp/session_expires");
  
  $our_dir = dirname(__FILE__);
  require_once($our_dir . DIRECTORY_SEPARATOR . "GameClip.php");
  require_once($our_dir . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "dustinrue" . DIRECTORY_SEPARATOR . "php-xboxliveclient" . DIRECTORY_SEPARATOR . "XboxLiveUser.php");
  
  $xuid = file_get_contents(XUIDCACHE);
  $auth = file_get_contents(AUTHCACHE);
  $live = XboxLiveUser::withCachedCredentials($xuid, $auth);
  
  date_default_timezone_set('Etc/GMT');
  
  $shortopts = "";
  $shortopts .= "x:";
  $shortopts .= "u::";
  $shortopts .= "d:";
  $shortopts .= "l::";
  $shortopts .= "g::";
  $shortopts .= "o::";

  $options = getopt($shortopts);

  if (count($options) == 0) {
    printf("Usage: %s <options>\n\n", $argv[0]);
    printf("  -x       Your XboxAPI API Key\n");
    printf("  -u       Xbox Gamertag to grab clips for, defaults to you\n");
    printf("  -d       File save location\n");
    printf("  -l       List games clips are available for\n");
    printf("  -g       Game to grab clips for. All are grabbed by default.\n");
    printf("  -o       If passed any files downloaded will be output to <gamertag>.txt");
    exit;
  }

  $show_available_games = false;
  $output_work_done = false;  

  if (array_key_exists('u', $options)) {
    if ($options['u'] == '') {
      printf("You can't leave a space behind -u because PHP is stupid\n");
      exit;
    }
    $gts = $options['u'];
    $gta = explode(',',$gts);
    foreach ($gta as $gt) {
      try {
        $data = $live->fetchXuidForGamertag($gt);
      }
      catch(Exception $e) {
        echo $e->getMessage();
        exit;
      }
      
      $xuids[] = array(
        'gamertag' => $gt,
        'xuid' => $data,
      );
    }
  }
  else {
    try {
      $data = $live->fetchGamertagForXuid();
    }
    catch(Exception $e) {
      echo $e->getMessage();
      exit;
    }
    

    $xuids[] = array(
      'gamertag' => $data,
      'xuid' => $live->xuid,
    );
  }

  if (array_key_exists('d', $options)) {
    $output = $options['d'];
  } else {
    $output = getcwd();
  }

  if (array_key_exists('g', $options)) {
    $games = explode(',',$options['g']);
  }

  if (array_key_exists('o', $options)) {
    if ($options['o'] == '') {
      printf("You can't leave a space behind -o because PHP is stupid\n");
      exit;
    }
    $output_work_done = true;
  }
  
  
  // loop over all the xuids and either output
  // what is available for them or download them

  foreach ($xuids AS $xuid) {
    $live->xuid = $xuid['xuid'];
    $params = array();
    $gameclip_metadata = json_decode($live->fetchGameDVRClips($params));
    
    foreach($gameclip_metadata->gameClips AS $gameclip) {
      $clip_object = new GameClip($gameclip,$output);
      $clip_object->gt = $xuid['gamertag'];
      $clip_object->download();
    }
    
  }

  

  /**
   * 
   * @param type $url
   * @return type
   * @throws Exception
   */
  function do_request($url) {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-AUTH: " . APIKEY));
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $rawdata = curl_exec($ch);
    try {
      $response = json_decode($rawdata);
    }
    catch(Exception $e) {
      // it isn't json, just info, stupid API
      $response = $rawdata;
    }
    
    curl_close($ch);
 
    if (is_object($response) && property_exists($response, 'success') && empty($response->success)) {
      throw new Exception(sprintf("XboxAPI request failed with %s - %s", $response->error_code, $response->error_message));
    }
    
    return $response;
  }

  function show_availabe_game_clips($game_clip_metadata) {
    foreach($game_clip_metadata AS $game_clip) {
      $games[$game_clip->titleName] = $game_clip->titleName;
    }

    foreach($games AS $game_title => $value) {
      printf("%s\n", $game_title);
    }

  }
  
  function get_data_for_gamertag($gamertag = null) {
    
    if ($gamertag) {
      $url = sprintf(BASE_URI . "/xuid/%s", $gamertag);  
    }
    else {
      $url = BASE_URI . "/accountXuid";
    }
    
    try {
      $data = do_request($url);
    }
    catch (Exception $e) {
      throw $e;
    }
    
    return $data;
  }
  
  function get_gamertag_for_xuid($xuid) {
    if ($gamertag) {
      $url = sprintf(BASE_URI . "/xuid/%s", $gamertag);  
    }
    else {
      $url = BASE_URI . "/accountXuid";
    }
    
    try {
      $data = do_request($url);
    }
    catch(Exception $e) {
      throw $e;
    }
    
    return $data->xuid;
  }

?>
