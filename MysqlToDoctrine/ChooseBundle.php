<?php
/**
 * This file is a part of SebkMysqlToDoctrineBundle
 * Copyright 2014 - Sébastien Kus
 * Under GNU GPL V3 licence
 */
namespace Sebk\MysqlToDoctrineBundle\MysqlToDoctrine;

use Doctrine\ORM\Query\AST\Functions\UpperFunction;


class ChooseBundle
{
    private $bundle = "";
    private static $srcDir;
    
    public static function setRootDir($rootDir)
    {
        self::$srcDir = $rootDir."/../src/";
    }

    public function getBundle()
    {
        return $this->bundle;
    }

    public function setBundle($bundleName)
    {
        $this->bundle = $bundleName;
    }

    public static function listBundles()
    {
        $bundles = array();

        // get src directory
        $srcDir = \dir(self::$srcDir);

        // scan it
        while (false !== ($devDirName = $srcDir->read())) {
            if ($devDirName != "." && $devDirName != ".." && is_dir(self::$srcDir . $devDirName)) {
                // get dev directory name
                $devDir = \dir(self::$srcDir . $devDirName);
                // scan it for bundles
                while (false !== ($bundle = $devDir->read())) {
                    if ($bundle != "." && $bundle != ".." && is_dir(self::$srcDir . $devDirName . "/" . $bundle))
                        if (substr($bundle, strlen($bundle) - 6) == "Bundle")
                            // it is a bundle, store it
                            $bundles[$devDirName . ucfirst($bundle)] = $devDirName . ucfirst($bundle);
                }
            }
        }

        $bundles[] = "";
        asort($bundles);
        return $bundles;
    }
}