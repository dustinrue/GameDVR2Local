#!/usr/bin/php
<?php


  date_default_timezone_set('Etc/GMT');
  $shortopts = "";
  $shortopts .= "x:";
  $shortopts .= "u::";
  $shortopts .= "d:";
  $shortopts .= "l::";
  $shortopts .= "g::";
  
  $options = getopt($shortopts);
  
  if (count($options) == 0) {
    printf("Usage: %s <options>\n\n", $argv[0]);
    printf("  -x       Your XboxAPI API Key\n");
    printf("  -u       Xbox Gamertag to grab clips for, defaults to you\n");
    printf("  -d       File save location\n");
    printf("  -l       List games clips are available for\n");
    printf("  -g       Game to grab clips for. All are grabbed by default.\n");
    exit;
  }
  
  $base_uri = "https://xboxapi.com/v2/%s/game-clips";
  $show_available_games = false;
  
  $xauth  = $options['x'];
  
  if (array_key_exists('u', $options)) {
    if ($options['u'] == '') {
      printf("You can't leave a space behind -u because PHP is stupid\n");
      exit;
    }
    $gt   = $options['u'];   
    $url = sprintf("https://xboxapi.com/v2/xuid/%s", $gt);
    $xuid_response = do_request($url, $xauth);
    $xuid = $xuid_response;
  }
  else {
    $url = "https://xboxapi.com/v2/accountXuid";
    $xuid_response = do_request($url, $xauth);
    $xuid = json_decode($xuid_response)->xuid;
  }
 
  if (array_key_exists('d', $options)) {
    $output = $options['d'];
  }
  
  
  if (array_key_exists('g', $options)) {
    $game = $options['g'];
  }
  
  $gameclip_metadata = do_request(sprintf($base_uri, $xuid), $xauth);
  $gameclip_metadata_decoded = json_decode($gameclip_metadata);

  if (array_key_exists('l', $options)) {
    show_availabe_game_clips($gameclip_metadata_decoded);
    exit;
  }
  
  foreach($gameclip_metadata_decoded AS $game_clip) {
    if (isset($game) && $game_clip->titleName != $game)
      continue;
    
    foreach ($game_clip->gameClipUris AS $gameClipUri) {
      if ($gameClipUri->uriType == "Download") {
        
        // creates the destination directory while ignoring any errors 
        // (maybe the destination already exists)
        @mkdir($output . DIRECTORY_SEPARATOR . $game_clip->titleName,0755,true);
        
        if (!file_exists($output . DIRECTORY_SEPARATOR . $game_clip->titleName)) {
          printf("Failed to create destination directory: %s%s%s\n", $output, DIRECTORY_SEPARATOR, $game_clip->titleName);
          exit;
        }
        
        $filename = generate_filename($output, $game_clip);
        
        // if the destination file already exists just skip it
        if (!file_exists($filename)) {
          printf("Downloading \"%s\"...", ($game_clip->userCaption != "") ? $game_clip->userCaption:$game_clip->gameClipId);
          //file_put_contents($filename, file_get_contents($gameClipUri->uri));
          download($filename, $gameClipUri->uri);
          touch($filename, strtotime($game_clip->dateRecorded));
          echo "done\n";
        }
        else {
          touch($filename, strtotime($game_clip->dateRecorded));
          printf("\"%s\" already exists\n", ($game_clip->userCaption != "") ? $game_clip->userCaption:$game_clip->gameClipId);
        }
      }

    }

  }
  function generate_filename($output_dir, $game_clip) {
    if ($game_clip->titleName == "") {
      printf("Game clip titles are coming back empty, bailing out\n");
      exit;
    }
    if ($game_clip->userCaption != "") {
      $filename = $output_dir . DIRECTORY_SEPARATOR . $game_clip->titleName . DIRECTORY_SEPARATOR . $game_clip->userCaption . " (" . $game_clip->gameClipId . ")" . ".mp4";
    }
    else {
      $filename = $output_dir . DIRECTORY_SEPARATOR . $game_clip->titleName . DIRECTORY_SEPARATOR . $game_clip->gameClipId  . ".mp4";
    }
    
    return $filename;
  }
  
  function do_request($url, $xauth) {
    $ch = curl_init($url);
  
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-AUTH: " . $xauth));
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
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
  
  function download($filename, $download_url) {
    $source_file = fopen($download_url, 'r');
    $output_file = fopen($filename, 'w');
    
    while (($content = fgets($source_file)) !== false) {
      fputs($output_file, $content);
    }
    
    fclose($source_file);
    fclose($output_file);
    
  }
    
?>
