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
	
	class ConfigType extends AbstractType
	{
		public function buildForm(FormBuilderInterface $builder, array $options)
		{
			$builder
				->add("bundlePath", "text", array("attr" => array("class" => "form-control")))
				->add("entitiesPath", "text", array("attr" => array("class" => "form-control")))
				->add("entitiesNamespace", "text", array("attr" => array("class" => "form-control")))
				->add("replaceEntities", "checkbox", array("required" => false, "attr" => array("class" => "form-control")))
				->add("replaceRepositories", "checkbox", array("required" => false, "attr" => array("class" => "form-control")))
				->add("save", "submit", array("label" => "Save configutation", "attr" => array("class" => "form-control btn btn-default")))
				->add("generate", "submit", array("label" => "Save and generate entities into bundle", "attr" => array("class" => "form-control btn btn-danger")));
		}
		
		public function setDefaultOptions(OptionsResolverInterface $resolver)
		{
			$resolver->setDefaults(array("data_class" => "Sebk\MysqlToDoctrineBundle\MysqlToDoctrine\Config"));
		}
		
		public function getName()
		{
			return "sebk_mysqltodoctrine_configtype";
		}
	}