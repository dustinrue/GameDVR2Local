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
  $stuff = curl_exec($ch);
  curl_close($ch);

  $content = json_decode($stuff);

  foreach($content AS $clip) {
    foreach ($clip->gameClipUris AS $gameClipUri) {
      if ($gameClipUri->uriType == "Download") {
        @mkdir($output . "/" . $clip->titleName,0750,true);
        
        if ($clip->userCaption != "") {
          $filename = $output . "/" . $clip->titleName . "/[" . $clip->lastModified . "] " . $clip->userCaption . ".mp4";
        }
        else {
          $filename = $output . "/" . $clip->titleName . "/[" . $clip->lastModified . "] " . $clip->gameClipId  . ".mp4";
        }
        
        if (!file_exists($filename)) {
          file_put_contents($filename, file_get_contents($gameClipUri->uri));
        }
        else {
          echo $clip->gameClipId . " already exists\n";
        }
      }

    }

  }
?>
