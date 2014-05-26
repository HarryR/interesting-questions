Unit tests should be provided for all PHP code writing tasks, and the code should be hosted in a public git repo, e.g. github. The same repo may be used for all examples. For bonus points supply API documentation for each example which has been automatically generated using phpDocumentor.

## Task 1
Write a function/method which exactly duplicates the functionality of [`parse_url()`](http://php.net/parse_url). Do so without without using regular expressions.

## Task 2
Create a dependency injection container class. It doesn't have to be overly complicated or production ready. I'm only looking to see your approach to DI and your approach to writing classes. Use your imagination to decide which features the container should have.

## Task 3
Given the following HTML, use the most recent version of jQuery to add the `target="_blank"` attribute to every anchor tag.

```html
<div id="content">
   <p>
       <a href="http://google.com">Google</a>
   </p>
   <p>
       <a href="http://yahoo.com">Yahoo</a>
   </p>
</div>

```

## Task 4
Given the following database table, optimize the table and provide queries to select
a) A single member by username.
a) All usernames ordered by the date joined ascending.
b) All ids and usernames ordered by login count descending.

```sql
CREATE TABLE `members` (
   `id` int(10) unsigned not null auto_increment,
   `username` varchar(20) not null,
   `password` char(35) not null,
   `email` varchar(255) not null,
   `date_joined` datetime not null,
   `login_count` int(10) unsigned default 0,
   PRIMARY KEY(`id`)
) ENGINE=InnoDB;
```