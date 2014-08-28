#!/usr/bin/php
<?php
  $shortopts = "";
  $shortopts .= "x:";
  $shortopts .= "u:";
  $shortopts .= "d:";
  
  $options = getopt($shortopts);
  
  if (count($options) == 0) {
    echo "Usage: " . $argv[0] . " <options>\n\n";
    echo "  -x       Your XboxAPI API Key\n";
    echo "  -u       Your Xbox Profile User ID\n";
    echo "  -d       File save location\n";
    exit;
  }
  
  $base_uri = "https://xboxapi.com/v2/%s/game-clips";

  $xauth  = $options['x'];
  $xuid   = $options['u'];
  $output = $options['d'];
  
  $ch = curl_init(sprintf($base_uri, $xuid));
  
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-AUTH: " . $xauth));
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $gameclip_metadata = curl_exec($ch);
  curl_close($ch);

  $gameclip_metadata_decoded = json_decode($gameclip_metadata);

  foreach($gameclip_metadata_decoded AS $game_clip) {
    foreach ($game_clip->gameClipUris AS $gameClipUri) {
      if ($gameClipUri->uriType == "Download") {
        
        // creates the destination directory while ignoring any errors 
        // (maybe the destination already exists)
        @mkdir($output . "/" . $game_clip->titleName,0755,true);
        
        if (!file_exists($output . "/" . $game_clip->titleName)) {
          echo "Failed to create destination directory: " . $output . "/" . $game_clip->titleName . "\n";
          exit;
        }
        
        // generate a clip name that includes the last modified date and, if set, the user caption
        // (the title given when using Upload Studio) or simply the game clip id if it's directly
        // from the game
        if ($game_clip->userCaption != "") {
          $filename = $output . "/" . $game_clip->titleName . "/[" . $game_clip->lastModified . "] " . $game_clip->userCaption . ".mp4";
        }
        else {
          $filename = $output . "/" . $game_clip->titleName . "/[" . $game_clip->lastModified . "] " . $game_clip->gameClipId  . ".mp4";
        }
        
        // if the destination file already exists just skip it
        if (!file_exists($filename)) {
          echo sprintf("Downloading \"%s\"...", ($game_clip->userCaption != "") ? $game_clip->userCaption:$game_clip->gameClipId);
          file_put_contents($filename, file_get_contents($gameClipUri->uri));
          echo "done\n";
        }
        else {
          echo sprintf("\"%s\" already exists\n", ($game_clip->userCaption != "") ? $game_clip->userCaption:$game_clip->gameClipId);
        }
      }

    }

  }
?>
