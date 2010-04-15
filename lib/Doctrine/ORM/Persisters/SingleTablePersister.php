<?php
/*
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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Persister for entities that participate in a hierarchy mapped with the
 * SINGLE_TABLE strategy.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 * @link http://martinfowler.com/eaaCatalog/singleTableInheritance.html
 */
class SingleTablePersister extends AbstractEntityInheritancePersister
{
    /** {@inheritdoc} */
    protected function _getDiscriminatorColumnTableName()
    {
        return $this->_class->table['name'];
    }

    /** {@inheritdoc} */
    protected function _getSelectColumnListSQL()
    {
        $columnList = parent::_getSelectColumnListSQL();
        // Append discriminator column
        $discrColumn = $this->_class->discriminatorColumn['name'];
        $columnList .= ", $discrColumn";
        $rootClass = $this->_em->getClassMetadata($this->_class->rootEntityName);
        $tableAlias = $this->_getSQLTableAlias($rootClass);
        $resultColumnName = $this->_platform->getSQLResultCasing($discrColumn);
        $this->_resultColumnNames[$resultColumnName] = $discrColumn;

        foreach ($this->_class->subClasses as $subClassName) {
            $subClass = $this->_em->getClassMetadata($subClassName);
            // Append subclass columns
            foreach ($subClass->fieldMappings as $fieldName => $mapping) {
                if ( ! isset($mapping['inherited'])) {
                    $columnList .= ', ' . $this->_getSelectColumnSQL($fieldName, $subClass);
                }
            }

            // Append subclass foreign keys
            foreach ($subClass->associationMappings as $assoc) {
                if ($assoc->isOwningSide && $assoc->isOneToOne() && ! $assoc->inherited) {
                    foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                        $columnAlias = $srcColumn . $this->_sqlAliasCounter++;
                        $columnList .= ', ' . $tableAlias . ".$srcColumn AS $columnAlias";
                        $resultColumnName = $this->_platform->getSQLResultCasing($columnAlias);
                        if ( ! isset($this->_resultColumnNames[$resultColumnName])) {
                            $this->_resultColumnNames[$resultColumnName] = $srcColumn;
                        }
                    }
                }
            }
        }

        return $columnList;
    }

    /** {@inheritdoc} */
    protected function _getInsertColumnList()
    {
        $columns = parent::_getInsertColumnList();
        // Add discriminator column to the INSERT SQL
        $columns[] = $this->_class->discriminatorColumn['name'];

        return $columns;
    }

    /** {@inheritdoc} */
    protected function _getSQLTableAlias(ClassMetadata $class)
    {
        if (isset($this->_sqlTableAliases[$class->rootEntityName])) {
            return $this->_sqlTableAliases[$class->rootEntityName];
        }
        $tableAlias = $this->_em->getClassMetadata($class->rootEntityName)->table['name'][0] . $this->_sqlAliasCounter++;
        $this->_sqlTableAliases[$class->rootEntityName] = $tableAlias;

        return $tableAlias;
    }
}