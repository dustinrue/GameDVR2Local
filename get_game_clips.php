#!/usr/bin/php
<?php
  date_default_timezone_set('Etc/GMT');
  $shortopts = "";
  $shortopts .= "x:";
  $shortopts .= "u::";
  $shortopts .= "d:";
  
  $options = getopt($shortopts);
  
  if (count($options) == 0) {
    echo "Usage: " . $argv[0] . " <options>\n\n";
    echo "  -x       Your XboxAPI API Key\n";
    echo "  -u       Xbox Profile User ID to grab clips for, defaults to you\n";
    echo "  -d       File save location\n";
    exit;
  }
  
  $base_uri = "https://xboxapi.com/v2/%s/game-clips";
  
  
  $xauth  = $options['x'];
  
  if (array_key_exists('u', $options)) {
    $xuid   = $options['u'];   
  }
  else {
    $url = "https://xboxapi.com/v2/accountXuid";
    $xuid_response = do_request($url, $xauth);
    $xuid = json_decode($xuid_response)->xuid;
  }
 
  $output = $options['d'];
  
  $gameclip_metadata = do_request(sprintf($base_uri, $xuid), $xauth);
  $gameclip_metadata_decoded = json_decode($gameclip_metadata);

  foreach($gameclip_metadata_decoded AS $game_clip) {
    foreach ($game_clip->gameClipUris AS $gameClipUri) {
      if ($gameClipUri->uriType == "Download") {
        
        // creates the destination directory while ignoring any errors 
        // (maybe the destination already exists)
        @mkdir($output . DIRECTORY_SEPARATOR . $game_clip->titleName,0755,true);
        
        if (!file_exists($output . DIRECTORY_SEPARATOR . $game_clip->titleName)) {
          echo "Failed to create destination directory: " . $output . DIRECTORY_SEPARATOR . $game_clip->titleName . "\n";
          exit;
        }
        
        $filename = generate_filename($output, $game_clip);
        
        // if the destination file already exists just skip it
        if (!file_exists($filename)) {
          echo sprintf("Downloading \"%s\"...", ($game_clip->userCaption != "") ? $game_clip->userCaption:$game_clip->gameClipId);
          file_put_contents($filename, file_get_contents($gameClipUri->uri));
          touch($filename, strtotime($game_clip->dateRecorded));
          echo "done\n";
        }
        else {
          touch($filename, strtotime($game_clip->dateRecorded));
          //echo sprintf("\"%s\" already exists\n", ($game_clip->userCaption != "") ? $game_clip->userCaption:$game_clip->gameClipId);
        }
      }

    }

  }
  function generate_filename($output_dir, $game_clip) {
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
?>
