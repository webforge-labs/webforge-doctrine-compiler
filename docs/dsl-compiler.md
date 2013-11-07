# Domain Specific Language Compiler

Example for an image Entitiy written in the DSL:

```
compile(
  $entity('WebImage', $extends('ACME\Library\PhyicalImage')),
  
  $defaultId(),
  
  $property('sourcePath', $type('String')),
  $property('hash', $type('String'), $unique()),
  $property('label', $type('String'), $nullable()),
  
  $constructor(
    $argument('sourcePath', NULL),
    $argument('label', NULL),
    $argument('hash', NULL)
  ),
);
```

This creates an Entity-Object which has the following attributes:

  - the class Name is `WebImage`
  - its parent is `ACME\Library\PhyiscalImage`
  - it has a field id which is an integer. This integer is an auto generated primary key for the entity. It is created with extra=AUTO_INCREMENT on mysql platforms.
  - it has the property sourcepath which is a string
  - it has the property hash which is a string and is unique for the table `webimages`
  - it has the property label which is a string but cann be set to NULL
  - the constructor accepts the arguments (sourcePath, label, hash) but it can be constructed empty
  - it has the following methods auto generated: 
    - getSourcePath
    - setSourcePath
    - getLabel
    - setLabel
    - getHash
    - setHash
