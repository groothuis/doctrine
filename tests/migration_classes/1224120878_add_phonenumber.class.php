<?php
/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class AddPhonenumber extends Doctrine_Migration_Base
{
    public function migrate($direction)
    {
        $this->createTable($direction, 'migration_phonenumber', array('id' => array('type' => 'integer', 'length' => 20, 'autoincrement' => true, 'primary' => true), 'user_id' => array('type' => 'integer', 'length' => 2147483647), 'phonenumber' => array('type' => 'string', 'length' => 2147483647)), array('indexes' => array(), 'primary' => array(0 => 'id')));
    }
}