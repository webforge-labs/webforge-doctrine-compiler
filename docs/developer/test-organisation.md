# Tests organization

this repository as a little bit different layout. There is one big fixture in files/acme-blog which contains a model.json in etc. This model is compiled in the CreateModelTest.php for the second testsuite (see phpunit.xml.dist). The package with compiled entities is stored in Base::$package (which is a static property). The testsuite for model is ordered and new tests have to be added manually(!)
The EntitiesTest compiles this model ONCE and requires the TestReflection.php which reflects the entities in the model. You can use the static methods as dataproviders for your tests. All previous tests do not have to compile the model. They CANT because the classes would be defined twice.
The classes get written to a VFS directory on the entities test and might then disappear.

## how to run

you can run the unit testsuite with:
```
phpunit --testsuite=unit
```
or the model tests

```
phpunit --testsuite=model
```

**NOTICE**: you cannot use --filter on the model tests because the test have to be in the right order (e.g. CreateModelTest has to setup the acceptance package and compile the entities!)