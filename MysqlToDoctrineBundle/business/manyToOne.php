<?
	// structures de définition des jointures
	class manyToOne
	{
		public $entityName;
		public $entityProperty;
		public $targetEntity;
		public $inversedByProperty; // propriété de l'objet implémentant le oneToMany correspondant
		public $joinColumn;
		public $referencedColumnName;
		
		public function reverseToYaml()
		{
			$result = "    manyToOne:\n";
			$result .= "        ".$this->entityProperty.":\n";
			$result .= "            field: ".$this->entityProperty."\n";
			$result .= "            targetEntity: ".$this->targetEntity."\n";
			$result .= "            inversedBy: ".$this->inversedByProperty."\n";
			$result .= "            joinColumn:\n";
			$result .= "                name: ".$this->joinColumn."\n";
			$result .= "                referencedColumnName: ".$this->$referencedColumnName."\n";
			
			return $result;
		}
		
		public function getObject()
		{
			$obj = new stdClass;
			$obj->field = $this->entityProperty;
			$obj->targetEntity = $this->targetEntity;
			$obj->inversedBy = $this->inversedByProperty;
			$obj->joinColumn = new stdClass;
			$obj->joinColumn->name = $this->joinColumn;
			$obj->joinColumn->referencedColumnName = $this->referencedColumnName;
			
			return $obj;
		}
	}
?>
