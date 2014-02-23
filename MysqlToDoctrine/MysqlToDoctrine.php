<?php
	/**
	 * This file is a part of SebkMysqlToDoctrineBundle
	 * Copyright 2014 - Sébastien Kus
	 * Under GNU GPL V3 licence
	 */
	namespace Sebk\MysqlToDoctrineBundle\MysqlToDoctrine;
	
	use Symfony\Component\Yaml\Parser;
	use Symfony\Component\Yaml\Exception\ParseException;
	use Symfony\Component\Yaml\Dumper;
	use Twig_Environment as Environment;
	use Twig_Filter_Function;
	
	class MysqlToDoctrineException extends \Exception {}
	
	class MysqlToDoctrine
	{
		protected $yamlParser;
		protected $yamlDumper;
		protected $templating;
		protected $bundle = null;
		protected $dumpOutput;
		protected $yamlObjects = array();
		protected $config;
		
		public function __construct(Environment $templating, $dumpOutput = true)
		{
			$this->yamlParser = new Parser;
			$this->dumpOutput = new Dumper;
			$templating->addFilter('dump', new Twig_Filter_Function('var_dump'));
			$this->templating = $templating;
		}
		
		public function setBundle($bundleName)
		{
			$this->bundle = $bundleName;
			$this->config = new Config($this->bundle);
			
			return $this;
		}
		
		public function getConfig()
		{
			return $this->config;
		}
		
		public function createEntities()
		{
			// is bundle set ?
			if($this->bundle === null)
				return false;
			
			// create temporary directory to parse mwb file to yaml
			$tmp = tempnam(sys_get_temp_dir(), "parseYaml");
			if (file_exists($tmp))
				unlink($tmp);
			mkdir($tmp);
			
			// read parameters from config
			$configObject = $this->config->getConfigAsArray();
			
			$mysqlFilename = __DIR__."/../Resources/config/".$this->config->getBundle().".mwb";
			if(!file_exists($mysqlFilename))
				throw new MysqlToDoctrineException("You must place the mwb file with the name 'Sebk/MysqlToDoctrineBundle/Resources/config/".$this->config->getBundle().".mwb'");
			
			// create configuration file
			file_put_contents($tmp.'/config.json', '{"export":"doctrine2-yaml","zip":false,"dir":"'.$tmp.'","params":{"indentation":4,"useTabs":false,"filename":"%entity%.dcm.%extension%","skipPluralNameChecking":false,"backupExistingFile":false,"useLoggedStorage":false,"enhanceManyToManyDetection":true,"logToConsole":false,"logFile":"","bundleNamespace":"","entityNamespace":"'.addslashes($this->config->getEntitiesNamespace()).'","repositoryNamespace":"","useAutomaticRepository":true,"extendTableNameWithSchemaName":false}}');
			// parse it with mwbConverter
			exec("echo 'n' | php ".dirname(__FILE__)."/../mwbConverter/cli/export.php --export=doctrine2-yaml --config='".$tmp."/config.json' '$mysqlFilename' 2>&1", $output, $result);
			$textOutput = "";
			foreach($output as $line)
				$textOutput .= $line."\n";
			
			//	throw new MysqlToDoctrineException($textOutput);
			
			// read all files into yamlObjects
			$this->yamlObjects = array();
			$dir = dir($tmp);
			while(false !== ($yamlFile = $dir->read()))
				if(!is_dir($tmp."/".$yamlFile))
					try
					{
						if(substr($yamlFile, strlen($yamlFile) - 4) == ".yml")
							$this->yamlObjects[$yamlFile] = $this->yamlParser->parse(file_get_contents($tmp."/".$yamlFile));
					}
					catch(ParseException $e)
					{
						if($this->dumpOutput)
							echo "$yamlFile : ".$e->getMessage();
					}
			
			// add some options to yaml
			foreach($this->yamlObjects as $yamlFile => $yamlObject)
			{
				foreach($yamlObject as $heading => $subobject)
					foreach($subobject as $tag => $value)
					{
						switch($tag)
						{
							case "manyToOne":
							case "manyToMany":
							case "oneToMany":
								foreach($value as $field => $fieldTags)
								{
									$yamlObject[$heading][$tag][$field]["cascade"] = array("persist", "merge", "detach");
								}
							break;
							
							default:
						}
					}
				$this->dumpOutput->setIndentation(4);
				file_put_contents($tmp."/".$yamlFile, $this->dumpOutput->dump($yamlObject, 10));
			}
			
			// Setup additionnal parameters to be usable by twig
			foreach($this->yamlObjects as $yamlFile => $yamlObject)
			{
				// - Get namespace and entity name
				foreach($yamlObject as $namespaceAndName => $subObject);
				$yamlObject = $subObject;
				$partNo = 1;
				$parts = array();
				$namespace = '';
				while(null !== ($partString = self::getPart($namespaceAndName, $partNo, '\\')))
				{
					if($partNo > 1)
						$namespace .= $savedPartString."\\";
					$savedPartString = $partString;
					$partNo++;
				}
				
				$entityName = $yamlObject["entityName"] = $savedPartString;
				$yamlObject["namespace"] = substr($namespace, 0, strlen($namespace) - 1);
				$yamlObject["businessNamespace"] = $this->config->getBusinessNamespace();
				$yamlObject["businessPath"] = $this->config->getBusinessPath();
				$yamlObject["headComment"] = $this->config->getHeadComment();
				$yamlObject["bundle"] = $this->config->getBundle();
				
				foreach($yamlObject as $subObjectName => $subObjectPart)
				{
					// - Get ids name and function name
					if($subObjectName == "id")
						foreach($subObjectPart as $idName => $idPart)
						{
							$yamlObject["id"][$idName]["name"] = $idName;
							$yamlObject["id"][$idName]["functionName"] = self::formatFieldNameForMethod($idName);
						}
					
					// - Get fields name and function name
					if($subObjectName == "fields")
						foreach($subObjectPart as $fieldName => $fieldPart)
						{
							$yamlObject["fields"][$fieldName]["name"] = $fieldName;
							$yamlObject["fields"][$fieldName]["functionName"] = self::formatFieldNameForMethod($fieldName);
						}
					
					// - Get manyToOne names and functions name
					if($subObjectName == "manyToOne")
						foreach($subObjectPart as $manyToOneName => $manyToOnePart)
						{
							$yamlObject["manyToOne"][$manyToOneName]["name"] = $manyToOneName;
							$yamlObject["manyToOne"][$manyToOneName]["functionName"] = ucfirst($manyToOneName);
						}
					
					// - Get oneToMany names and functions name
					if($subObjectName == "oneToMany")
						foreach($subObjectPart as $oneToManyName => $oneToManyPart)
						{
							$yamlObject["oneToMany"][$oneToManyName]["name"] = $oneToManyName;
							$yamlObject["oneToMany"][$oneToManyName]["functionName"] = ucfirst($oneToManyName);
						}
					
					// - Get manyToMany names and functions name
					if($subObjectName == "manyToMany")
						foreach($subObjectPart as $manyToManyName => $manyToManyPart)
						{
							$yamlObject["manyToMany"][$manyToManyName]["name"] = $manyToManyName;
							$yamlObject["manyToMany"][$manyToManyName]["functionName"] = ucfirst($manyToManyName);
						}
					
				}
				
				// and build code
				
				// yaml file
				$yamlBundlePath = __DIR__.'/../../../../'.$this->getConfig()->getBundlePath()."/Resources/config/doctrine";
				if(!file_exists($yamlBundlePath))
					mkdir($yamlBundlePath);
				rename($tmp."/".$yamlFile, $yamlBundlePath."/".$entityName.".orm.yml");
				
				// doctrine entity class and repository class
				$entitiesPath = __DIR__.'/../../../../'.$configObject["bundle"]["path"].'/'.$configObject["entities"]["path"].'/';
				$entityFileName = $entityName.'.php';
				
				$repositoryFileName = $yamlObject["repositoryClass"].'.php';
				if(!file_exists($entitiesPath.$entityFileName) || $this->getConfig()->getReplaceEntities())
					file_put_contents($entitiesPath.$entityFileName, 
										$this->templating->render('SebkMysqlToDoctrineBundle:Code:Entity.php.twig', $yamlObject));
				if(!file_exists($entitiesPath.$repositoryFileName) || $this->getConfig()->getReplaceRepositories())
					file_put_contents($entitiesPath.$repositoryFileName, 
										$this->templating->render('SebkMysqlToDoctrineBundle:Code:Repository.php.twig', $yamlObject));
				
				// business object and collection
				$businessPath = __DIR__.'/../../../../'.$configObject["bundle"]["path"].'/'.$configObject["business"]["path"].'/';
				if(!file_exists($businessPath))
					mkdir($businessPath);
				$fileContents = $this->templating->render('SebkMysqlToDoctrineBundle:Code:BusinessObject.php.twig', $yamlObject);
				
				// custom uses code
				$beginUses = "	/******Begin Custom Uses*/\n";
				$endUses = "	/******End Custom Uses*/\n";
				$customUses = $this->extractCustomCode($businessPath.$entityName.".php", $beginUses, $endUses);
				if($customUses)
					$fileContents = str_replace($beginUses.$endUses, $customUses, $fileContents);
				// custom exception code
				$beginException = "		/******Begin Custom Exception*/\n";
				$endException = "		/******End Custom Exception*/\n";
				$customException = $this->extractCustomCode($businessPath.$entityName.".php", $beginException, $endException);
				if($customException)
					$fileContents = str_replace($beginException.$endException, $customException, $fileContents);
				// custom extensions and implements code
				$beginExtensions = "	/******Begin Custom Extends And Implements*/\n";
				$endExtensions = "	/******End Custom Extends And Implements*/\n";
				$customExtensions = $this->extractCustomCode($businessPath.$entityName.".php", $beginExtensions, $endExtensions);
				if($customExtensions)
					$fileContents = str_replace($beginExtensions.$endExtensions, $customExtensions, $fileContents);
				// custom properties code
				$beginProperties = "		/******Begin Custom Properties*/\n";
				$endProperties = "		/******End Custom Properties*/\n";
				$customProperties = $this->extractCustomCode($businessPath.$entityName.".php", $beginProperties, $endProperties);
				if($customProperties)
					$fileContents = str_replace($beginProperties.$endProperties, $customProperties, $fileContents);
				// custom methods code
				$beginMethods = "		/******Begin Custom Methods*/\n";
				$endMethods = "		/******End Custom Methods*/\n";
				$customMethods = $this->extractCustomCode($businessPath.$entityName.".php", $beginMethods, $endMethods);
				if($customMethods)
					$fileContents = str_replace($beginMethods.$endMethods, $customMethods, $fileContents);
				
				file_put_contents($businessPath.$entityName.".php", $fileContents);
				
				$baseCode = $this->templating->render('SebkMysqlToDoctrineBundle:Code:BusinessObjectCollection.php.twig', $yamlObject);
				$customCode = $this->extractCustomCode($businessPath.$entityName."Collection.php", $beginMethods, $endMethods);
				$fileContents = str_replace($beginMethods.$endMethods, $customCode, $baseCode);
				file_put_contents($businessPath.$entityName."Collection.php", $fileContents);
			}
						
			// render common business objects
			$parms["businessNamespace"] = $this->config->getBusinessNamespace();
			$parms["businessPath"] = $this->config->getBusinessPath();
			$parms["headComment"] = $this->config->getHeadComment();
			$parms["bundle"] = $this->config->getBundle();
			file_put_contents($businessPath."BusinessCollection.php",
					$this->templating->render('SebkMysqlToDoctrineBundle:Code:BusinessCollection.php.twig', $parms));
			file_put_contents($businessPath."BusinessException.php",
					$this->templating->render('SebkMysqlToDoctrineBundle:Code:BusinessException.php.twig', $parms));
			file_put_contents($businessPath."BusinessObjectInterface.php",
					$this->templating->render('SebkMysqlToDoctrineBundle:Code:BusinessObjectInterface.php.twig', $parms));
		}
		
		/**
		 * Extract custom code in file
		 * @param string filename
		 */
		private function extractCustomCode($filename, $begin, $end)
		{
			@$f = fopen($filename, "r");
			if($f === false)
				return $begin.$end;
			
			$start = false;
			$result = "";
			while(false !== ($line = fgets($f)))
			{
				if(trim($line) == trim($begin))
					$start = true;
				if($start)
					$result .= $line;
				if(trim($line) == trim($end))
					return $result;
			}
			
			return $result;
		}
		
		/**
		 * Return the part (partNo) of the line separated by specific char
		 *
		 * @param string $line
		 * @param int $partNo
		 * @param string $sepChar
		 * @return string|NULL
		 */
		private static function getPart($line, $partNo, $sepChar = ":")
		{
			$parts = explode($sepChar, $line);
			if(isset($parts[$partNo - 1]))
				return trim($parts[$partNo - 1]);
			else
				return null;
		}
		
		/**
		 * Return the last part of the line separated by specific char
		 *
		 * @param string $line
		 * @param string $sepChar
		 * @return string
		 */
		private static function getLastPart($line, $sepChar = ":")
		{
			$parts = explode($sepChar, $line);
			return trim($parts[count($parts) - 1]);
		}
		
		/**
		 * Return the field separated by underscore concatened and each first words letter in uppercase
		 *
		 * @param unknown $field
		 * @return string
		 */
		private static function formatFieldNameForMethod($field)
		{
			$parts = explode("_", $field);
			$result = "";
			foreach($parts as $part)
				$result .= ucfirst($part);
				
			return $result;
		}
	}