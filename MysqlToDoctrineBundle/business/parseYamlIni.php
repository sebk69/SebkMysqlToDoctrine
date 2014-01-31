<?
	class parseYamlIni extends \helpers\ini
	{
		public function __construct($iniFileName)
		{
			// ini constructor : parse file
			parent :: __construct($iniFileName);
			
			// check necessary keys & sections
			if(!isset($this->project))
				trigger_error("Ini File - Missing required section (project)", E_USER_ERROR);
			if(!isset($this->project->path))
				trigger_error("Ini File - Section \"project\" : Missing required parameter (path)", E_USER_ERROR);
			
			// map many to many parameters to class
			$this->manyToManySection = $this->manyToMany;
			$this->manyToMany = array();
			foreach($this->manyToManySection as $key => $value)
			{
				$joins = new stdClass();
				$joins->joinEntity = $key;
				$joins->joinTable = \helpers\parsing::getPart($value, 1, ",");
				$joinDefinition = \helpers\parsing::getPart($value, 2, ",");
				$joins->table1 = new stdClass;
				$joins->table1->entity = \helpers\parsing::getPart($joinDefinition, 1, "->");
				$joins->table1->field = \helpers\parsing::getPart($joinDefinition, 2, "->");
				$joinDefinition = \helpers\parsing::getPart($value, 3, ",");
				$joins->table2 = new stdClass;
				$joins->table2->entity = \helpers\parsing::getPart($joinDefinition, 1, "->");
				$joins->table2->field = \helpers\parsing::getPart($joinDefinition, 2, "->");
				$this->manyToMany[] = $joins;
			}
		}
	}
?>
