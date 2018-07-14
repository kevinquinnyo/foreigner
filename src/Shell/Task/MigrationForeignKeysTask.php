<?php
namespace Foreigner\Shell\Task;

use Bake\Shell\Task\SimpleBakeTask;
use Cake\Core\Configure;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Migrations\Shell\Task\MigrationTask;

class MigrationForeignKeysTask extends MigrationTask
{
    public $pathFragment = 'config/Migrations/';

    public function name()
    {
        return 'migration_foreign_keys';
    }

    public function template()
    {
        return 'Foreigner.config/migration_foreign_keys';
    }

    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->addOption('model', [
            'short' => 'm',
            'help' => __('The model class to build foreign keys for.'),
        ]);

        return $parser;
    }

    public function templateData()
    {
        $className = $this->BakeTemplate->viewVars['name'];
        $namespace = Configure::read('App.namespace');
        $pluginPath = '';
        if ($this->plugin) {
            $namespace = $this->_pluginNamespace($this->plugin);
            $pluginPath = $this->plugin . '.';
        }
        $modelClass = $this->param('model');

        // FIXME: Add --all option or default to build keys for all models somehow.
        if ($modelClass === null) {
            $this->abort('--model option is currently required.');

            return false;
        }

        $table = TableRegistry::get($modelClass);
        $tables = [$table];
        $foreignKeys = [];
        $associationTypes = [
            BelongsTo::class,
            BelongsToMany::class,
        ];
        foreach ($tables as $table) {
            $associations = $table->associations();
            foreach ($associations as $associatedTable => $association) {
                $relevant = false;
                foreach ($associationTypes as $relevantType) {
                    if ($association instanceOf $relevantType) {
                        $relevant = true;
                    }
                }
                if ($relevant === false) {
                    continue;
                }
                $referenceTable = $association->getTarget()->getTable();
                $tableName = $table->getTable();
                $foreignKeys[$tableName][$referenceTable] = [
                    'foreignKey' => $association->getForeignKey(),
                    'primaryKey' => $table->getPrimaryKey(),
                ];
            }
        }
        return [
            'plugin' => $this->plugin,
            'pluginPath' => $pluginPath,
            'namespace' => $namespace,
            'name' => $className,
            'foreignKeys' => $foreignKeys,
        ];
    }
}
