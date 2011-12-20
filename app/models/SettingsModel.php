<?php

class SettingsModel extends BaseModel
{
        public function addRoot($args)
        {
            $this->getDatabase()->insert('uploadroots', $args)->execute();
        }

        public function deleteRoot($id)
        {
            $this->getDatabase()->delete('uploadroots')->where('id = %i', $id)->execute();
        }

        public function getRoots()
        {
            return $this->getDatabase()->select('*')->from('uploadroots');
        }

        public function getRoot($id)
        {
            return $this->getRoots()->where("id = %i", $id)->fetch();
        }

        public function updateRoot($id, $args)
        {
            $this->getDatabase()->update('uploadroots', $args)
                    ->where('id = %i', $id)
                    ->execute();
        }
}
