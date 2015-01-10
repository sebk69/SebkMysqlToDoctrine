<?php
/**
 * This file is a part of SebkMysqlToDoctrineBundle
 * Copyright 2014 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\MysqlToDoctrineBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Sebk\MysqlToDoctrineBundle\MysqlToDoctrine\ChooseBundle;

class ChooseBundleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("bundle", "choice", array(
                "choices" => ChooseBundle:: listBundles(),
                "label" => "Choose bundle ",
                "attr" => array("class" => "form-control")
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array("data_class" => "Sebk\MysqlToDoctrineBundle\MysqlToDoctrine\ChooseBundle"));
    }

    public function getName()
    {
        return "sebk_mysqltodoctrine_choosebundletype";
    }
}