#!/usr/bin/php
<?php
	/********
	* Author: Bas van Elburg (b.van.elburg@global-datacenter.nl
	*/ 
 
	// config
	$config = array( 
		'running' => true,
		'debug' => false, // warning, this will produce debug data to stdout!
		'blackholeipfile' => '/home/bas/ExaRTBH/blackholeips',
		'filecheckinterval' => 5,
		'blackhole_bgp_community' => "65001:666",
		'blackhole_nexthop' => '192.6.6.6'
	);

	// "internal" list of active blackhole routes
	$blackholes = array();
	

	logstr("Booting....");
	while($config['running']) {
		// does the file exist? no? write memory to file, else parse 
		if(!file_exists($config['blackholeipfile'])) {
			// file does not exist, create one and write known blackhole entries to file!
                        logstr2("WARNING","blackhole ip file not found, (re)creating file [" . $config['blackholeipfile'] . "]");
			generateBlackholeFile();
        	} else {
			// load entries from blackhole file
			$newBlackholeEntries = loadBlackholesFromFile($config['blackholeipfile']);

			// loop trough new blackhole entries to see what action is needed
			foreach($newBlackholeEntries as $newBlackhole) {
				// loop trough new set of prefixes
				$prefixFound = false;
				foreach($blackholes as $key => $blackhole) {
					if($blackhole == $newBlackhole) {
						// blackhole is allready active, no action required
						unset($blackholes[$key]); // remove from current list, will be replaced when new list is processed
						$prefixFound = true;
						break;
					}
				}
				if(!$prefixFound) {
					echo $newBlackhole->announceString() . "\n";
				}
			} 
			
			//process prefixes in current list to withdrawel these routes
			foreach($blackholes as $blackhole) {
				echo $blackhole->withdrawString()."\n";
			}

			//processing complete, replace old list with the new one
			$blackholes = $newBlackholeEntries;
		}

		// generate hash of file
        	$config['blackholiphash'] = md5_file($config['blackholeipfile']);
	        logstr("generated hash: " . $config['blackholiphash']);
		
		// wait for change of file
		logstr("waiting...");
		
		while(@md5_file($config['blackholeipfile']) == $config['blackholiphash']) {
			sleep($config['filecheckinterval']);
		}  
			
		logstr("file changed, reloading!");
		
	}
	
	/* support fuctions */
	function logstr($str_msg) {
		logstr2("INFO",$str_msg);
	}
	function logstr2($loglevel,$str_msg) {
		global $config;
		if($config['debug']) {
			echo "[".date("Y-m-d H:i:s")."] [".$loglevel."] ".$str_msg . "\n";
		}
	}

	// load blackholes from file and return them als an array of BlackHoleEntries
	function loadBlackholesFromFile($file) {
		global $config;
		$newBlackholes = array();
		// tbd: yes i know this should have error / input checking
		foreach(file($file) as $row) {
			$row = trim($row); // trim the line
			// if it is not a comment 
			if(substr($row,0,1) != "#") { 
				// parse the row, it is 1 of the following formats:
				//  network/prefixlen (127.0.0.1/32) (only /32 supported right now)
				// more formats will be supported in the future 
				if(preg_match("/(.*)\/(32)$/",$row)) {
					$newBlackholes[] = new BlackholeEntry(trim($row),$config['blackhole_bgp_community'],$config['blackhole_nexthop']);
				} else {
					logstr2("WARNING", "Ignoring entry (unexpected format) [".$row."]");
				}
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
		public $localpref = 4000;
		function __construct($_prefix,$_bgp_community,$_nexthop) {
			$this->prefix = $_prefix;
			$this->bgp_community = $_bgp_community;
			$this->nexthop = $_nexthop;	
		}

		// returns the string to add to the blackhole file (including trailing \n)
		function toBlackholeFileString() {
			return $this->prefix . "\n";
		}
		// tbd add local pref to data class
		// tbd better checks
		function announceString() { return "announce route $this->prefix next-hop $this->nexthop local-preference $this->localpref community [$this->bgp_community]"; }
		function withdrawString() { return "withdraw route $this->prefix next-hop $this->nexthop local-preference $this->localpref community [$this->bgp_community]"; }
	}
?>
