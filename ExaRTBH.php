#!/usr/bin/php
<?php
	/********
	* Author: Bas van Elburg (b.van.elburg@global-datacenter.nl
	*/ 
 
	// config
	$config = array( 
		'running' => true,
		'blackholeipfile' => 'blackholeips',
		'filecheckinterval' => 5
	);

	
	logstr("Booting....");
	logstr("Loading routes from file ". $config['blackholeipfile']);
	
	if(!is_file($config['blackholeipfile'])) {
		if(!is_dir($config['blackholeipfile'])) {
			logstr2("WARNING","blackhole ip file not found, creating empty file");
		}
	}

	// should announce all loaded prefixes
	
	while($config['running']) {
	        // generate hash of file
        	$config['blackholiphash'] = md5_file($config['blackholeipfile']);
	        logstr("generated hash: " . $config['blackholiphash']);

		logstr("waiting...");
		while(md5_file($config['blackholeipfile']) == $config['blackholiphash']) {
			sleep($config['filecheckinterval']);
		}  
			
		logstr("file changed, reloading!");
		
	}
	
	/* support fuctions */
	function logstr($str_msg) {
		logstr2("INFO",$str_msg);
	}
	function logstr2($loglevel,$str_msg) {
		echo "[".date("Y-m-d h:i:s")."] [".$loglevel."] ".$str_msg . "\n";
	}
?>
