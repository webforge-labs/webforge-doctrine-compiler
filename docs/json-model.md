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
        "writtenPosts": { "type": "Collection<Post>"},
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
    