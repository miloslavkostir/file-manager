<?php

use Nette\Object;

class SettingsModel extends Object
{
        public function addRoot($args)
        {
            dibi::insert('uploadroots', $args)->execute();
        }

        public function deleteRoot($id)
        {
            dibi::delete('uploadroots')->where('id = %i', $id)->execute();
        }

        public function getRoots()
        {
            return dibi::select('*')->from('uploadroots');
        }

        public function getRoot($id)
        {
            return $this->getRoots()->where('id = %i', $id)->limit('1')->fetchAll();
        }

        public function updateRoot($id, $args)
        {
            dibi::update('uploadroots', $args)
                    ->where('id = %i', $id)
                    ->execute();
        }
}
