**GameDVR2Local**

Saves all GameDVR and Upload Studio clips to your computer

**Requirements**

Two requirements right now:

  * You'll need a free account at https://xboxapi.com in order to use this script
  * You'll need to run this on a Linux or Mac based computer, or if you're savvy you can get it to run on Windows

**Running**

```
Usage: ./get_game_clips.php <options>

  -x       Your XboxAPI API Key
  -u       Gamertag to fetch videos for (Optional, do not provide to fetch your own)
  -d       File save location
  -l       List games clips are available for
  -g       Game to grab clips for. All are grabbed by default.
```

**Examples**

Show what games are available for a user

```
./get_game_clips.php -x<your xbox api key> -l -uRealAngryMonkey
```

Download specific game clips for a specific user

```
./get_game_clips.php -x<your xbox api key> -g"Game Title" -uRealAngryMonkey -d<directory to save files to>
```

Download all of your own game clips

```
./get_game_clips.php -x<your xbox api key> -d<directory to save files to>
```