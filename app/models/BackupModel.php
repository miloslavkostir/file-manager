<?php

class BackupModel extends BaseModel
{
        /**
         * Get backup files
         * @return SplFileInfo Object
         */
        public function load()
        {
                return \Nette\Utils\Finder::findFiles("*.sql")
                        ->in($this->context->parameters["backupDir"]);
        }

        public function save()
        {
                $path = $this->context->parameters["backupDir"] . "/";

                $fp = fopen($path . $this->checkDuplName($path, "backup.sql"), "w");
                fputs($fp, $this->getDump());
                fclose($fp);
        }

        public function delete($file)
        {
                return unlink($this->context->parameters["backupDir"] . "/" . $file);
        }

        public function getFile($file)
        {
                return $this->context->parameters["backupDir"] . "/" . $file;
        }

        public function restore($file)
        {
                $path = $this->context->parameters["backupDir"] . "/$file";
                if (file_exists($path))
                    return $this->getDatabase()->loadFile($path);
                else
                    return false;
        }

        private function getDump()
        {
                $db = $this->getDatabase();
                $driver = strtolower($db->getConfig("driver"));

                if ($driver == "sqlite")
                    return $this->sqlite2($db);
        }

        /**
         * Based on http://forum.dibiphp.com/cs/661-dibi-sqldump
         */
        private function sqlite2(DibiConnection $db)
        {
                $time = date("l jS \of F Y h:i:s A", time());
                $output = "-- NetFileMan SQLite 2 dump\n";
                $output .= "-- Generated on $time\n\n";

                $tables = $db->select("name")
                            ->from("SQLITE_MASTER")
                            ->where("type = 'table'");

                foreach ($tables as $table) {
                    $table = reset($table);

                    $rows = $db->select("sql")
                            ->from("sqlite_master")
                            ->where("tbl_name = %s", $table)
                            ->fetch();

                    $output .= "DROP TABLE [$table];\n\n";

                    foreach ($rows as $row) {
                        $output .= "$row;\n\n";
                    }

                    $values = $db->select("*")->from("%n", $table);
                    if (count($values) > 0)
                    {
                            $columns = array();
                            foreach ($db->query("PRAGMA table_info('$table')")->fetchAll() as $column) {
                                $columns[] = "'" . $column["name"] . "'";
                            }
                            $columns = implode(',', $columns);

                            foreach ($values as $row)
                            {
                                $output .= "INSERT INTO '$table' ($columns) VALUES\n";
                                $val = array();
                                foreach ($row as $field => $value) {
                                    // choose escaping according to column type
                                    // echo $column[$field]['Type'];
                                    if (empty($value))
                                        $val[$field] = "''";
                                    else {
                                        if (is_string($value))
                                            $val[$field] = "'$value'";  // TODO escaping
                                        else
                                            $val[$field] = $value;  // TODO escaping
                                    }
                                }
                                $output .= "(" . implode(',', $val) . ");\n\n";
                            }
                    }
                }

                return $output;
        }

        /**
         * Check if file exists and give alternative name
         * @param  string  actual dir (absolute path)
         * @param  string  filename
         * @return string
         */
        private function checkDuplName($path, $filename)
        {
                if (file_exists($path . $filename)) {
                    $i = 1;
                    while (file_exists($path . $i . "_$filename")) {
                        $i++;
                    }
                    return $i . "_$filename";
                } else
                    return $filename;
        }
}