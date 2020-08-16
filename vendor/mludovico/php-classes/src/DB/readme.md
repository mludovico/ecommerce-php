# Secrets

For security reasons confidential information should not be commited to repo
Therefor, I used a secrets.php file to hold that info and this file, in addition to be added to gitignore, have the information as the following example

## secrets.php example:

```
  <?php
    const HOSTNAME = "ip or hostname";
    const USERNAME = "username";
    const PASSWORD = "password";
    const DBNAME = "db_name";
  ?>
```

