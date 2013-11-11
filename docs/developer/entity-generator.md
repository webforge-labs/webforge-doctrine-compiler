# The EntityGenerator

The compiler validates the model. While this is happening the "type" from the properties of an entity in the model are evaluated. If these properties reference another entity they are replaced with EntityReference or EntityCollectionReference.

The generator now tries to generate all entities, e.g.: Turning the stdClass-Objects from the read model into `GeneratedEntity` - instances. The main problem here is that cyclic references from entities have to be resolved. For example:

**a simple dependency:** The Author extends the User. Therefore the User has to be generated before the Author.
**a cycle**: The author has many written posts. One post has exactly one author. Therefore the author needs the post to be generated and the post needs the author to be generated.

Find the author first, on generating properties, check if post is already generated. If not create the generated entity empty and store the reference in the property of the posts-property in the author and move on. If the post is then found it is already created but the properties have to be generated. The properties can be generated, because author is already created and generated.