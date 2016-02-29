This php script will be started by ExaBGP [https://github.com/Exa-Networks/exabgp]
The script will process and announce the prefixes in the configured file with the given next-hop and community. If you remove the prefix from the file it will be withdrawn.

= Installation =
requirements: php, exabgp

= configuration =
1. edit ExaRBTH.php with the right configuration parameters
2. configure exabgp to use ExaRBTH:
	process announce-routes {
        	run /path/to/ExaRTBH.php;
    	}
3. reload ExaBGP


