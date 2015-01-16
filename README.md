SebkMysqlToDoctrine
===================

Bundle to automatic entities generation and create a business template that can be modified in order to manage inheritance and other business methods

Features :
- Can rebuild business methods (impacting database changes for example) automatically without discare modifications
- Provide collection of business objects that can be easyly load from entities or doctrine QueryBuilder queries
- Provide factory as a symfony service to instanciate business model

Installation :
- Specify the folowing lines in composer.json of your project (after autoload line) :
-    "repositories": [
-        {
-            "type": "git",
-            "url": "https://github.com/sebk69/SebkMysqlToDoctrine.git"
-        }
-    ],
- and these in the "require" section :
-    "sebk69/SebkMysqlToDoctrine": "dev-master"
- add in your app/AppKernel.php in the dev section :
-    $bundles[] = new Sebk\MysqlToDoctrineBundle\SebkMysqlToDoctrineBundle();
- and add route in app/config/routing_dev.yml :
-    sebk_mysql_to_doctrine:
-        resource: "@SebkMysqlToDoctrineBundle/Resources/config/routing.yml"
-        prefix:   /
