# the JSON Model for the compiler

Entities in Doctrine are cumbersome to deal with. The annotations are little bit tricky to learn. Webforge tries to help you build the commonly needed Entities automatically.  
Therefore a JSON format for creating entities should be used. This is a rough draft and needs discussion and alternation

```json
{
  "namespace": "ACME\\Entities",

  "entities": [

    {
      "name": "User",
  
      "properties": {
        "id": { "type": "DefaultId" },
        "email": { "type": "String" }
      }
    },

    {
      "name": "Post",
  
      "properties": {
        "id": { "type": "DefaultId" },
        "author": { "type": "Author" },
        "revisor": { "type": "Author", "nullable": true },
        "categories": { "type": "Collection<Category>", "isOwning": true },
        "created": { "type": "DateTime" },
        "modified": { "type": "DateTime", "nullable": true }
      }
    },

    {
      "name": "Author",
      "extends": "User",
  
      "properties": {    
        "writtenPosts": { "type": "Collection<Post>", "relation": "author" },
        "revisionedPosts": { "type": "Collection<Post>", "relation": "revisor" }
      }
    },

    {
      "name": "Category",
      "plural": "categories",

      "properties": {
        "id": "DefaultId",
        "posts": { "type": "Collection<Post>" }
      }
    }

  ]
}
```
  - The Model has the namespace ACME\Entities
  - `User`, `Author`, `Post`, `Category` are entities of the model
  - Entities are expanded to `ACME\Entities\User`, etc
  - all entities in this example have an auto generated numeric primary key named `id`
  - an `Author` is a `User`
  - `Post:Category` is a ManyToMany relationship. (One post has several categories, one category has several posts)
    - `Post.categories` is the owning side of the relationship. That means: When the collection in `Post` named categories is changed and the post is saved, the relationship is changed; When `Category.posts` is changed and the category saved, nothing happens
  - `Post:Author` is a ManyToOne relationship. (One post has one author, one author has several posts)
  - `Post:Author` as revisor is a ManyToOne relationship. (One post has one (or none) revisor, one author revises several posts)
    - the post might not have a revisor and `modified` time can be a NULL value (meaning: it was not modified, yet)
    - the posts from this relationship are stored in the collection `revisionedPosts`.
    - the relationside from `Post` on the `Author`-side is ambigous, thats why its referenced with: `relation: "revisor"`
    -  `"author": { "type": "Author" },` is expanded to `"author": { "type": "Author", "relation": "author" }`
       `"revisor": { "type": "Author" },` is expanded to `"revisor": { "type": "Author", "relation": "revisor" }`
    
## Ambigous Relation Sides

There is one case where the doctrine-compiler cannot guess what you really want to do. In case you have a unidirectional ManyToOne relationship (that is specified on the Many-side). It cannot determine if this should be a OneToOne or an ManyToOne relationships, so you have to give a little advice:

```json
    {
      "name": "ContentStream\\Paragraph",
      "extends": "ContentStream\\Entry",
    
      "properties": {
        "content": { "type": "String" }
      },

      "constructor": ["content"]
    },

    {
      "name": "ContentStream\\TextBlock",
      "extends": "ContentStream\\Entry",

      "properties": {
        "paragraph1": { "type": "ContentStream\\Paragraph" },
        "paragraph2": { "type": "ContentStream\\UniqueParagraph", "relation": "OneToOne" }
      }

    }
```

*Notice*: Paragraph has not relationship defined to TextBlock, otherwise the compiler could have guessed the relationship (OneToOne if it is a property, ManyToOne if it is a collection).
Because OneToOne is less common doctrine guesses here ManyToOne per default. You **HAVE** to use `"relation": "OneToOne"` for unidirectional OneToOne relationships.

## cascade and other properties for associations

If you want to use the `cascade` settings for a side of an relation ship add the options to the corrosponding property:

```json
    {
      "name": "Post",
  
      "properties": {
        "id": { "type": "DefaultId" },
        "categories": { "type": "Collection<Category>", "isOwning": true, "cascade": ["persist", "remove"] },
      }
    }
```
The parameters will be passed directly to the written doctrine annotation

## Extensions

The Serializer extension can be configured in its own subsection:

```json
    {
      "name": "Post",
      "serializer": { "defaultGroups": ["api"] }
  
      "properties": {
        "id": { "type": "DefaultId" },
        "content": { "type": "MarkupText" },
        "active": { "type": "Boolean", "serializer": { "groups": ["cms"] }},

      }
    }
```
`groups` sets the annotation `@Groups` for the property. Every property inherits the defaultGroups from the entity, if it has no own subsection. So the model above is aequvalent to:

```json
    {
      "name": "Post",
  
      "properties": {
        "id": { "type": "DefaultId", "serializer": { "groups": ["api"] } },
        "content": { "type": "MarkupText", "serializer": { "groups": ["api"] } },
        "active": { "type": "Boolean", "serializer": { "groups": ["cms"] }},

      }
    }
```

## defaultValue for Properties

```json
    "properties": {
      "somePrecisionValue": { "type": "Float", "defaultValue": "0.5" }
      "someEnum": { "type": "Enum<tiptoi\\SoundType>", "defaultValue": "\\tiptoi\\SoundType::FX" }
      "someString": { "type": "String", "defaultValue": "'myString'" }
    }
```

will be compiled to:

```php
  protected $somePrecisionValue = 0.5;
  protected $someEnum = \tiptoi\SoundType::FX;
  protected $someString = 'myString';
```

so the default Behavior is, that the value from "defaultValue" will be interpretet as literal PHP Code. This allows more flexibility (but less portability, which is not a huge factor yet)

