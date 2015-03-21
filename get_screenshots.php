#!/usr/bin/php
<?php

  define("BASE_URI", "https://xboxapi.com/v2");
  
  $our_dir = dirname(__FILE__);
  require_once($our_dir . DIRECTORY_SEPARATOR . "Screenshot.php");
  require_once($our_dir . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php");
  
  $thing = XboxLiveUser::withCachedCredentials("thing", "derp");
  print_r($thing);
  exit;
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
    printf("  -o       If passed any files downloaded will be output to <gamertag>.txt\n");
    exit;
  }

  $show_available_games = false;
  $output_work_done = false;

  if (array_key_exists('x', $options)) {
    define("APIKEY", $options['x']);
  }
  else {
    printf("I need your xboxapi.com API key\n");
    exit;
  }
  

  if (array_key_exists('u', $options)) {
    if ($options['u'] == '') {
      printf("You can't leave a space behind -u because PHP is stupid\n");
      exit;
    }
    $gts = $options['u'];
    $gta = explode(',',$gts);
    foreach ($gta as $gt) {
      try {
        $data = get_data_for_gamertag($gt);
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
      $data = get_data_for_gamertag();
    }
    catch(Exception $e) {
      echo $e->getMessage();
      exit;
    }
    $xuids[] = array(
      'gamertag' => $data->gamertag,
      'xuid' => $data->xuid,
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
    $gameclip_metadata = do_request(sprintf(BASE_URI . "/%s/screenshots", $xuid['xuid']));

    foreach($gameclip_metadata AS $gameclip) {
      $clip_object = new Screenshot($gameclip,$output);
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
    echo $url . "\n";
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
