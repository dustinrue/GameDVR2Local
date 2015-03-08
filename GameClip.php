<?php

Class GameClip {
  var $output_filename;
  var $clip_data;
  var $gt;
  
  public function GameClip($clip_data = null, $output_filename = null) {
    $this->clip_data = $clip_data;
    $this->output_filename = $output_filename;
  }
 
 
  public function download() {
    
    if (is_null($this->output_filename)) {
      throw new Exception("output dir or gamer tag not set");
    }

    $game_clip_uris = $this->clip_data->gameClipUris;
    foreach ($game_clip_uris AS $game_clip_uri) {
      
        if ($game_clip_uri->uriType == "Download") {
          $filename = $this->generate_filename();
          
          // creates the destination directory while ignoring any errors
          // (maybe the destination already exists)
          @mkdir(dirname($filename),0755,true);

          if (!file_exists(dirname($filename))) {
            printf("Failed to create destination directory: %s\n", dirname($filename));
            exit;
          }

          // if the destination file already exists just skip it
          if (!file_exists($filename)) {
            printf("Downloading \"%s\"...", ($this->clip_data->userCaption != "") ? $this->clip_data->userCaption:$this->clip_data->gameClipId);
            //file_put_contents($filename, file_get_contents($gameClipUri->uri));
            //download($filename, $gameClipUri->uri);
            //if ($new_video_output_file) {
            //  fputs($new_video_output_file, sprintf("%s\n",$filename));
            //}
            
            
            $source_file = fopen($game_clip_uri->uri, 'r');
            $output_file = fopen($filename, 'w');

            while (($content = fgets($source_file)) !== false) {
              fputs($output_file, $content);
            }

            fclose($source_file);
            fclose($output_file);
            touch($filename, strtotime($this->clip_data->dateRecorded));
            echo "done\n";
          }
          else {
            touch($filename, strtotime($this->clip_data->dateRecorded));
            printf("\"%s\" already exists\n", ($this->clip_data->userCaption != "") ? $this->clip_data->userCaption:$this->clip_data->gameClipId);
          }
        }

      }
    return;
    

  }
  
  private function generate_filename() {
    if ($this->clip_data->titleName == "") {
      throw new Exception("Clip doesn't have a title, this is bad and unusual");
    }
    
    if (empty($this->gt)) {
      throw new Exception("No gamertag set, please set one");
    }
    if ($this->clip_data->userCaption != "") {
      $filename = $this->output_filename . DIRECTORY_SEPARATOR . $this->gt . DIRECTORY_SEPARATOR . $this->clip_data->titleName . DIRECTORY_SEPARATOR . $this->clip_data->userCaption . " (" . $this->clip_data->gameClipId . ")" . ".mp4";
    }
    else {
      $filename = $this->output_filename . DIRECTORY_SEPARATOR . $this->gt . DIRECTORY_SEPARATOR . $this->clip_data->titleName . DIRECTORY_SEPARATOR . $this->clip_data->gameClipId  . ".mp4";
    }

    return $filename;
  }
}