#!/usr/bin/php
<?php
	/********
	* Author: Bas van Elburg (b.van.elburg@global-datacenter.nl
	*/ 
 
	// config
	$config = array( 
		'running' => true,
		'blackholeipfile' => 'blackholeips',
		'filecheckinterval' => 5,
		'blackhole_bgp_community' => "65001:666",
		'blackhole_nexthop' => '192.6.6.6'
	);

	// "internal" list of active blackhole routes
	$blackholes = array();
	

	logstr("Booting....");
	/*logstr("Loading routes from file ". $config['blackholeipfile']);
	
	if(!is_file($config['blackholeipfile'])) {
		if(!is_dir($config['blackholeipfile'])) {
			logstr2("WARNING","blackhole ip file not found, creating empty file");
		}
	}*/

// some debug testing black hole entries
$blackholes[] = new BlackHoleEntry("a","b","c");
$blackholes[] = new BlackHoleEntry("d","e","f");
$blackholes[] = new BlackHoleEntry("g","h","i");


	
	while($config['running']) {
		// does the file exist?
		if(!is_file($config['blackholeipfile'])) {
                	if(!is_dir($config['blackholeipfile'])) {
				// file does not exist, create one and write known blackhole entries to file!
                        	logstr2("WARNING","blackhole ip file not found, (re)creating file [" . $config['blackholeipfile'] . "]");
				generateBlackholeFile();
                	}
        	}
		// load entries from blackhole file
		$newBlackholeEntries = loadBlackholesFromFile($config['blackholeipfile']);
var_dump($newBlackholeEntries);
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

	// load blackholes from file and return them als an array of BlackHoleEntries
	function loadBlackholesFromFile($file) {
		global $config;
		$newBlackholes = array();
		// tbd: yes i know this should have error / input checking
		foreach(file($file) as $row) {
			$row = trim($row); // trim the line
			// if it is not a comment 
			if(substr($row,0,1) != "#") 
				// parse the row, it is 1 of the following formats:
				//  network/prefixlen (127.0.0.1/32) (only /32 supported right now)
				// more formats will be supported in the future 
				if(preg_match("/(.*)\/(32)$/",$row)) {
					$newBlackholes[] = new BlackholeEntry(trim($row),$config['blackhole_bgp_community'],$config['blackhole_nexthop']);
				} else {
					logstr2("WARNING", "Ignoring entry (unexpected format) [".$row."]");
			}
		}
		return $newBlackholes;
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
