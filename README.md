SQL-Builder
===========

Sql builder is the library that help you create SQL statement in a simpler style.

Example usage

```php
<?php

 $q = new SQLBuilder('users', 'u')
 
 # Simple where clause condition
 $q->filter('username', 'admin')
 $q->to_sql() will compose an sql 
 # >> SELECT * FROM users u WHERE username=?
  
 # More complex where condition
 $q->filter('date_created', '<', time())
 $q->to_sql() 
 # >> SELECT * FROM users u WHERE date_created < ?
 
 # Arbitary condition
 $q->filter('u.setting_id IS NOT NULL')
 # >> SELECT * FROM users u WHERE u.setting IS NOT NUL

```
