# magedbgen
Database construction generator for Magento 2

This takes a MySQL dump (SQL file) and turns it into a Magento 2 function to create the table.

The function could then be placed in an `InstallSchema` or `UpdateSchema` class.

Note that the output is printed to the console and it won't actually modify any files.

## Usage
`php magedbgen.php path/to/dump.sql`
Or:
`php magedbgen.php`
`(paste raw MySQL dump when prompted)`
