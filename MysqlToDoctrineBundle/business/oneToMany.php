<?
	class oneToMany
	{
		public $entityName;
		public $relationName;
		public $entityProperty;
		public $targetEntity;
		public $mappedBy;
		
		public function reverseToYaml()
		{
			$result = "    oneToMany:\n";
			$result .= "        ".$this->entityProperty.":\n";
			$result .= "            field: ".$this->entityProperty."\n";
			$result .= "            targetEntity: ".$this->targetEntity."\n";
			$result .= "            mappedBy: ".$this->mappedBy."\n";
			
			return $result;
		}
		
		public function getObject()
		{
			$result = new stdClass;
			$result->field = $this->entityProperty;
			$result->targetEntity = $this->targetEntity;
			$result->mappedBy = $this->mappedBy;
			
			return $result;
		}
	}
?>
