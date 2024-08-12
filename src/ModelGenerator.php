<?php

namespace Ibex\CrudGenerator;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Class ModelGenerator.
 */
class ModelGenerator
{
    private $functions = null;

    private $table;
    private $properties;
    private $modelNamespace;

    /**
     * ModelGenerator constructor.
     *
     * @param  string  $table
     * @param  string  $properties
     * @param  string  $modelNamespace
     */
    public function __construct(string $table, string $properties, string $modelNamespace)
    {
        $this->table = $table;
        $this->properties = $properties;
        $this->modelNamespace = $modelNamespace;
        $this->_init();
    }

    /**
     * Get all the eloquent relations.
     *
     * @return array
     */


    public function getLazyLoadRelationsipFields($all = "")
    {
        return $this->_getTableRelationNames($all, true);
    }

    public function getLazyLoadRelationsips($all = "")
    {
        return $this->_getTableRelationNames($all, false);
    }
    /**
     * Get all the eloquent relations tables.
     *
     * @return array
     */

    public function getLazyLoadRelationsipsTables($all = "")
    {
        return $this->_getTableRelationTableNames($all, false);
    }

    public function getEloquentRelations()
    {
        return [$this->functions, $this->properties];
    }

    private function _init()
    {
        $allRelations = $this->_getTableRelations();
        foreach ($allRelations as $relation) {
            $this->functions .= $this->_getFunction($relation);
        }
    }

    private function _getFunction(array $relation)
    {
        switch ($relation['name']) {
            case 'hasOne':
            case 'belongsTo':
                $this->properties .= "\n * @property {$relation['class']} \${$relation['relation_name']}";
                break;
            case 'hasMany':
                $this->properties .= "\n * @property " . $relation['class'] . "[] \${$relation['relation_name']}";
                break;
        }

        return '
    /**
     * @return \Illuminate\Database\Eloquent\Relations\\' . ucfirst($relation['name']) . '
     */
    public function ' . $relation['relation_name'] . '()
    {
        return $this->' . $relation['name'] . '(\\' . $this->modelNamespace . '\\' . $relation['class'] . '::class, \'' . $relation['foreign_key'] . '\', \'' . $relation['owner_key'] . '\');
    }
    ';
    }



    /**
     * Get all relations name from Table.
     *
     * @return array
     */
    private function _getTableRelationNames($all, $exportOnlyFields = false)
    {
        if ($all == "all") {
            return  $this->getBelongsToNames("", $exportOnlyFields) . $this->getOtherRelationNames("", $exportOnlyFields);
        }
        return  $this->getBelongsToNames("", $exportOnlyFields);
    }

    /**
     * Get all relations table names.
     *
     * @return array
     */
    private function _getTableRelationTableNames($all, $exportOnlyFields = false)
    {
        $findTableNames = "true";
        if ($all == "all") {
            return  $this->getBelongsToNames($findTableNames, $exportOnlyFields) . $this->getOtherRelationNames($findTableNames, $exportOnlyFields);
        }
        return  $this->getBelongsToNames($findTableNames, $exportOnlyFields);
    }

    protected function getBelongsToNames($tableNames = "", $exportOnlyFields = false)
    {

        echo "\n\n called get belongs to relationship names at " . date('d-M-Y H:i:s');
        $relations = Schema::getForeignKeys($this->table);

        $eloquent = [];
        $foundClasses = [];
        $relationshipColumns = [];

        foreach ($relations as $relation) {
            if (count($relation['foreign_columns']) != 1 || count($relation['columns']) != 1) {
                continue;
            }

            $relationshipName = "";
            if (Str::camel($relation['columns'][0]) == Str::singular($relation['foreign_table'])) {
                $relationshipName = Str::ucfirst(Str::camel($relation['columns'][0]));
            } else {
                // $relationshipName = Str::ucfirst(Str::camel($relation['columns'][0] . Str::ucfirst(Str::singular($relation['foreign_table']))));
                $relationshipName = Str::ucfirst(Str::camel($relation['columns'][0]));
            }
            if ($tableNames != "") {
                $modelNameCheck = Str::studly(Str::singular($relation['foreign_table']));
                if (!in_array($modelNameCheck, $foundClasses)) {
                    $foundClasses[] = $modelNameCheck;
                    $eloquent[] =  $modelNameCheck;
                    $relationshipColumns[] = $relation['columns'][0];
                }
            } else {
                $eloquent[] = '"' . $relationshipName . '"';
                $relationshipColumns[] = $relation['columns'][0];
            }
        }

        $return = "";

        if (!empty($eloquent)) {
            $return = implode(', ', $eloquent);
        }
        if (!empty($relationshipColumns)) {
            if ($exportOnlyFields == true) {
                $return = implode(',', $relationshipColumns);
            }
        }

        echo " ...ends at " . date('d-M-Y H:i:s');
        return $return;
    }

    protected function getOtherRelationNames($tableNames = "", $exportOnlyFields = false)
    {
        $tables = Schema::getTableListing();
        $eloquent = [];
        $foundClasses = [];
        $relationshipColumns = [];

        echo "\n\n called get other relationship names at " . date('d-M-Y H:i:s');

        foreach ($tables as $table) {
            $relations = Schema::getForeignKeys($table);
            $indexes = collect(Schema::getIndexes($table));

            foreach ($relations as $relation) {
                if ($relation['foreign_table'] != $this->table) {
                    continue;
                }

                if (count($relation['foreign_columns']) != 1 || count($relation['columns']) != 1) {
                    continue;
                }

                $isUniqueColumn = $this->getUniqueIndex($indexes, $relation['columns'][0]);
                $relationshipName = Str::ucfirst($isUniqueColumn ?  Str::ucfirst(Str::singular($table)) :  Str::ucfirst(Str::plural($table)));

                if ($tableNames != "") {
                    $modelNameCheck = Str::studly(Str::singular($relation['foreign_table']));
                    if (!in_array($modelNameCheck, $foundClasses)) {
                        $foundClasses[] = $modelNameCheck;
                        $eloquent[] = $modelNameCheck;
                        $relationshipColumns[] = $relation['columns'][0];
                    }
                } else {
                    $eloquent[] = '"' . $relationshipName . '"';
                    $relationshipColumns[] = $relation['columns'][0];
                }
            }
        }
        $return = "";

        if (!empty($eloquent)) {
            $return = implode(', ', $eloquent);
        }
        if (!empty($relationshipColumns)) {
            if ($exportOnlyFields == true) {
                $return = implode(',', $relationshipColumns);
            }
        }
        echo " ...ends at " . date('d-M-Y H:i:s');

        return $return;
    }

    /**
     * Get all relations from Table.
     *
     * @return array
     */
    private function _getTableRelations()
    {
        return [
            ...$this->getBelongsTo(),
            ...$this->getOtherRelations(),
        ];
    }

    protected function getBelongsTo()
    {

        echo "\n\n called get belongs to relationship at " . date('d-M-Y H:i:s');

        $relations = Schema::getForeignKeys($this->table);

        $eloquent = [];

        foreach ($relations as $relation) {
            if (count($relation['foreign_columns']) != 1 || count($relation['columns']) != 1) {
                continue;
            }

            $relationshipName = "";
            if (Str::camel($relation['columns'][0]) == Str::singular($relation['foreign_table'])) {
                $relationshipName = Str::ucfirst(Str::camel($relation['columns'][0]));
            } else {
                // $relationshipName = Str::ucfirst(Str::camel($relation['columns'][0] . Str::ucfirst(Str::singular($relation['foreign_table']))));
                $relationshipName = Str::ucfirst(Str::camel($relation['columns'][0]));
            }
            $eloquent[] = [
                'name' => 'belongsTo',
                // 'relation_name' => Str::camel(Str::singular($relation['foreign_table'])),
                'relation_name' => $relationshipName,
                'class' => Str::studly(Str::singular($relation['foreign_table'])),
                'foreign_key' => $relation['columns'][0],
                'owner_key' => $relation['foreign_columns'][0],
            ];
        }

        echo " ...ends at " . date('d-M-Y H:i:s');

        return $eloquent;
    }

    protected function getOtherRelations()
    {
        $tables = Schema::getTableListing();
        $eloquent = [];


        echo "\n\n called get other relationship at " . date('d-M-Y H:i:s');

        foreach ($tables as $table) {
            $relations = Schema::getForeignKeys($table);
            $indexes = collect(Schema::getIndexes($table));

            foreach ($relations as $relation) {
                if ($relation['foreign_table'] != $this->table) {
                    continue;
                }

                if (count($relation['foreign_columns']) != 1 || count($relation['columns']) != 1) {
                    continue;
                }

                $isUniqueColumn = $this->getUniqueIndex($indexes, $relation['columns'][0]);

                // $relationshipName =  Str::camel($isUniqueColumn ? Str::singular($table) : Str::plural($table));
                $relationshipName = Str::ucfirst($isUniqueColumn ?  Str::ucfirst(Str::singular($table)) :  Str::ucfirst(Str::plural($table)));
                $eloquent[] = [
                    'name' => $isUniqueColumn ? 'hasOne' : 'hasMany',
                    'relation_name' => Str::camel($isUniqueColumn ? Str::singular($table) : Str::plural($table)),
                    'class' => Str::studly(Str::singular($table)),
                    'foreign_key' => $relation['foreign_columns'][0],
                    'owner_key' => $relation['columns'][0],
                ];
            }
        }

        echo " ...ends at " . date('d-M-Y H:i:s');
        return $eloquent;
    }

    private function getUniqueIndex($indexes, $column)
    {
        $isUnique = false;

        foreach ($indexes as $index) {
            if (
                (count($index['columns']) == 1)
                && ($index['columns'][0] == $column)
                && $index['unique']
            ) {
                $isUnique = true;
                break;
            }
        }

        return $isUnique;
    }
}
