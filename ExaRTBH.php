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

	// "internal" list of active blackhole routes
	$blackholes = array();
	

	logstr("Booting....");
	logstr("Loading routes from file ". $config['blackholeipfile']);
	
	if(!is_file($config['blackholeipfile'])) {
		if(!is_dir($config['blackholeipfile'])) {
			logstr2("WARNING","blackhole ip file not found, creating empty file");
		}
	}

// some debug testing black hole entries
$blackholes[] = new BlackHoleEntry("a","b","c");
$blackholes[] = new BlackHoleEntry("d","e","f");
$blackholes[] = new BlackHoleEntry("g","h","i");


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

	// generateBlackhole file content en write to file
	function generateBlackholeFile()
	{
		global $blackholes,$config;
		$string2write = "";
		foreach($blackholes as $bhEntry) {
			$string2write .= $bhEntry->toBlackholeFileString();
		}
		file_put_contents($config['blackholeipfile'],$string2write);
		logstr("blackholes written to new file");		
	}

	

	/* data classes */
	class BlackholeEntry {
		public $prefix;
		public $bgp_community;
		public $nexthop;
		function __construct($_prefix,$_bgp_community,$_nexthop) {
			$this->prefix = $_prefix;
			$this->bgp_community = $_bgp_community;
			$this->nexthop = $_nexthop;	
		}

		// returns the string to add to the blackhole file (including trailing \n)
		function toBlackholeFileString() {
			return $this->prefix . "\n";
		}
	}
?>
