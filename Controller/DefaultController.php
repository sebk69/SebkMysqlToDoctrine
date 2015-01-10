<?php
/**
 * This file is a part of SebkMysqlToDoctrineBundle
 * Copyright 2014 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\MysqlToDoctrineBundle\Controller;

use Sebk\MysqlToDoctrineBundle\Form\ConfigType;
use Sebk\MysqlToDoctrineBundle\MysqlToDoctrine\Config;
use Sebk\MysqlToDoctrineBundle\MysqlToDoctrine\ConfigException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sebk\MysqlToDoctrineBundle\MysqlToDoctrine\MysqlToDoctrineException;
use Symfony\Component\HttpFoundation\Session\Session;
use Sebk\MysqlToDoctrineBundle\MysqlToDoctrine\ChooseBundle;
use Sebk\MysqlToDoctrineBundle\Form\ChooseBundleType;
;

class DefaultController extends Controller
{
    public function indexAction()
    {
        ob_start();

        // get request
        $request = $this->get("request");

        // manage bundle choice
        $bundleChoice = new ChooseBundle();
        $bundleChoiceForm = $this->createForm(new ChooseBundleType, $bundleChoice);

        if ($request->getMethod() == "POST") {
            $bundleChoiceForm->bind($request);

            if ($bundleChoiceForm->isValid() && $bundleChoice->getBundle() != "") {
                // bundle ok, pass to config bundle action
                return $this->redirect($this->generateUrl('sebk_mysql_to_doctrine_bundle_config', array('bundle' => $bundleChoice->getBundle())));
            }
        }

        return $this->render("SebkMysqlToDoctrineBundle:Default:index.html.twig", array("form" => $bundleChoiceForm->createView()));
    }

    public function configBundleAction($bundle, $message)
    {
        // get request
        $request = $this->get("request");

        // manage bundle choice
        $config = new Config($bundle);
        $configForm = $this->createForm(new ConfigType(), $config);

        $viewParms = array("bundle" => $bundle);

        if ($request->getMethod() == "POST") {
            $configForm->bind($request);

            if ($configForm->isValid()) {
                try {
                    $config->validate();
                    $config->write();
                    $message = "saved";
                } catch (ConfigException $e) {
                    $viewParms["error"] = $e->getMessage();
                }

                // config ok, pass to generate action
                if ($configForm->get("generate")->isClicked())
                    return $this->redirect($this->generateUrl('sebk_mysql_to_doctrine_bundle_generate', array('bundle' => $bundle)));
            }
        }

        $viewParms["form"] = $configForm->createView();
        if ($message)
            $viewParms["message"] = $message;
        if ($message == "error") {
            $session = new Session();
            $viewParms["error"] = $session->get("message");
        }
        return $this->render("SebkMysqlToDoctrineBundle:Default:config_bundle.html.twig", $viewParms);
    }

    public function generateBundleAction($bundle)
    {
        //ob_start();
        try {
            $generator = $this->get("sebk_mysql_to_doctrine.main");
            $generator->setBundle($bundle);
            $generator->createEntities();
        } catch (MysqlToDoctrineException $e) {
            $session = new Session();
            $session->start();
            $session->set("message", $e->getMessage());
            return $this->redirect($this->generateUrl('sebk_mysql_to_doctrine_bundle_config', array('bundle' => $bundle, 'message' => 'error')));
        }
        //ob_end_clean();
        return $this->redirect($this->generateUrl('sebk_mysql_to_doctrine_bundle_config', array('bundle' => $bundle, 'message' => 'generated')));
    }
}
