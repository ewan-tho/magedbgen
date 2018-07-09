<?php

const TAB = '    ';

echo "MySQL dump to process: ";
$mysqlDump = null;
if (!empty($argv[1])) {
    $filePath = $argv[1];
    echo $filePath . "\n";
} else {
    $handle = fopen("php://stdin","r");
    $filePath = trim(fgets($handle));
    fclose($handle);
    if (strpos($filePath, "\n") !== "false") {
        $mysqlDump = $filePath;
    }
}

if (!file_exists($filePath)) {
    die("File '${filePath}' could not be found.\n");
}

$mysqlDump = file_get_contents($filePath);


// Clean up a bit first
$mysqlDump = preg_replace('#ENGINE=(.[^ \;]+)#', '', $mysqlDump);
$mysqlDump = preg_replace('#DEFAULT CHARSET=(.[^ \;]+)#', '', $mysqlDump);
$mysqlDump = str_replace("\r\n", "\n", $mysqlDump);
$mysqlDump = str_replace("\n\n", chr(5), $mysqlDump);
$mysqlDump = str_replace("\n", '', $mysqlDump);
$mysqlDump = explode(chr(5), $mysqlDump);
$tab2 = str_repeat(TAB, 2);
$tab3 = str_repeat(TAB, 3);
$tab4 = str_repeat(TAB, 4);
$tab5 = str_repeat(TAB, 5);
$function = [];


// Setup types
$types = [
    'int' => 'TYPE_INTEGER',
    'tinyint' => 'TYPE_BOOLEAN',
    'bigint' => 'TYPE_BIGINT',
    'decimal' => 'TYPE_DECIMAL',
    'timestamp' => 'TYPE_TIMESTAMP',
    'text' => 'TYPE_TEXT',
    'blob' => 'TYPE_BLOB',
    'varchar' => 'TYPE_TEXT',
    'varbinary' => 'TYPE_VARBINARY'
];

// Setup defaults
$defaults = [
    'current_timestamp()' => 'TIMESTAMP_INIT'
];

foreach ($mysqlDump as $item) {
    if( preg_match('#^create table (.[^\(]+)\(#i', $item, $matches)) {
        $table = trim(str_replace('`', '', $matches[1]));
        $content = str_replace($matches[0], '', $item);
        $primaryKey = null;
        if (preg_match('# primary key(.[^\(]*)\((.[^\)]+)\)#i', $content, $matches)) {
            $primaryKey = trim(str_replace('`','', $matches[2]));
            $content = str_replace($matches[0], '', $content);
        }

        if(preg_match_all('#([a-zA-Z0-9\_\`]+) ([a-zA-Z]+)( ?\(.[^\)]+\))? (UNSIGNED )?(DEFAULT )?(NOT )?NULL( .[^,]+)?#i', $content, $matches)) {

            $tableCamelCase = $table;

            if (strpos($tableCamelCase, '_') !== false) {
                $tableCamelCase = explode('_', $tableCamelCase);
                $segments = [];
                foreach ($tableCamelCase as $item) {
                    $segments[] = ucwords($item);
                }
                $tableCamelCase = implode('', $segments);
            }

            $function[] = '/**';
            $function[] = '* @param \Magento\Framework\Setup\SchemaSetupInterface $setup';
            $function[] = '*';
            $function[] = '* @throws \Zend_Db_Exception';
            $function[] = '*/';
            $function[] = 'private function createTable' . $tableCamelCase . '(SchemaSetupInterface $setup)';
            $function[] = '{';
            $function[] = TAB . '$newTable = $setup->getConnection()';
            $function[] = $tab2 . '->newTable(\'' . addslashes($table) . '\')';

            foreach ($matches[0] as $key => $match) {

                $columnName = '';
                $columnType = '';
                $columnSize = '';
                $columnOptions = [];
                $columnComment = '';

                // field name
                $columnName = str_replace('`', '', $matches[1][$key]);

                // type
                $columnTypeKey = strtolower(trim($matches[2][$key]));

                if (!isset($types[$columnTypeKey])) {
                    die("\n\n** ERROR ** An unknown column type was detected: '${columnTypeKey}'. Execution halted.\n\n");
                }

                $columnType = '\Magento\Framework\DB\Ddl\Table::' . $types[$matches[2][$key]];


                // size
                if (!empty($matches[3][$key])) {
                    $columnSize = preg_replace('#[^0-9,]#', '', $matches[3][$key]);
                    if (strpos($columnSize, ',') === false) {
                        $columnSize = (int)$columnSize;
                    } else {
                        $columnSize = '\'' . $columnSize . '\'';
                    }
                }

                // unsigned
                if (trim(strtolower($matches[4][$key])) == 'unsigned') {
                    $columnOptions[] = '\'unsigned\' => true';
                }

                // default null
                if (strtolower(trim($matches[5][$key])) == 'default') {
                    $columnOptions[] = '\'nullable\' => true';
                }

                // not null
                if (strtolower(trim($matches[6][$key])) == 'not') {
                    // note that we may end up with two nullable indexes; ignore
                    $columnOptions[] = '\'nullable\' => false';
                }

                // comment
                if (preg_match('#COMMENT \'(.+)\'#i', $matches[7][$key], $comment)) {
                    $columnComment = addslashes($comment[1]);
                }

                // default value
                if (preg_match('#DEFAULT ((\')(.[^\']+)(\2)|(.[^\(\)]+\(\)))#i', $matches[7][$key], $default)) {
                    if (empty($default[3])) {
                        $defaultKey = strtolower(trim($default[1]));
                        if (isset($defaults[$defaultKey])) {
                            $columnOptions[] = '\'default\' => \Magento\Framework\DB\Ddl\Table::' . $defaults[$defaultKey];
                        } else {
                            $columnOptions[] = '\'default\' => ' . $default[1];
                        }
                    } else {
                        $columnOptions[] = '\'default\' => \'' . $default[3] . '\'';
                    }
                }

                // primary key
                if (!empty($primaryKey) && $primaryKey == $columnName) {
                    $columnOptions[] = '\'primary\' => true';
                    if (trim(strtolower($matches[7][$key])) == 'auto_increment') {
                        $columnOptions[] = '\'identity\' => true';
                    }
                }

                // function output
                $function[] = $tab2 . '->addColumn(';
                $function[] = $tab3 . '\'' . $columnName . '\',';
                $function[] = $tab3 . $columnType . ',';
                $function[] = $tab3 . (empty($columnSize) ? 'null' : $columnSize) . ',';
                if (empty($columnOptions)) {
                    $function[] = $tab3 . '[],';
                } else {
                    $function[] = $tab3 . '[';
                    $function[] = $tab3 . implode(",\n$tab4", $columnOptions);
                    $function[] = $tab3 . '],';
                }
                $function[] = $tab3 . (empty($columnComment) ? '\'\'' : '\'' . $columnComment . '\'');
                $function[] = $tab2 . ')';
             }
             $function[count($function)-1] .= ';';
            $function[] = TAB . '$setup->getConnection()->createTable($newTable);';
            $function[] = '}';
        }
    }
}
echo "\n\n";

$function = implode("\n", $function);
$function = explode("\n", $function);
$function = implode("\n" . TAB, $function);
echo TAB . $function . "\n";

?>
