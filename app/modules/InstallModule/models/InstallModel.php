<?php

namespace InstallModule;

class InstallModel extends BaseModel
{
        public function createDB($path)
        {
            if (file_exists($path))
                unlink($path);

            try {
                    $database = new \SQLiteDatabase($path, 0666, $error);
            } catch (Exception $e) {
                    throw new \Nette\Application\ApplicationException($error);
            }
        }

        public function importDB($db_path, $dump_path)
        {
            \dibi::connect(array(
                'driver' => 'sqlite',
                'database' => $db_path,
                'profiler' => true
            ));

            return \dibi::loadFile($dump_path);
        }
}