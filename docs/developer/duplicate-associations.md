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


## Detecting Associations with marriage algorithms

We can model our problem of finding the associations between entity-properties with a stable marriage problem.
We have a group of properties scattered over several entities which are entity-collections and entity-references to other properties.
So every property is a vertice in a directed graph that has edges to properties in other entities.

We have two participation groups: 
  women: all properties in entities that are on the owning side
  men: all properties in entities that are on the inverse side

if we loop through all entities and find the properties with a reference to another entity, we have all properties that should be marriaged somehow.

But we don't know if one is a women or a man.

- EntityCollections with isOwning: true will be womans
- EntityReferences with isOwning: true (for OneToOne) will be womans

others are undecided

We can construct the lists of candidates with looping through the properties of the referenced entity
  an edge is to be created, if:
   - the property has a reference
   - the reference has the same entity as our property
   - the referencedProperty has not a tag "relation" that does not match our property name
   - our property has not a tag "relation" that does not match the referencedProperty name

   IF we find a property which has a relation-tag that matches, we can remove the property from the pool of possible candidates for edges

loop through all entites
  find all properties that have a reference

=> all properties that have to be married

find the properties that are self-referencing, create associations and remove them from the pool

we take one property out of the pool and loop through all others
  if a property references the entity of the taken property
    if it has a relation-tag wich matches the name of the property
      remove the matched property from the pool create an association between them
    else
      create a possible association between them, but leave the property in the pool

if no property matches create an unidirectional association
  if the property is an owning singleReference its OneToOne
  if the property is an owning collectionReference its ManyToMany (raise a warning and make it owning if its not owning?)
  if the property is an not owning singleReference raise an error, because its not decideable if its a ManyToOne unidirectionl or OneToOne unidirectional

  remove the property from the pool

After we have investigated every property

  find all duplicates of possible assocations

## example

Entity: Post
  - categories
  - specialCategory
  - author
  - revisor

Entity: Author
  - writtenPosts
  - revisionedPosts tag:revisor

Entity: Category
  - posts
  - relatedCategories


post.categories
post.specialCategory
post.author
post.revisor
author.writtenPosts
author.revisionedPosts
category.posts
category.relatedCategories

find self-referencing properties:

 => category.relatedCategories will be removed

post.categories
post.specialCategory
post.author
post.revisor
author.writtenPosts
author.revisionedPosts
category.posts

post.categories matches category.posts, has no relation tag

possible Relations: [
  post.categories -> category.posts
]

post.specialCategory matches category.posts, 

possible Relations: [
  post.categories -> category.posts
  post.specialCategory -> category.posts
]

post.author matches author.writtenPosts
post.author matches author.revisionedPosts but the tag:revisor does not match

possible Relations: [
  post.categories -> category.posts
  post.specialCategory -> category.posts
  post.author -> author.writtenPosts
]

author.writtenPosts matches post.author
author.writtenPosts matches post.revisor (because tag:revisor is only in revisionedPosts)

possible Relations: [
  post.categories -> category.posts
  post.specialCategory -> category.posts
  post.author -> author.writtenPosts
  author.writtenPosts -> post.author
  author.writtenPosts -> post.revisor
]

post.revisor matches author.revisionedPosts by tag so that post.revisionedPosts is removed
post.revisor is matched without a doubt so all its associations are removed from possible associations
author.revisionedPosts is matched without a doubt so all its associations are removed from possible associations

possible Relations: [
  post.categories -> category.posts
  post.specialCategory -> category.posts
  post.author -> author.writtenPosts
  author.writtenPosts -> post.author
]

author.revisionedPosts was already removed

category.posts matches post.categories
category.posts matches post.specialCategory

possible Relations: [
  post.categories -> category.posts
  post.specialCategory -> category.posts
  post.author -> author.writtenPosts
  author.writtenPosts -> post.author
  category.posts -> post.categories
  category.posts -> post.specialCategory
]

we loop through the relations and find out that

  post.author -> author.writtenPosts
  author.writtenPosts -> post.author

is a pair that can be married without a conflict

  category.posts -> post.categories
  category.posts -> post.specialCategory
  post.categories -> category.posts
  post.specialCategory -> category.posts

is not marriable because we don't know where category.posts should be married to