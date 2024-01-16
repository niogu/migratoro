<?php

namespace Migratoro\Tests;

use Migratoro\Schema\FieldCommand;
use Migratoro\Schema\Migrator\MergeModelFiles;
use Migratoro\Schema\Migrator\ModelBuilder;
use Migratoro\Schema\ModelCommand;
use Migratoro\Schema\Parser;
use Migratoro\Schema\Schema;

class MergeFileTest extends BaseTestCase
{
    use GenerateAndRun;

    /** @test */
    public function merges_namespacestmt()
    {
        $this->markTestIncomplete('TODO@slava: create test ');
    }

    /** @test */
    public function merges_use1()
    {
        $this->markTestIncomplete('TODO@slava: create test ');
    }

    /** @test */
    public function merges_extendsstmt()
    {
        $this->markTestIncomplete('TODO@slava: create test ');
    }

    /** @test */
    public function merges_guarded()
    {
        $this->generateAndRun('
            namespace {namespace}
            
            User
                is_admin: boolean Guarded
        ');

        $this->generateAndRun('
            namespace {namespace}
            
            User
                is_admin: boolean Guarded
                is_admin2: boolean Guarded
        ');

        $this->assertModelsContain("protected \$guarded = ['is_admin', 'is_admin2'];");
    }

    /** @test */
    public function merges_guarded1()
    {
        $this->generateAndRun('
            namespace {namespace}
            
            User
                is_admin: boolean
        ');

        $this->generateAndRun('
            namespace {namespace}
            
            User
                is_admin: boolean Guarded
        ');

        $this->assertModelsContain("protected \$guarded = ['is_admin'];");
    }

    /** @test */
    public function merges_primarykey()
    {
        $this->markTestIncomplete('TODO@slava: create test ');
    }

    /** @test */
    public function merges_casts()
    {
        $this->generateAndRun('
            namespace {namespace}
            
            User
                name: string
        ');

        $this->generateAndRun('
            namespace {namespace}

            User
                name: string
                js1: json 
        ');

        $this->generateAndRun('
            namespace {namespace}

            User
                name: string
                js1: json 
                js2: json 
        ');

        $this->assertStringContainsString('$casts', $this->getModelContents('User'));
        $this->assertStringContainsString('js1', $this->getModelContents('User'));
        $this->assertStringContainsString('js2', $this->getModelContents('User'));

        $user = $this->newInstanceOf('User')->create(['js1' => [1], 'js2' => [2]]);
        $this->assertEquals([1], $this->newInstanceOf('User')->first()->js1);
        $this->assertEquals([2], $this->newInstanceOf('User')->first()->js2);
    }

    /** @test */
    public function preserves_custom_class_based_casts()
    {
        $ns = $this->generateAndRun('
            namespace {namespace}
            
            User
                name: string
        ');

        $this->generateAndRun('
            namespace {namespace}

            User
                name: string
                js1: json 
                js2: json 
                js3: json 
                js4: json 
        ');

        $filename = array_keys($this->migrator->modelsUpdated)[0];
        $this->replaceTextInModel(
            $filename,
            "'js1' => 'json',",
            "'js1' => CustomCastClass::class,"
        );

        $this->replaceTextInModel(
            $filename,
            "'js2' => 'json',",
            "'js2' => \Custom\CustomCastClass::class,"
        );

        $this->replaceTextInModel(
            $filename,
            "'js3' => 'json',",
            "'js3' => '\\Custom\\CustomCastClass',"
        );

        $this->replaceTextInModel(
            $filename,
            "'js4' => 'json',",
            "'js4' => 'CustomCastClass',"
        );

        $this->generateAndRun('
            namespace {namespace}

            User
                name: string
                js1: json
                js2: json 
                js3: json 
                js4: json 
        ');

        $this->assertStringContainsString("'js1' => CustomCastClass::class,", $this->getModelContents('User'));
        $this->assertStringContainsString("'js2' => \\Custom\\CustomCastClass::class,", $this->getModelContents('User'));
        $this->assertStringContainsString("'js3' => '\\Custom\\CustomCastClass',", $this->getModelContents('User'));
        $this->assertStringContainsString("'js4' => 'CustomCastClass',", $this->getModelContents('User'));
    }

    /** @test */
    public function merges_dates()
    {
        $this->markTestIncomplete('TODO@slava: create test ');
    }

    /** @test */
    public function should_add_guarded_if_it_was_removed()
    {
        $file = '<?php namespace App;
use Illuminate\Database\Eloquent\Model;
class GeoRect extends Model
{
}';
        $tmp = tempnam('/tmp', 'model');
        file_put_contents($tmp, $file);
        $schema = new Schema();
        $model = ModelCommand::fromString('GeoRect', 'App');
        $field = FieldCommand::fromString('history: json Guarded', $model);
        $field->setSchema($schema);
        $model->addField($field);
        (new MergeModelFiles($tmp, new ModelBuilder($tmp, 'GeoRect', $model)))->merge();
        $newContent = file_get_contents($tmp);
        $this->assertStringContainsString('$guarded', $newContent);
        $this->assertStringNotContainsString("\n\n\n", $newContent);

        // should not change
        (new MergeModelFiles($tmp, new ModelBuilder($tmp, 'GeoRect', $model)))->merge();
        $newContent2 = file_get_contents($tmp);
        $this->assertEquals($newContent, $newContent2);
    }

    /** @test */
    public function should_add_casts_if_it_was_removed()
    {
        $file = '<?php namespace App;
use Illuminate\Database\Eloquent\Model;
class GeoRect extends Model
{
}';
        $tmp = tempnam('/tmp', 'model');
        file_put_contents($tmp, $file);
        $schema = new Schema();
        $model = ModelCommand::fromString('GeoRect', 'App');
        $field = FieldCommand::fromString('history: json', $model);
        $field->setSchema($schema);
        $model->addField($field);
        (new MergeModelFiles($tmp, new ModelBuilder($tmp, 'GeoRect', $model)))->merge();
        $newContent = file_get_contents($tmp);
        $this->assertStringContainsString('$casts', $newContent);
        $this->assertStringNotContainsString("\n\n\n", $newContent);
        $this->assertStringNotContainsString("{\n\n", $newContent);

        // should not change
        (new MergeModelFiles($tmp, new ModelBuilder($tmp, 'GeoRect', $model)))->merge();
        $newContent2 = file_get_contents($tmp);
        $this->assertEquals($newContent, $newContent2);
    }

    /** @test */
    public function should_add_methods()
    {
        $file = '<?php namespace App;
use Illuminate\Database\Eloquent\Model;
class GeoRect extends Model
{
}';
        $tmp = tempnam('/tmp', 'model');
        file_put_contents($tmp, $file);
        $schema = new Schema();

        $p = new Parser();
        $schema = $p->parse('
        GeoRect
            histories()
        History
            geo_rect()
        ');

        $model = $schema->getModel('GeoRect');

        (new MergeModelFiles($tmp, new ModelBuilder($tmp, 'GeoRect', $model)))->merge();
        $newContent = file_get_contents($tmp);
        $this->assertStringNotContainsString("\n\n\n", $newContent);
        $this->assertStringNotContainsString("{\n\n", $newContent);
        $this->assertStringContainsString('public function histories()', $newContent);

        // should not change
        (new MergeModelFiles($tmp, new ModelBuilder($tmp, 'GeoRect', $model)))->merge();
        $newContent2 = file_get_contents($tmp);
        $this->assertEquals($newContent, $newContent2);
    }

    /** @test */
    public function merges_datetime()
    {
        $ns = $this->generateAndRun('
            namespace {namespace}
            Item
        ');

        $ns = $this->generateAndRun('
            namespace {namespace}
            Item
                last_bought: datetime
        ');

        $this->assertStringContainsString('last_bought', $this->getModelContents('Item'));
        $this->assertStringContainsString("protected \$casts = [\n        'last_bought' => 'datetime',\n    ];\n",
            $this->getModelContents('Item'));

        $ns = $this->generateAndRun('
            namespace {namespace}
            Item
                last_bought: datetime
                last_sold: datetime
        ');

        $this->assertStringContainsString('last_sold', $this->getModelContents('Item'));
        $this->assertStringContainsString("protected \$casts = [\n        'last_bought' => 'datetime',\n        'last_sold' => 'datetime',\n    ];\n",
            $this->getModelContents('Item'));
    }

    public function replaceTextInModel($filename, string $what, string $withWhat): void
    {
        // $filename = __DIR__ . '/' . str_replace('\\', '/', $ns) . '/' . $model . '.php';
        $model = file_get_contents($filename);
        $model = str_replace($what, $withWhat, $model);
        file_put_contents($filename, $model);
    }
}
