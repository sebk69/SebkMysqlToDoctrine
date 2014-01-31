<?php
	namespace Dixheure\DixheureBundle\Parsing;
	
	class Parsing
	{
		public function linesToArray($str)
		{
			$result = array();
			$line = "";
			for($i = 0; $i < strlen($str); $i++)
			{
			if(substr($str, $i, 1) == "\n")
			{
			if(trim($line) != "");
			$result[] = $line;
			$line = "";
			}
			else
				$line .= substr($str, $i, 1);
			}
			$result[] = $line;
				
			return $result;
		}
		
		function getLineLevel($line, $char, $nbCharPerLevel)
		{
		$level = 0;
		$innerLevel = 0;
		for($i = 0; $i < strlen($line); $i++)
		{
		$innerLevel++;
			if($innerLevel == $nbCharPerLevel)
			{
				$innerLevel = 0;
				$level++;
			}
			if(substr($line, $i, 1) != $char)
					return $level;
			}
			}
		
			static function getEndingPonctuation($line)
			{
			return substr(trim($line), strlen(trim($line)) - 1, 1);
		}
		
		function getPart($line, $partNo, $sepChar = ":")
		{
		$parts = explode($sepChar, $line);
		if(isset($parts[$partNo - 1]))
			return trim($parts[$partNo - 1]);
			else
			return null;
		}
		
		function getLastPart($line, $sepChar = ":")
		{
		$parts = explode($sepChar, $line);
		return trim($parts[count($parts) - 1]);
		}
		
		function formatFieldNameForMethod($field)
		{
		$parts = explode("_", $field);
		$result = "";
		foreach($parts as $part)
			$result .= ucfirst($part);
				
			return $result;
		}
		
		function utf8_encode($str)
		{
		if (mb_detect_encoding($str, 'UTF-8', true) === FALSE)
			$str = utf8_encode($str);
		return $str;
		}
		
		public function stripToDraw($string, $maxLength)
			{
			if(strlen($string) > $maxLength)
			{
			return substr($string, 0, $maxLength)."[...]";
		}
			
		return $string;
		}
	}
?>