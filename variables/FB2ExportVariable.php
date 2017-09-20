<?php

namespace Craft;

class FB2ExportVariable
{
    public function getAllForms()
    {
      $query = craft()->db->createCommand();

      $query->from('formbuilder2_forms');

      return $query->queryAll();
    }
}

?>