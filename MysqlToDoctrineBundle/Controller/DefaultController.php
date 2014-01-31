<?php

namespace Sebk\MysqlToDoctrineBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('SebkMysqlToDoctrineBundle:Default:index.html.twig', array('name' => $name));
    }
}
