# Task 4

Given the following database table, optimize the table and provide queries to select

 1. A single member by username.
 2. All usernames ordered by the date joined ascending.
 3. All ids and usernames ordered by login count descending.

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



## 1. A single member by username

```sql
CREATE TABLE `members` (
   `id` int(10) unsigned not null auto_increment,
   `username` varchar(20) not null,
   `password` char(35) not null,
   `email` varchar(255) not null,
   `date_joined` datetime not null,
   `login_count` int(10) unsigned default 0,
   PRIMARY KEY(`id`),
   UNIQUE KEY(`username`)
) ENGINE=InnoDB;
```

```sql
SELECT * FROM members WHERE username = 'theusername';
```



## 2. All usernames ordered by the date joined ascending.

```sql
CREATE TABLE `members` (
   `id` int(10) unsigned not null auto_increment,
   `username` varchar(20) not null,
   `password` char(35) not null,
   `email` varchar(255) not null,
   `date_joined` datetime not null,
   `login_count` int(10) unsigned default 0,
   PRIMARY KEY(`id`),
   UNIQUE KEY(`username`),   
   INDEX (`date_joined`)
) ENGINE=InnoDB;
```

```sql
SELECT username FROM members ORDER BY date_joined
```


## 3. All ids and usernames ordered by login count descending.

```sql
CREATE TABLE `members` (
   `id` int(10) unsigned not null auto_increment,
   `username` varchar(20) not null,
   `password` char(35) not null,
   `email` varchar(255) not null,
   `date_joined` datetime not null,
   `login_count` int(10) unsigned default 0,
   PRIMARY KEY(`id`),
   UNIQUE KEY(`username`),   
   INDEX (`date_joined`),
   INDEX (`login_count`)
) ENGINE=InnoDB;
```

```sql
SELECT username FROM members ORDER BY login_count DESC
```