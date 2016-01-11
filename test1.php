#!/usr/bin/php
<?php
	// config
	$config = array( 
		'running' => true,
		'blackholeipfile' => 'blackholeips' 
	);
	logstr("Booting....");
	logstr("Loading routes from file ". $config['blackholeipfile']);
	if(!is_file($config['blackholeipfile'])) {
		if(!is_dir($config['blackholeipfile'])) {
			logstr2("WARNING","blackhole ip file not found, creating empty file");
		}
	}
	
	
	
	echo "STUB: start watching file\n";

	$inotifyObject = inotify_init();
	
	$watch_descriptor = inotify_add_watch($inotifyObject, 'badips', IN_MODIFY);
	echo "STUB: watching file!\n";		
	while($config['running']) {
		usleep(100);
		echo "waihing...";
		$inotify_event = inotify_read($inotifyObject);
		echo "hee, een event!\n";
		if(($inotify_event[0]['mask'] & IN_IGNORED) == IN_IGNORED) {
			echo "file deleted, start recovery";
		}
	}

	function logstr($str_msg) {
		logstr2("INFO",$str_msg);
	}
	function logstr2($loglevel,$str_msg) {
		echo "[".date("Y-m-d h:i:s")."] [".$loglevel."] ".$str_msg . "\n";
	}
?>
