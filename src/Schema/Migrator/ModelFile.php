<?php

namespace Migratoro\Schema\Migrator;

use Migratoro\Schema\ModelCommand;

class ModelFile
{
    public static function build(ModelCommand $model)
    {
        $name = $model->getShortName();

        return new ModelBuilder("$name.php", $name, $model);
    }
}
