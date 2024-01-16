<?php

namespace Migratoro\Schema\Migrator;

use Migratoro\Schema\ModelCommand;

/** @var string $name */

/** @var ModelCommand $model */
class ModelBuilder
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ModelCommand
     */
    private $model;

    /**
     * @var string
     */
    private $filename;

    public function __construct(string $filename, string $name, ModelCommand $model)
    {
        $this->name = $name;
        $this->model = $model;
        $this->filename = $filename;
    }

    public function __toString()
    {
        return $this->render();
    }

    public function render()
    {
        return "<?php\n".
            "namespace {$this->namespaceStmt()};\n".
            "\n".
            $this->use1()."\n".
            "\n".
            "class {$this->name} extends {$this->extendsStmt()}\n{\n".
            $this->guarded().$this->primaryKey().$this->casts()."\n".
            $this->methods()."\n".
            '}';
    }

    private function namespaceStmt()
    {
        return $namespace = trim($this->model->getNamespace(), '\\');
    }

    private function use1()
    {
        $use = 'use Illuminate\Database\Eloquent\Model;';
        if ($this->model->extendsPivot()) {
            $use = 'use Illuminate\Database\Eloquent\Relations\Pivot;';
        }

        return $use;
    }

    private function extendsStmt()
    {
        $extends = 'Model';
        if ($this->model->extendsPivot()) {
            $extends = 'Pivot';
        }

        return $extends;
    }

    public function guardedFieldNames()
    {
        return collect($this->model->getGuardedFields())->map->getName()->all();
    }

    private function quote($fields)
    {
        return array_map(function ($i) {
            return "'".addslashes($i)."'";
        }, $fields);
    }

    public function guarded($fields = null)
    {
        $guardedFields = implode(', ', $this->quote($fields ?? $this->guardedFieldNames()));

        return $guarded = "    protected \$guarded = [$guardedFields];\n";
    }

    private function primaryKey()
    {
        $primaryKey = '';
        if ($this->model->getPrimaryKeyFieldNames() != ['id']) {
            $pk = $this->model->getPrimaryKeyFieldNamesExpectOne("model generation of `{$this->model->getShortName()}` requires simple Primary Key");
            $primaryKey = "    protected \$primaryKey = '$pk';\n";
        }

        return $primaryKey;
    }

    public function castFields()
    {
        $res = [];
        if ($fields = $this->model->getFieldsWithType(['json', 'jsonb', 'collection', 'array', 'datetime', 'date', 'dateTimeTz'])) {
            $map = ['jsonb' => 'json', 'dateTimeTz' => 'datetime'];
            foreach ($fields as $field) {
                $type = data_get($map, $field->getFieldType(), $field->getFieldType());
                $res[$field->getName()] = $type;
            }
        }

        return $res;
    }

    public function casts($originalCastsString = '')
    {
        $casts = '';
        $toCast = $this->castFields();
        if ($toCast) {
            $casts = "\n    protected \$casts = [\n";
            foreach ($toCast as $name => $type) {
                // preserve original class/string-based casts
                if($originalCastsString && preg_match("#['\"]{$name}['\"]\s*=>\s*(['\"][^'\"]+['\"]|\S*?::class)#", $originalCastsString, $m)) {
                    $casts .= "        '$name' => $m[1],\n";
                } else {
                    $casts .= "        '$name' => '$type',\n";
                }
            }
            $casts .= '    ];';
        }

        return $casts;
    }

    public function methods()
    {
        return implode("\n\n", $this->singleMethods());
    }

    public function singleMethods()
    {
        $methods = [];

        foreach ($this->model->getMethods() as $method) {
            $txt = "    public function {$method->getName()}()\n    {\n";
            $txt .= "        return \$this->{$method->laravelRelationCall()};\n";
            $txt .= '    }';
            $methods[$method->getName()] = $txt;
        }

        return $methods;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }
}
