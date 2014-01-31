<?
	// autoloader
	class Loader
	{
	    static function loadClass($className)
	    {
	    	// generics
	    	$parts = explode("\\", $className);
	    	switch($parts[0])
	    	{
	    		case "helpers":
	    			include dirname(__FILE__)."/helpers/".$parts[1].".php";
	    		break;
	    	}
	    	
	    	// application
			$parts = explode("_", $className);
			
			// business object
			if(count($parts) == 1)
				$load = dirname(__FILE__)."/class/$className.php";
			else
			{
				// others
				$lastPartIndex = count($parts) - 1;
				unset($parts[$lastPartIndex]);
				$load = dirname(__FILE__);
				foreach($parts as $part)
					$load .= "/$part";
				$load .= "/$className.php";
			}
			
			// include file
			!file_exists($load) ?: require($load);
			// return true if class is loaded
			return class_exists($className, false);
	    }
	    
	    static function register()
	    {
			spl_autoload_register("Loader::loadClass");
	    }
	    
	    static function unregister()
	    {
			spl_autoload_unregister("Loader::loadClass");
	    }
	}
	Loader::register();
	
	// lesture des paramètres
	$options = getopt("", array("ini::", "clean-bootstrap"));
	
	// paramètres par défaut
	$iniFileName = "parseYaml.ini";
	
	// traitement des paramètres
	foreach($options as $option => $value)
		switch($option)
		{
			case "debug":
				$debug = true;
				$verbose = true;
			break;
		
			case "verbose":
				$verbose = true;
			break;
		
			case "ini":
				$iniFileName = $value;
			break;
		}
	
	// lecture du fichier ini
	$ini = new parseYamlIni($iniFileName);
	
	// lancement conversion mwb en yaml
	$tempconversionDirectory = "/tmp/parseYaml";
	exec("rm -rf $tempconversionDirectory");
	mkdir($tempconversionDirectory);
	echo "Change config ? [no]";
	exec("echo 'n' | php ".dirname(__FILE__)."/mwbConverter/cli/export.php --export=doctrine2-yaml --no-auto-config ".$ini->models->mwbFileName." $tempconversionDirectory > /dev/null");
	
	// lancement du parsing
	$parseProject = new projectParsing($ini, $tempconversionDirectory);
	$parseProject->parseAll();
?>
