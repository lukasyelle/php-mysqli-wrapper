# php-mysqli-wrapper
Having struggled to find a secure, easy, wrapper to use with PHP for MySQLi databases that would prevent SQL Injection attacks, and also make encryption easy, I decided to make my own. 

This PHP MySQLi wrapper makes database integration easy, it aims to improve the security and efficiency of PHP developers. The class makes use of dynamically generated prepared statements based on the query you are preforming, and the database you are preforming it on, while securely escaping all user input before the query is executed.
 
It **will eventually** use the Halite PHP encryption library by Paragon Initiative in the background, to keep the data you need secure, with just a modification the the initial class instantiation.

### Prerequisites

* You need to be using MySQL as your database backend.
* PHPMyAdmin is a great piece of software for administering MySQL databases, and is highly recommended. If you do not already have that installed, the installation process for that can be found on their website [www.phpmyadmin.net](https://www.phpmyadmin.net).  
* It is also recommended that you have PHP 7 installed on the deployment machine.

##### ** NOTE ** This is still in development and as of now the Paragon Initiative cryptology library has not been implemented into the Crypt Class.
If you plan on using the encryption wrapper, you must also install that library.

Instructions for that can be found at [Paragon Initiative's website](https://paragonie.com/project/halite).


### Installation

After you have completed the prerequisites, the installation process is very straight forward.

* Clone this repo, and include the Database.php file in your project.
* Open the config.php file, and configure your server and user information for the mysql backend at the top of the file.
* If you plan on using the Cryptology features this wrapper provides, you must configure the location that the encryption key for your project will be stored. It is highly recommended that this is a location outside the web root on your server. The location is configured at the bottom of the Config.php file.

### Usage

After including Database.php in your project, it is straight forward to use in your work.

```php
<?php

# Instantiate the Table class with the name of the table you would like to access.

$table = new Table("table_name");

# Then, operations like inserting, selecting, updating and soon deleting are made easy and secure.

# Insert query, returns either an array with error information, or "Success."
# The key of the array represents the column, and the value is what to insert.
# If you miss a column, it will use the default value for whatever type the column is.
$insert_result = $table->insert(Array(
    "table_column_1"=>"table_value_1",
    "table_column_2"=>"table_value_2",
    "table_column_3"=>"table_value_3",
));

# Select query, returns an array of each row that matched the sent values.
# This is read, "from my table, select all the rows where table_column = where_value"
$select_result = $table->select(Array("table_column"=>"where_value")); 

# Update query, returns either an array with error information, or "Success."
# This is read:
# "from my table, update all of the following table_columns with the associated new_values where where_table_column = where_value" 
$update_result = $table->update(Array("table_column"=>"new_value"),Array("where_table_column"=>"where_value"));

?>
```

## Authors

* **Lukas Yelle**

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details