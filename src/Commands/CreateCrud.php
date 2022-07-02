<?php

namespace Aboulhouda\Crud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CreateCrud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {entity}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate crud created by IMAD ABOULHOUDA';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $entity = $this->argument("entity");
        $model_prefix = "App\Models";
        $modal = $model_prefix . '\\' . $entity;

        $columns = $this->getColumn((new $modal)->getTable(), false);


        $rules = "[";
        foreach ($columns as $col) {
            if (!in_array($col, ['id', 'created_at', 'updated_at'])) {
                $type = $this->getTypeEl((new $modal)->getTable(), $col);

                $rules .= "'$col' => ['required',";
                if (in_array($type, ['int', 'bigint'])) {
                    $rules .= "'integer',";
                } elseif ($type == "boolean") {
                    $rules .= "'boolean',";
                }
                $rules .= "],\n";
            }
        }


        $rules .= "]";


        $this->createController($entity, $rules, $columns);
        $this->generateRoute($entity);
        $this->generateView($entity, $columns[1], $columns, (new $modal));
    }
    private function getTypeEl($table, $column)
    {
        return Schema::getColumnType($table, $column);
    }
    private function getColumn($table, $first = true)
    {
        $columns = Schema::getColumnListing($table);
        return $first ? $columns[1] : $columns;
    }
    private function createController($name, $rules)
    {
        $namex = (app_path("Http/Controllers/") . $name . "Controller.php");

        $file = file_get_contents(dirname(__FILE__) . "/templates/XController.template");
        $data = str_replace('%rules%', $rules, $file);
        $data = str_replace('%model%', $name, $data);
        file_put_contents($namex, $data);
    }

    private function generateRoute($name)
    {
        $x = base_path() . "/routes/";
        $d = file_get_contents($x . "/web.php");
        $xSearch = "Route::resource('$name'," . $name . "Controller::class);";

        $n = $d . "\n";
        $n .= "Route::resource('$name'," . $name . "Controller::class);";
        if (strpos($d, $xSearch) === false) {
            file_put_contents($x . "web.php", $n);
        }
    }
    private function generateView($name, $col, $columns = [])
    {
        //Generate index
        $folder = resource_path() . "/views/";
        if (!is_dir($folder . $name)) {
            mkdir($folder . $name, 0775);
        }
        $file = file_get_contents(dirname(__FILE__) . "/templates/index.blade.template");

        $data = str_replace('%model%', $name, $file);
        $data = str_replace('%col%', $col, $data);

        file_put_contents($folder . $name . "/index.blade.php", $data);
        //Generate Create
        $file = file_get_contents(dirname(__FILE__) . "/templates/create.blade.template");

        $data = str_replace('%model%', $name, $file);
        $data = str_replace('%col%', $col, $data);
        $dataShow = "";
        foreach ($columns  as $col) {
            $this->generateForm($col, $dataShow);
        }

        $data = str_replace('%dataShow%', $dataShow, $data);

        file_put_contents($folder . $name . "/create.blade.php", $data);


        //Generate uPDATE

        $file = file_get_contents(dirname(__FILE__) . "/templates/update.blade.template");

        $data = str_replace('%model%', $name, $file);
        $data = str_replace('%col%', $col, $data);
        $dataShow = "";
        foreach ($columns  as $col) {
            $this->generateForm($col, $dataShow, true);
        }

        $data = str_replace('%dataShow%', $dataShow, $data);

        file_put_contents($folder . $name . "/edit.blade.php", $data);
    }


    public function generateForm($col, &$dataShow, $edit = false)
    {
        if (!in_array($col, ['id', 'created_at', 'updated_at'])) {
            $dataShow .= '<div class="form-group">
                <label for="">' . $col . '</label>';
            $el = substr($col, -3);;
            if ($el == "_id") {
                $ent = str_replace("_id", "", $col);
                $ent = ucfirst($ent);
                $model_prefix = "App\Models";
                $model = $model_prefix . '\\' . $ent;
                $mTable = new $model();
                $col2 = $this->getColumn($mTable->getTable());

                $m = $model::all();
                $dataShow .= '<select name="' . $col . '" class="form-control">';
                $dataShow .= "<option value=''>----</option>";
                $dataShow .= '
                @foreach ($modelx = App\\Models\\' . $ent . '::all() as $e)
                   <option value="{{ $e->id }}"
                ';
                if ($edit) {
                    $dataShow .= '
                     @if($model->' . $col . ' == $e->id)  selected   @endif';
                }
                $dataShow .= '
                     >{{ $e->' . $col2 . ' }}</option>
                @endforeach;
                </select>';
            } else {
                if ($edit) {
                    $dataShow .= '
                <input type="text" name="' . $col . '" required  placeholder="' . $col . '"
                value="{{ $model->' . $col . '}}"
                    class="form-control" />';
                } else {
                    $dataShow .= '
                <input type="text" name="' . $col . '" required  placeholder="' . $col . '"
                value="{{ old("' . $col . '")}}"
                    class="form-control" />';
                }
            }

            $dataShow .= '
            </div>';
        }
    }
}
