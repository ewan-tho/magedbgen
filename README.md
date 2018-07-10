# Note
This package has been incorporated into [magegen](https://github.com/ewth/magegen). You can still use it as an example or point of interest, but since v0.2, it will no longer work as standalone.

# magedbgen
Database construction generator for Magento 2

This takes a MySQL dump (SQL file) and turns it into a Magento 2 function to create the table.

The function could then be placed in an `InstallSchema` or `UpdateSchema` class.

Note that the output is printed to the console and it won't actually modify any files.

Note also that the engine and default charset are ignored.

## Usage
`php magedbgen.php path/to/dump.sql`
Or:
`php magedbgen.php`
`(type in filename when prompted)`

## Assumptions
- Your lines end in either `\n` or `\r\n`.
- A dump follows the syntax `CREATE TABLE table_name ( ... );`. This is case-insensitive, `table_name` can be surrounded by \` but doesn't have to be, the indentation/structure doesn't matter, and meta-data can preceed the final semicolon.
- Distinct field names are case-consistent, e.g. refer to `field_name_1` always, not `field_name_1` and then `Field_Name_1`.

## Notes
- Does not support relationships, foreign keys or indexes (other than primary key) at the moment.
