To broaden consistency checks on the property page and provide more user guidance in case of some input error such as:

![image](https://user-images.githubusercontent.com/1245473/54471080-4559bf00-47ab-11e9-9b61-a79c7fd5c64c.png)

### `src/Property/DeclarationExaminer`

- Extending one of the existing [DeclarationExaminer](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Property/DeclarationExaminer.php) or
- Adding a new class that implements the interface which is then registered with the `DeclarationExaminerFactory` is sufficient to get called when viewing a property page.
