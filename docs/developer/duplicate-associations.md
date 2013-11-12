# Associations

In our simple model we have associations like this:

```
Model has 10 associations:
  Post::author => Author::writtenPosts
  Post::author => Author::revisionedPosts
  Post::revisor => Author::writtenPosts
  Post::revisor => Author::revisionedPosts
  Post::categories => Category::posts
  Author::writtenPosts => Post::author
  Author::writtenPosts => Post::revisor
  Author::revisionedPosts => Post::author
  Author::revisionedPosts => Post::revisor
  Category::posts => Post::categories
```

We constructed them with quadrupels: entity, property, referencedEntity, referencedProperty
We looped through all properties that have references and searched for properties in the referencedEntity which reference entity. (They link back)

The problem for duplicates is the post and author. Posts can be revisioned and can be authored. On the Author side we have no clue which property needs to be referenced in Post. Thats why we write in the model:
```json
"revisionedPosts": { "type": "Collection<Post>", "relation": "revisor" },
"writtenPosts": { "type": "Collection<Post>" }
```
the rule is: the .relation property has to be the name of the property in the referencedEntity.

So what we do is that we group by `Post+Author` and see that Post::author has two referenced Properties: writtenPosts and revisionedPosts. We than loop through them and see if writtenPosts.relation or revisionedPosts.relation are equal to "author". Every entry which HAS a .relation property we treat this as an explicit "no match" if relation does not equal the property name it will be removed.
So that for writtenPosts (which has no .relation property) will be left in the list and is the only one which could match.
