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

class ConfigException extends \Exception
{
}

class Config
{
    private $bundle;
    private $configObject;
    private $saveAction;
    private $generateAction;

    public function __construct($bundle)
    {
        $this->bundle = $bundle;
        $this->yamlParser = new Parser();
        $this->yamlDumper = new Dumper();
        if (file_exists($this->getFileName())) {
            $yaml = file_get_contents($this->getFileName());
            $this->configObject = $this->yamlParser->parse($yaml);
        }
    }

    public function getBundle()
    {
        return $this->bundle;
    }


    public function getSave()
    {
        return $this->saveAction;
    }

    public function setSave($value)
    {
        $this->saveAction = $value;

        return $this;
    }

    public function getGenerate()
    {
        return $this->getGenerate();
    }

    public function setGenerate($value)
    {
        $this->generateAction = $value;

        return $this;
    }

    public function getConfigAsArray()
    {
        return $this->configObject;
    }

    public function getBundlePath()
    {
        return $this->configObject["bundle"]["path"];
    }

    public function setBundlePath($path)
    {
        $this->configObject["bundle"]["path"] = $path;

        return $this;
    }

    public function getReplaceEntities()
    {
        if (!isset($this->configObject["bundle"]["replaceEntities"]))
            return true;

        return $this->configObject["bundle"]["replaceEntities"] == "true";
    }

    public function setReplaceEntities($value)
    {
        if ($value)
            $this->configObject["bundle"]["replaceEntities"] = "true";
        else
            $this->configObject["bundle"]["replaceEntities"] = "false";

        return $this;
    }

    public function getReplaceRepositories()
    {
        if (!isset($this->configObject["bundle"]["replaceRepositories"]))
            return false;

        return $this->configObject["bundle"]["replaceRepositories"] == "true";
    }

    public function setReplaceRepositories($value)
    {
        if ($value)
            $this->configObject["bundle"]["replaceRepositories"] = "true";
        else
            $this->configObject["bundle"]["replaceRepositories"] = "false";

        return $this;
    }

    public function getEntitiesPath()
    {
        if (isset($this->configObject["entities"]["path"]))
            return $this->configObject["entities"]["path"];
        return null;
    }

    public function setEntitiesPath($path)
    {
        $this->configObject["entities"]["path"] = $path;

        return $this;
    }

    public function getEntitiesNamespace()
    {
        if (isset($this->configObject["entities"]["namespace"]))
            return $this->configObject["entities"]["namespace"];
        return null;
    }

    public function setEntitiesNamespace($namespace)
    {
        $this->configObject["entities"]["namespace"] = $namespace;

        return $this;
    }

    public function getBusinessPath()
    {
        if (isset($this->configObject["business"]["path"]))
            return $this->configObject["business"]["path"];
        return null;
    }

    public function setBusinessPath($value)
    {
        $this->configObject["business"]["path"] = $value;

        return $this;
    }

    public function getBusinessNamespace()
    {
        if (isset($this->configObject["business"]["namespace"]))
            return $this->configObject["business"]["namespace"];
        return null;
    }

    public function setBusinessNamespace($value)
    {
        $this->configObject["business"]["namespace"] = $value;

        return $this;
    }

    public function getHeadComment()
    {
        if (isset($this->configObject["business"]["headComment"]))
            return $this->configObject["business"]["headComment"];
        return null;
    }

    public function setHeadComment($value)
    {
        $this->configObject["business"]["headComment"] = $value;

        return $this;
    }

    public function getBusinessFactoryServiceName()
    {
        if (isset($this->configObject["business"]["factoryServiceName"]))
            return $this->configObject["business"]["factoryServiceName"];

        return null;
    }

    public function setBusinessFactoryServiceName($value)
    {
        $this->configObject["business"]["factoryServiceName"] = $value;

        return $this;
    }

    public function getBusinessGeneration()
    {
        if (isset($this->configObject["business"]["generation"])) {
            return $this->configObject["business"]["generation"] == "true";
        }

        return true;
    }

    public function setBusinessGeneration($value)
    {
        if ($value) {
            $this->configObject["business"]["generation"] = "true";
        } else {
            $this->configObject["business"]["generation"] = "false";
        }
    }

    public function setFromArray($configObject)
    {
        $savedConfig = $this->configObject;
        $this->configObject = $configObject;
        try {
            $this->validateConfig();
        } catch (Exception $e) {
            $this->configObject = $savedConfig;
            throw $e;
        }

        return $this;
    }

    public function validate()
    {
        // check bundle section
        if (!isset($this->configObject["bundle"]))
            throw new ConfigException("MysqlToDoctrine - Missing bundle section");
        // - doctrine folder
        if (!isset($this->configObject["bundle"]["path"]))
            throw new ConfigException("MysqlToDoctrine - Missing bundle path section");
        if (!is_dir(__DIR__ . '/../../../../' . $this->configObject["bundle"]["path"]))
            throw new ConfigException("MysqlToDoctrine - Bundle path is not corresponding to a directory");

        // check entity section
        if (!isset($this->configObject["entities"]))
            throw new ConfigException("MysqlToDoctrine - Missing entities section");
        if (!isset($this->configObject["entities"]["path"]))
            throw new ConfigException("MysqlToDoctrine - Missing entities path section");
        if (!is_dir(__DIR__ . '/../../../../' . $this->configObject["bundle"]["path"] . '/' . $this->configObject["entities"]["path"]))
            throw new ConfigException("MysqlToDoctrine - Entities path is not corresponding to a directory");
        if (!isset($this->configObject["entities"]["namespace"]))
            throw new ConfigException("MysqlToDoctrine - Missing entities namespace section");
    }

    public function write()
    {
        // validate config
        $this->validate();

        // dump config object to yaml
        $yaml = $this->yamlDumper->dump($this->configObject, 10);

        // write it on disk
        file_put_contents($this->getFileName(), $yaml);
    }

    public function getFileName()
    {
        $bundles = new ChooseBundle();
        return ChooseBundle::getRootDir()."/".$bundles->getBundlePath($this->getBundle()).'/Resources/config/mysqlToDoctrine.yaml';
    }
}

?>