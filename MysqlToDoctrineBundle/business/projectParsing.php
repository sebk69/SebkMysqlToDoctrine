<?
	// parse a project
	class projectParsing
	{
		private $projectPath;
		private $fromFolder = "/tmp/parseYaml";
		private $projectNamespace;
		private $entitiesNamespace;
		private $toYaml;
		private $toEntities;
		private $toRepositoryClass;
		private $entityFileList;
		private $oneToMany = array();
		private $manyToOne = array();
		private $manyToMany = array();
		private $manyToManyParameters;
		
		public function __construct($ini, $fromFolder)
		{
			$this->projectPath = $ini->project->path;
			$this->projectNamespace = $ini->project->projectNamespace;
			$this->entitiesNamespace = $ini->project->entitiesNamespace;
			$this->fromFolder = $fromFolder;
			
			// check all necessary parameters are here
			if($this->projectPath === null)
				trigger_error("Missing ini parameter (projectPath)", E_USER_ERROR);
			if($this->fromFolder === null)
				trigger_error("Missing ini parameter (fromFolder)", E_USER_ERROR);
			if($this->entitiesNamespace === null)
				trigger_error("Missing ini parameter (entitiesNamespace)", E_USER_ERROR);
				
			// determine deductible parameters
			$this->toYaml = $this->projectPath."/config/yaml";
			$this->toEntities = $this->projectPath."/entities";
			$this->toRepositoryClass = $this->projectPath."/repositories";
			$this->entityFileList = $this->projectPath."/".$this->entityFileList;
			$this->manyToMany = $ini->manyToMany;
		}
		
		public function parseAll()
		{
			$fileList = scandir($this->fromFolder);
			
			// manyToOne and onToMany detection
			$manyToOne = array();
			$oneToMany = array();
			foreach($fileList as $fileToParse)
			{
				if($fileToParse != "." && $fileToParse != "..")
				{
					$parseYaml = new parseYaml($this->fromFolder."/".$fileToParse, $this->entitiesNamespace);
					if(isset($parseYaml->parsed->relations))
					{
						foreach($parseYaml->parsed->relations as $relationName => $relationNode)
						{
							$relationNode->class = $this->entitiesNamespace."\\dao".$relationNode->class;
							// create manyToOne parameters
							$i = @count($manyToOne[$parseYaml->path]);
							if($i == 0)
								$manyToOne[$parseYaml->path] = array();
							
							$manyToOne[$parseYaml->path][$i] = new manyToOne;
							$manyToOne[$parseYaml->path][$i]->entityName = $parseYaml->path;
							$manyToOne[$parseYaml->path][$i]->entityProperty = $relationName;
							$manyToOne[$parseYaml->path][$i]->targetEntity = $relationNode->class;
							foreach($parseYaml->parsed->indexes as $indexName => $index)
							{
								if(strtolower(substr($index->columns, 1, strlen($index->columns) - 2)) == strtolower($relationNode->local))
									$inversedByProperty = $indexName;
							}
							$manyToOne[$parseYaml->path][$i]->inversedByProperty = $inversedByProperty;
							$manyToOne[$parseYaml->path][$i]->joinColumn = $relationNode->local;
							$manyToOne[$parseYaml->path][$i]->referencedColumnName = $relationNode->foreign;
							// create oneToMany parameters
							$j = @count("dao".$oneToMany[$relationNode->class]);
							if($j == 0)
								$oneToMany[$relationNode->class] = array();
							
							$oneToMany[$relationNode->class][$j] = new oneToMany;
							$oneToMany[$relationNode->class][$j]->entityName = $parseYaml->path;
							$oneToMany[$relationNode->class][$j]->relationName = $manyToOne[$parseYaml->path][$i]->inversedByProperty;
							$oneToMany[$relationNode->class][$j]->entityProperty = $manyToOne[$parseYaml->path][$i]->inversedByProperty;
							$oneToMany[$relationNode->class][$j]->targetEntity = $manyToOne[$parseYaml->path][$i]->entityName;
							$oneToMany[$relationNode->class][$j]->mappedBy = $manyToOne[$parseYaml->path][$i]->entityProperty;
						}
					}
				}
			}
			
			// manyToMany detection
			foreach($this->manyToMany as $joinDefinition)
			{
				// join to entity 1
				$joinStructureTable1 = new stdClass;
				$entity = $joinDefinition->table1->entity;
				$field = $joinDefinition->table1->field;
				$joinStructureTable1->field = $field;
				$joinStructureTable1->object = new stdClass;
				$joinStructureTable1->object->targetEntity = $joinDefinition->table2->entity;
				$joinStructureTable1->object->inversedBy = $joinDefinition->table2->field;
				$joinStructureTable1->object->joinTable = new stdClass;
				$joinStructureTable1->object->joinTable->name = $joinDefinition->joinTable;
				$jointRelation = null;
				foreach($manyToOne[$joinDefinition->joinEntity] as $joinRelationNumber => $jointRelation)
					if($jointRelation->entityName == $joinDefinition->table1->entity)
						break;
				$joinStructureTable1->object->joinTable->joinColumns = new stdClass;
				$column = $jointRelation->joinColumn;
				$joinStructureTable1->object->joinTable->joinColumns->$column = new stdClass;
				$joinStructureTable1->object->joinTable->joinColumns->$column->referencedColumnName = $jointRelation->referencedColumnName;
				unset($oneToMany[$joinDefinition->joinEntity][$joinRelationNumber]);
				
				$jointRelation = null;
				foreach($manyToOne[$joinDefinition->joinEntity] as $joinRelationNumber => $jointRelation)
					if($jointRelation->entityName == $joinDefinition->table2->entity)
						break;
				$joinStructureTable1->object->joinTable->inverseJoinColumns = new stdClass;
				$column = $jointRelation->joinColumn;
				$joinStructureTable1->object->joinTable->inverseJoinColumns->$column = new stdClass;
				$joinStructureTable1->object->joinTable->inverseJoinColumns->$column->referencedColumnName = $jointRelation->referencedColumnName;
				// unregister previous links manyToOne and OneToMany
				unset($manyToOne[$joinDefinition->joinEntity][$joinRelationNumber]);
				foreach($oneToMany[$joinDefinition->table1->entity] as $joinRelationNumber => $jointRelation)
					if($jointRelation->targetEntity == $joinDefinition->joinEntity)
						unset($oneToMany[$joinDefinition->table1->entity][$joinRelationNumber]);
				
				// join to entity 2
				$joinStructureTable2 = new stdClass;
				$entity = $joinDefinition->table2->entity;
				$field = $joinDefinition->table2->field;
				$joinStructureTable2->field = $field;
				$joinStructureTable2->object = new stdClass;
				$joinStructureTable2->object->targetEntity = $joinDefinition->table1->entity;
				$joinStructureTable2->object->mappedBy = $joinDefinition->table1->field;
				// unregister previous links manyToOne and OneToMany
				unset($manyToOne[$joinDefinition->joinEntity][$joinRelationNumber]);
				foreach($oneToMany[$joinDefinition->table2->entity] as $joinRelationNumber => $jointRelation)
					if($jointRelation->targetEntity == $joinDefinition->joinEntity)
						unset($oneToMany[$joinDefinition->table2->entity][$joinRelationNumber]);
				
				// register links
				$manyToMany[$joinDefinition->table1->entity][] = $joinStructureTable1;
				$manyToMany[$joinDefinition->table2->entity][] = $joinStructureTable2;
			}
			
			
			// generate files
			foreach($fileList as $fileToParse)
			{
				foreach($this->manyToMany as $joinDefinition)
				{
					$i = 1;
					while(\helpers\parsing::getPart($joinDefinition->joinEntity, $i, "\\"))
					{
						$class = \helpers\parsing::getPart($joinDefinition->joinEntity, $i, "\\");
						$i++;
					}
					if(substr($class, 3) == \helpers\parsing::getPart($fileToParse, 1, "."))
						$fileToParse = ".";
				}
				if($fileToParse != "." && $fileToParse != "..")
				{
					if(end(explode('.', $fileToParse)) != "yml")
						trigger_error("File $fileToParse is not a yaml file", E_USER_WARNING);
					else
					{
						$parseYaml = new parseYaml($this->fromFolder."/".$fileToParse, $this->entitiesNamespace);
						$parseYaml->parsed->repositoryClass = $parseYaml->path."Repository";
						// write yaml file for the project
						$yamlFile = fopen($this->toYaml."/".str_replace("\\", ".", $parseYaml->path).".dcm.yml", "w");
						
						// integrate manyToOne relations
						if(isset($manyToOne[$parseYaml->path]))
						{
							$parseYaml->parsed->manyToOne = new stdClass;
							foreach($manyToOne[$parseYaml->path] as $relation)
							{
								$relationName = $relation->getObject()->field;
								$parseYaml->parsed->manyToOne->$relationName = $relation->getObject();
							}
						}
						
						if(isset($oneToMany[$parseYaml->path]))
						{
							$parseYaml->parsed->oneToMany = new stdClass;
							foreach($oneToMany[$parseYaml->path] as $relation)
							{
								$relationName = $relation->getObject()->field;
								$parseYaml->parsed->oneToMany->$relationName = $relation->getObject();
							}
						}
						if(isset($manyToMany[$parseYaml->path]))
						{
							$parseYaml->parsed->manyToMany = new stdClass;
							foreach($manyToMany[$parseYaml->path] as $relation)
							{
								$relationName = $relation->field;
								$parseYaml->parsed->manyToMany->$relationName = $relation->object;
							}
						}
						
						fwrite($yamlFile, $parseYaml->reverseToYaml());
						
						fclose($yamlFile);
						
						// write entity class for the project
						$entityFile = fopen($this->toEntities."/".$parseYaml->className.".php", "w");
						fwrite($entityFile, $parseYaml->generateEntityClass($this->projectNamespace, $this->entitiesNamespace));
						fclose($entityFile);
						
						// write repository class for the project if not exists
						$repositortyName = $this->toRepositoryClass."/".$parseYaml->className."Repository.php";
						if(!file_exists($repositortyName))
						{
							$repositoryFile = fopen($repositortyName, "w");
							fwrite($repositoryFile, $parseYaml->generateRepositoryClass($this->entitiesNamespace));
							fclose($repositoryFile);
						}
					}
				}
			}
		}
	}
?>
