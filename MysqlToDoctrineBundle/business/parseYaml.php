<?
	// parsing a single YAML file
	class parseYaml
	{
		public $parsed;
		public $path;
		public $namespace;
		public $className;
		
		public function __construct($fileName, $namespace)
		{
			$this->namespace = $namespace;
			if($fileName && file_exists($fileName))
			{
				$this->parse(file_get_contents($fileName));
			}
		}
		
		public function parse($str)
		{
			foreach(\helpers\parsing :: linesToArray($str) as $line)
			{
				$level = \helpers\parsing :: getLineLevel($line, " ", 4);
				if($level == 0)
				{
					$node[$level] = new stdClass;
					$this->path = $this->namespace."\\"."dao".substr(\helpers\parsing :: getPart($line, 1), 8);
					$this->className ="dao".substr(\helpers\parsing :: getPart($line, 1), 8);
					$node[$level] = $this->parsed;
				}
				elseif(\helpers\parsing :: getEndingPonctuation($line) == ":")
				{
					$sectionName = \helpers\parsing :: getPart($line, 1);
					$node[$level - 1]->$sectionName = new stdClass;
					$node[$level] = $node[$level - 1]->$sectionName;
				}
				else
				{
					$tagName = \helpers\parsing :: getPart($line, 1);
					$tagValue = \helpers\parsing :: getPart($line, 2);
					
					// generator bug : type: entity in lower case
					if($tagName == "type" && $level == 1)
						$tagValue = strtolower($tagValue);
					
					$node[$level - 1]->$tagName = $tagValue;
				}
			}
			
			$this->parsed = $node[0];
		}
		
		public function reverseToYaml($node = null, $level = 1)
		{
			if($node == null)
			{
				// correction bug car outils de génération basé sur symphony
				// echo $this->path.":\n";
				$result = $this->path.":\n";
				$node = $this->parsed;
			}
			else
				$result = "";
			
			foreach($node as $name => $element)
			{
				for($i = 0; $i < $level; $i++)
					$result .= "    ";
				if(gettype($element) == "object")
				{
					$result .= $name.":\n";
					$result .= $this->reverseToYaml($element, $level + 1);
				}
				else
					$result .= str_replace("_", "-", $name).": ".$element."\n";
			}
			
			return $result;
		}
		
		public function generateEntityClass($projectNamespace, $entitiesNamespace)
		{
			// en-têtes
			$result = "<?\n";
			$result .= "\tnamespace $entitiesNamespace;\n";
			$result .= "\t\n";
			$result .= "\tclass ".$this->className." extends \\$projectNamespace\\persistDb\n";
			$result .= "\t{\n";
			
			// Indexs primaires
			$result .= "\t\t// propriétés des indexes primaires\n";
			foreach($this->parsed->id as $id => $node)
			{
				$result .= "\t\tprivate \$$id;\n";
			}
			// Champs
			$result .= "\t\t// propriétés des champs\n";
			foreach($this->parsed->fields as $field => $node)
			{
				$result .= "\t\tprivate \$$field;\n";
			}
			// jointures
					if(isset($this->parsed->oneToMany))
			{
				$result .= "\t\t// propriétés des relations oneToMany\n";
				foreach($this->parsed->oneToMany as $relation)
				{
					$result .= "\t\tprivate \$".$relation->field.";\n";
				}
			}
			if(isset($this->parsed->manyToMany))
			{
				$result .= "\t\t// propriétés des relations manyToMany\n";
				foreach($this->parsed->manyToMany as $id => $relation)
				{
					$result .= "\t\tprivate \$".$id.";\n";
				}
			}
			if(isset($this->parsed->manyToOne))
			{
				$result .= "\t\t// propriétés des relations manyToOne\n";
				foreach($this->parsed->manyToOne as $relation)
				{
					$result .= "\t\tprivate \$".$relation->field.";\n";
				}
			}
			
			$result .= "\t\t\n";
			
			// constructeur
			$result .= "\t\tpublic function __construct()\n";
			$result .= "\t\t{\n";
			$result .= "\t\t\tparent::__construct();\n";
				
			if(isset($this->parsed->oneToMany))
			{
				$result .= "\t\t\t// define oneToMany properties\n";
				foreach($this->parsed->oneToMany as $id => $node)
				{
					$field = $id;
					$result .= "\t\t\t\$this->$field = new \Doctrine\Common\Collections\ArrayCollection();\n";
				}
			}
			
			if(isset($this->parsed->manyToMany))
			{
				$result .= "\t\t\t// define manyToMany properties\n";
				foreach($this->parsed->manyToMany as $id => $node)
				{
					$field = $id;
					$result .= "\t\t\t\$this->$field = new \Doctrine\Common\Collections\ArrayCollection();\n";
				}
			}
			
			$result .= "\t\t}\n\n";
			
			// getters
			$result .= "\t\t// getters\n";
			foreach($this->parsed->id as $column => $node)
			{
				$result .= "\t\tpublic function get".ucfirst($column)."()\n";
				$result .= "\t\t{\n";
				$result .= "\t\t\treturn \$this->$column;\n";
				$result .= "\t\t}\n\n";
			}
			foreach($this->parsed->fields as $column => $node)
			{
				$result .= "\t\tpublic function get".\helpers\parsing :: formatFieldNameForMethod($column)."()\n";
				$result .= "\t\t{\n";
				$result .= "\t\t\treturn \$this->$column;\n";
				$result .= "\t\t}\n\n";
			}
			if(isset($this->parsed->oneToMany))
				foreach($this->parsed->oneToMany as $relation)
				{
					$result .= "\t\tpublic function get".\helpers\parsing :: formatFieldNameForMethod($relation->field)."()\n";
					$result .= "\t\t{\n";
					$result .= "\t\t\treturn \$this->".$relation->field.";\n";
					$result .= "\t\t}\n\n";
				}
			if(isset($this->parsed->manyToMany))
				foreach($this->parsed->manyToMany as $field => $relation)
				{
					$result .= "\t\tpublic function get".\helpers\parsing :: formatFieldNameForMethod($field)."()\n";
					$result .= "\t\t{\n";
					$result .= "\t\t\treturn \$this->".$field.";\n";
					$result .= "\t\t}\n\n";
				}
			if(isset($this->parsed->manyToOne))
				foreach($this->parsed->manyToOne as $relation)
				{
					$result .= "\t\tpublic function get".\helpers\parsing :: formatFieldNameForMethod($relation->field)."()\n";
					$result .= "\t\t{\n";
					$result .= "\t\t\treturn \$this->".$relation->field.";\n";
					$result .= "\t\t}\n\n";
				}
			
			// setters
			$result .= "\t\t// setters\n";
			foreach($this->parsed->fields as $column => $node)
			{
				$result .= "\t\tpublic function set".\helpers\parsing :: formatFieldNameForMethod($column)."($".\helpers\parsing :: formatFieldNameForMethod($column).")\n";
				$result .= "\t\t{\n";
				$result .= "\t\t\t\$this->$column = $".\helpers\parsing :: formatFieldNameForMethod($column).";\n";
				$result .= "\t\t}\n\n";
			}
			
			if(isset($this->parsed->manyToOne))
				foreach($this->parsed->manyToOne as $field => $relation)
				{
					$class = $relation->targetEntity;
					$result .= "\t\tpublic function set".\helpers\parsing :: formatFieldNameForMethod($field)."(\\$class \$$field)\n";
					$result .= "\t\t{\n";
					$result .= "\t\t\t\$this->$field = \$$field;\n";
					$result .= "\t\t}\n\n";
				}
						
			// fin de la classe
			$result .= "\t}\n";
			$result .= "?>";
			
			return $result;
		}
		
		public function generateRepositoryClass($namespace)
		{
			$result = "<?\n";
			$result .= "\tnamespace $namespace;\n\n";
			$result .= "\tuse Doctrine\ORM\EntityRepository;\n\n";
			$result .= "\tclass ".$this->className."Repository extends EntityRepository\n";
			$result .= "\t{\n";
			$result .= "\t}\n";
			$result .= "?>";
			
			return $result;
		}
	}
?>
