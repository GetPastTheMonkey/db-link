# DB Link

*DB Link* is a lightweight database abstraction layer for PHP, partly inspired by [Django](https://www.djangoproject.com/).

Please note that *DB Link* does not manage the database structure for you. If you update a model class, you must update
the database schema accordingly, and vice versa.

## Configuration

For the database connection, *DB Link* will look for a function called `dblink_create_pdo()` that must be defined by the
user. The function must have the following signature:

```php
function dblink_create_pdo(): PDO
```

A basic usage might be the following implementation:

```php
function dblink_create_pdo(): PDO
{
    $db_host = "127.0.0.1";
    $db_name = "my_database";
    $db_user = "my_user";
    $db_password = "this_is_top_secret";

    return new PDO(
        "mysql:dbname=$db_name;host=$db_host",
        $db_user,
        $db_password
    );
}
```

Make sure that this function is included before calling any *DB Link* functions. If the function is not found, a
`LogicException` will be thrown.

## Creating Models

You can create your models by extending the `Model` class. The `get_table_name()` function should return the name of the
relevant database table as a string. The `get_attributes()` function should return an array, where the keys are the
database column names, and the values are instances of a `Field` subclass.

```php
use \Getpastthemonkey\DbLink\Model;
use \Getpastthemonkey\DbLink\fields\IntegerField;
use \Getpastthemonkey\DbLink\fields\CharField;

class User extends Model
{
    protected static function get_table_name(): string
    {
        return "users";
    }

    protected static function get_attributes(): array
    {
        return array(
            "id" => new IntegerField(is_primary_key: true),
            "username" => new CharField(),
            "mail" => new CharField(),
            "is_admin" => new IntegerField(min: 0, max: 1, default: 0),
        );
    }
}
```

Currently, only the field types `IntegerField` and `CharField` are supported, but more field types are planned for
future updates, such as `TextField`, `DateField`, or `DateTimeField`. In the meantime, use `CharField` for these
purposes.

## Run Queries

Here is a showcase of the available query functionality.

```php
// Example 1: Basic example
// Load all users (using the default ordering of the database)
$users = User::objects();

// Example 2: Filtering
// Only load users that have a Gmail address
use \Getpastthemonkey\DbLink\filters\F_LIKE;
$users = User::objects()->filter(new F_LIKE("mail", "%@gmail.com"));

// Example 3: Exclude
// Load all users that do not have a Gmail address
use \Getpastthemonkey\DbLink\filters\F_LIKE;
$users = User::objects()->exclude(new F_LIKE("mail", "%@gmail.com"));

// Example 4: Ordering
// Load all users sorted by increasing name
$users = User::objects()->order("name");

// Example 5: Reverse ordering
// Load all users sorted by decreasing ID
$users = User::objects()->order("id", false);

// Example 6: Limit
// Only load the first 10 users
$users = User::objects()->limit(10);

// Example 7: Limit and offset
// Load 10 users without loading the first 20 users
$users = User::objects()->limit(10, 20);
```

All of these examples return a `Query` object. The function calls may be chained arbitrarily. The `Query` class
implements the `Countable` interface, so the `count()` function can be used to obtain the number of returned rows. The
`Query` class also implements the `Iterator` interface, so the query results  may be obtained by iterating over the
`Query` instance.

### List of Filters

The following table shows all available filter classes. All of them are in the `\Getpastthemonkey\DbLink\filters`
namespace.

| Class     | SQL Equivalent    | Description                                                                     |
|-----------|-------------------|---------------------------------------------------------------------------------|
| F_AND     | a AND b           | Logical AND of two filters                                                      |
| F_BETWEEN | a BETWEEN b AND c | Checks if the first operand is between the second and third operand (inclusive) |
| F_EQ      | a = b             | Checks if the first operand is equal to the second operand                      |
| F_GT      | a > b             | Checks if the first operand is greater than the second operand                  |
| F_GTE     | a >= b            | Checks if the first operand is greater than or equal to the second operand      |
| F_IN      | a IN (b, c, d)    | Checks if the first operand is in the list of given subsequent operands         |
| F_LIKE    | a LIKE b          | Checks if the first operand is "like" the second operand                        |
| F_LT      | a < b             | Checks if the first operand is less than the second operand                     |
| F_LTE     | a <= b            | Checks if the first operand is less than or equal to the second operand         |
| F_NEQ     | a <> b            | Checks if the first operand is not equal to the second operand                  |
| F_NOT     | NOT a             | Logical NOT of a filter                                                         |
| F_OR      | a OR b            | Logical OR of two filters                                                       |

## Accessing Model Attributes

The `Model` class implements the `ArrayAccess` interface, and it overloads most magic methods. Thus, there are two ways
of accessing model attributes.

The first way of accessing the model attributes is via the `ArrayAccess` interface, which allows array-style access.

```php
$users = User::objects();

foreach ($users as $user) {
    echo "<tr>
        <td>" . $user["id"] . "</td>
        <td>" . $user["username"] . "</td>
        <td>" . $user["mail"] . "</td>
        <td>" . $user["is_admin"] . "</td>
    </tr>";
}
```

The second option is to use the magic method overloads by accessing the attributes in a class-member style. Note that
this option might give warnings in IDEs, as the attributes are not defined in the class.

```php
$users = User::objects();

foreach ($users as $user) {
    echo "<tr>
        <td>" . $user->id . "</td>
        <td>" . $user->username . "</td>
        <td>" . $user->mail . "</td>
        <td>" . $user->is_admin . "</td>
    </tr>";
}
```

## Updating Model Attributes

Updating model attributes is possible in the same two ways as accessing model attributes: Either through the
`ArrayAccess` interface or the overloaded magic methods.

Here is an example that uses the `ArrayAccess` interface to update an attribute and save it back to the database.
Saving internally also validated the current field values.

```php
use \Getpastthemonkey\DbLink\exceptions\ValidationException;

$user = User::objects()->current();
$user["is_admin"] = 1;

try {
    $user->save();
} catch (ValidationException $e) {
    // The instance did not pass the validation checks
    // Use $e->children to get an array of validation exceptions with more details about the failed checks
}
```

To check if an instance is valid without saving it, call `validate()` instead of `save()`. If the instance is invalid,
a `ValidationException` will be thrown. If it instead is valid, nothing will happen.

## Creating New Instances

By calling the constructor of a `Model` subclass, a new model instance is created. All fields hold the defined default
value. If a field does not have a default value, it will be `NULL`. Note that the new instance only exists in the PHP
runtime, but it is not saved in the database. To add the instance to the database, you must call `save()`.

Here is an example of how to create a new `User` and save it to the database.

```php
use \Getpastthemonkey\DbLink\exceptions\ValidationException;

$user = new User();
$user["id"] = 123;
$user["username"] = "my_username";
$user["mail"] = "me@example.com";

try {
    $user->save();
} catch (ValidationException $e) {
    // The instance did not pass the validation checks
    // Use $e->children to get an array of validation exceptions with more details about the failed checks
}
```

## Deleting Instances

Single model instances can be deleted by calling the `delete()` function. Note that the database entry will be deleted,
but the model instance still exists in the code.

Here is an example for how to delete a user with ID 123.

```php
use \Getpastthemonkey\DbLink\filters\F_EQ;

$user = User::objects()->filter(new F_EQ("id", 123))->current();
$user->delete();
```

Bulk deletion is supported by calling `delete()` on a `Query` instance. All entries returned by the query will be
deleted.

Here is an example for how to delete all users that are not administrators.

```php
use \Getpastthemonkey\DbLink\filters\F_EQ;

User::objects()->filter(new F_EQ("is_admin", 0))->delete();
```

## Troubleshooting

As *DB Link* is a new project, there is not yet a complete guide to frequently asked questions or problems. If you have
any questions or problems, do not hesitate to open a [GitHub issue](https://github.com/GetPastTheMonkey/db-link/issues).
