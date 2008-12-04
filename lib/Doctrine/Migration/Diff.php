<?php
/*
 *  $Id: Diff.php 1080 2007-02-10 18:17:08Z jwage $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Migration_Diff - class used for generating differences and migration
 * classes from 'from' and 'to' schema information.
 *
 * @package     Doctrine
 * @subpackage  Migration
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Doctrine_Migration_Diff
{
    protected $_from,
              $_to,
              $_changes = array('created_tables'      =>  array(),
                                'dropped_tables'      =>  array(),
                                'created_fks'         =>  array(),
                                'dropped_fks'         =>  array(),
                                'added_columns'       =>  array(),
                                'removed_columns'     =>  array(),
                                'changed_columns'     =>  array(),
                                'added_indexes'       =>  array(),
                                'removed_indexes'     =>  array()),
              $_migration;

    protected static $_toPrefix   = 'ToPrfx',
                     $_fromPrefix = 'FromPrfx';

    /**
     * Instantiate new Doctrine_Migration_Diff instance
     *
     * <code>
     * $diff = new Doctrine_Migration_Diff('/path/to/old_models', '/path/to/new_models', '/path/to/migrations');
     * $diff->generateMigrationClasses();
     * </code>
     *
     * @param string $from      The from schema information source
     * @param string $to        The to schema information source
     * @param mixed  $migration Instance of Doctrine_Migration or path to migration classes
     * @return void
     */
    public function __construct($from, $to, $migration)
    {
        $this->_from = $from;
        $this->_to = $to;

        if ($migration instanceof Doctrine_Migration) {
            $this->_migration = $migration;
        } else if (is_dir($migration)) {
            $this->_migration = new Doctrine_Migration($migration);
        }
    }

    /**
     * Get unique hash id for this migration instance
     *
     * @return string $uniqueId
     */
    protected function getUniqueId()
    {
        return md5($this->_from . $this->_to);
    }

    /**
     * Generate an array of changes found between the from and to schema information.
     *
     * @return array $changes
     */
    public function generateChanges()
    {
        $from = $this->_generateModels(self::$_fromPrefix, $this->_from);
        $to = $this->_generateModels(self::$_toPrefix, $this->_to);

        return $this->_diff($from, $to);
    }

    /**
     * Generate a migration class for the changes in this diff instance
     *
     * @return array $changes
     */
    public function generateMigrationClasses()
    {
        $builder = new Doctrine_Migration_Builder($this->_migration);

        return $builder->generateMigrationsFromDiff($this);
    }

    /**
     * Generate a diff between the from and to schema information
     *
     * @param  string $from     Path to set of models to migrate from
     * @param  string $to       Path to set of models to migrate to
     * @return array  $changes
     */
    protected function _diff($from, $to)
    {
        // Load the from and to models
        $fromModels = Doctrine::initializeModels(Doctrine::loadModels($from));
        $toModels = Doctrine::initializeModels(Doctrine::loadModels($to));

        // Build schema information for the models
        $fromInfo = $this->_buildModelInformation($fromModels);
        $toInfo = $this->_buildModelInformation($toModels);

        // Build array of changes between the from and to information
        $changes = $this->_buildChanges($fromInfo, $toInfo);
        
        // clean up tmp directories
        Doctrine_Lib::removeDirectories(sys_get_temp_dir() . DIRECTORY_SEPARATOR . strtolower(self::$_fromPrefix) . '_doctrine_tmp_dirs');
        Doctrine_Lib::removeDirectories(sys_get_temp_dir() . DIRECTORY_SEPARATOR . strtolower(self::$_toPrefix) . '_doctrine_tmp_dirs');
        
        return $changes;
    }

    /**
     * Build array of changes between the from and to array of schema information
     *
     * @param array $from  Array of schema information to generate changes from
     * @param array $to    Array of schema information to generate changes for
     * @return array $changes
     */
    protected function _buildChanges($from, $to)
    {
        // Loop over the to schema information and compare it to the from
        foreach ($to as $className => $info) {
            // If the from doesn't have this class then it is a new table
            if ( ! isset($from[$className])) {
                $table = array('tableName' => $info['tableName'],
                               'columns'   => $info['columns'],
                               'options'   => array('type'        => $info['options']['type'],
                                                    'charset'     => $info['options']['charset'],
                                                    'collation'   => $info['options']['collation'],
                                                    'indexes'     => $info['options']['indexes'],
                                                    'foreignKeys' => $info['options']['foreignKeys'],
                                                    'primary'     => $info['options']['primary']));
                $this->_changes['created_tables'][$info['tableName']] = $table;
            }
            // Check for new and changed columns
            foreach ($info['columns'] as $name => $column) {
                // If column doesn't exist in the from schema information then it is a new column
                if (isset($from[$className]) && ! isset($from[$className]['columns'][$name])) {
                    $this->_changes['added_columns'][$info['tableName']][$name] = $column;
                }
                // If column exists in the from schema information but is not the same then it is a changed column
                if (isset($from[$className]['columns'][$name]) && $from[$className]['columns'][$name] != $column) {
                    $this->_changes['changed_columns'][$info['tableName']][$name] = $column;
                }
            }
            // Check for new foreign keys
            foreach ($info['options']['foreignKeys'] as $name => $foreignKey) {
                $foreignKey['name'] = $name;
                // If foreign key doesn't exist in the from schema information then we need to add a index and the new fk
                if ( ! isset($from[$className]['options']['foreignKeys'][$name])) {
                    $this->_changes['created_fks'][$info['tableName']][$name] = $foreignKey;
                    $indexName = Doctrine_Manager::connection()->generateUniqueIndexName($info['tableName'], $foreignKey['local']);
                    $this->_changes['added_indexes'][$info['tableName']][$indexName] = array('fields' => array($foreignKey['local']));
                // If foreign key does exist then lets see if anything has changed with it
                } else if (isset($from[$className]['options']['foreignKeys'][$name])) {
                    $oldForeignKey = $from[$className]['options']['foreignKeys'][$name];
                    $oldForeignKey['name'] = $name;
                    // If the foreign key has changed any then we need to drop the foreign key and readd it
                    if ($foreignKey !== $oldForeignKey) {
                        $this->_changes['dropped_fks'][$info['tableName']][$name] = $oldForeignKey;
                        $this->_changes['created_fks'][$info['tableName']][$name] = $foreignKey;
                    }
                }
            }
            // Check for new indexes
            foreach ($info['options']['indexes'] as $name => $index) {
                // If index doesn't exist in the from schema information
                if ( ! isset($from[$className]['options']['indexes'][$name])) {
                    $this->_changes['added_indexes'][$info['tableName']][$name] = $index;
                }
            }
        }
        // Loop over the from schema information and compare it to the to schema information
        foreach ($from as $className => $info) {
            // If the class exists in the from but not in the to then it is a dropped table
            if ( ! isset($to[$className])) {
                $table = array('tableName' => $info['tableName'],
                               'columns'   => $info['columns'],
                               'options'   => array('type'        => $info['options']['type'],
                                                    'charset'     => $info['options']['charset'],
                                                    'collation'   => $info['options']['collation'],
                                                    'indexes'     => $info['options']['indexes'],
                                                    'foreignKeys' => $info['options']['foreignKeys'],
                                                    'primary'     => $info['options']['primary']));
                $this->_changes['dropped_tables'][$info['tableName']] = $table;
            }
            // Check for removed columns
            foreach ($info['columns'] as $name => $column) {
                // If column exists in the from but not in the to then we need to remove it
                if (isset($to[$className]) && ! isset($to[$className]['columns'][$name])) {
                    $this->_changes['removed_columns'][$info['tableName']][$name] = $column;
                }
            }
            // Check for dropped foreign keys
            foreach ($info['options']['foreignKeys'] as $name => $foreignKey) {
                // If the foreign key exists in the from but not in the to then we need to drop it
                if ( ! isset($to[$className]['options']['foreignKeys'][$name])) {
                    $this->_changes['dropped_fks'][$info['tableName']][$name] = $foreignKey;
                }
            }
            // Check for removed indexes
            foreach ($info['options']['indexes'] as $name => $index) {
                // If the index exists in the from but not the to then we need to remove it
                if ( ! isset($to[$className]['options']['indexes'][$name])) {
                    $this->_changes['removed_indexes'][$info['tableName']][$name] = $index;
                }
            }
        }
        return $this->_changes;
    }

    /**
     * Build all the model schema information for the passed array of models
     *
     * @param  array $models Array of models to build the schema information for
     * @return array $info   Array of schema information for all the passed models
     */
    protected function _buildModelInformation(array $models)
    {
        $fromPrefix = self::$_fromPrefix;
        $toPrefix = self::$_toPrefix;

        $info = array();
        foreach ($models as $key => $model) {
            $table = Doctrine::getTable($model);
            if ($table->getTableName() !== $this->_migration->getTableName()) {
                if (substr($model, 0, strlen($toPrefix)) === $toPrefix) {
                    $name = substr($model, strlen($toPrefix), strlen($model));
                } else if (substr($model, 0, strlen($fromPrefix)) === $fromPrefix) {
                    $name = substr($model, strlen($fromPrefix), strlen($model));
                } else {
                    $name = $model;
                }
                $info[$name] = $table->getExportableFormat();
            }
        }

        return $info;
    }

    /**
     * Generate a set of models for the schema information source
     *
     * @param  string $prefix  Prefix to generate the models with
     * @param  mixed  $item    The item to generate the models from
     * @return string $path    The path where the models were generated
     * @throws Doctrine_Migration_Exception $e
     */
    protected function _generateModels($prefix, $item)
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . strtolower($prefix) . '_doctrine_tmp_dirs';
        $options = array('classPrefix' => $prefix);

        if ( is_string($item) && file_exists($item)) {
            if (is_dir($item)) {
                $files = glob($item . DIRECTORY_SEPARATOR . '*.*');
            } else {
                $files = array($item);
            }

            if (isset($files[0])) {
                $pathInfo = pathinfo($files[0]);
                $extension = $pathInfo['extension'];
            }

            if ($extension === 'yml') {
                Doctrine::generateModelsFromYaml($item, $path, $options);

                return $path;
            } else if ($extension === 'php') {
                Doctrine_Lib::copyDirectory($item, $path);

                return $path;
            } else {
                throw new Doctrine_Migration_Exception('No php or yml files found at path: "' . $item . '"');
            }
        } else {
            try {
                Doctrine::generateModelsFromDb($path, (array) $item, $options);
                return $path;
            } catch (Exception $e) {
                throw new Doctrine_Migration_Exception('Could not generate models from connection: ' . $e->getMessage());
            }
        }
    }
}