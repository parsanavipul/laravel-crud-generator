<?php

namespace Ibex\CrudGenerator\Commands;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\select;

/**
 * Class CrudGenerator.
 *
 * @author  Awais <asargodha@gmail.com>
 */
class CrudGenerator extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud
                            {name : Table name}                            
                            {--stack= : The development stack that should be installed (bootstrap,tailwind,livewire)}                           
                            {--generateValidation= : create validation request (yes or no)}                           
                            {--route= : Custom route name}
                            {--api= : Generate API (yes or no)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Laravel CRUD operations';

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $this->info('Running Crud Generator ...');

        $this->table = $this->getNameInput();

        // If table not exist in DB return
        if (!$this->tableExists()) {
            $this->error("`$this->table` table not exist");

            return false;
        }


        $this->getRlationships();
        $this->getRlationshipsTables();
        // Build the class name from table name
        $this->name = $this->_buildClassName();

        // Generate the crud
        $this->buildOptions()
            ->buildController()
            ->buildModel()
            ->buildViews()
            ->writeRoute();

        $this->info('Created Successfully.');

        return true;
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            // 'stack' => fn () => select(
            //     label: 'Which stack would you like to install?',
            //     options: [
            //         'bootstrap' => 'Blade with Bootstrap css',
            //         'tailwind' => 'Blade with Tailwind css',
            //         'livewire' => 'Livewire with Tailwind css',
            //         'api' => 'API only',
            //     ],
            //     scroll: 4,
            // ),

            // 'generateValidation' => fn () => select(
            //     label: 'Would you like to generate validation requests?',
            //     options: [
            //         'yes' => 'Yes, Create validation request class',
            //         'no' => 'No, Don\'t create validation calss',
            //     ],
            //     scroll: 5,
            // ),
        ];
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        // $this->options['stack'] = match ($input->getArgument('stack')) {
        //     'tailwind' => 'tailwind',
        //     'livewire' => 'livewire',
        //     'react' => 'react',
        //     'vue' => 'vue',
        //     default => $this->defaultStack,
        // };
    }

    protected function writeRoute(): static
    {
        $replacements = $this->buildReplacements();

        $this->info('Please add route below: i:e; web.php or api.php');

        $this->info('');

        $lines = [];

        if ($this->commandOptions['stack'] == "livewire") {
            $lines =  [
                "Route::get('/{$this->_getRoute()}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Index::class)->name('{$this->_getRoute()}.index');",
                "Route::get('/{$this->_getRoute()}/create', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Create::class)->name('{$this->_getRoute()}.create');",
                "Route::get('/{$this->_getRoute()}/show/{{$replacements['{{modelNameLowerCase}}']}}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Show::class)->name('{$this->_getRoute()}.show');",
                "Route::get('/{$this->_getRoute()}/update/{{$replacements['{{modelNameLowerCase}}']}}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Edit::class)->name('{$this->_getRoute()}.edit');",
            ];
        } else if ($this->commandOptions['api'] == "yes") {
            $lines =  [
                "Route::apiResource('" . $this->_getRoute() . "', {$this->name}Controller::class);",
                "Route::resource('" . $this->_getRoute() . "', {$this->name}Controller::class);",
            ];
        } else {
            $lines =  [
                "Route::resource('" . $this->_getRoute() . "', {$this->name}Controller::class);",
            ];
        }

        /*$lines = match ($this->commandOptions['stack']) {
            'livewire' => [
                "Route::get('/{$this->_getRoute()}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Index::class)->name('{$this->_getRoute()}.index');",
                "Route::get('/{$this->_getRoute()}/create', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Create::class)->name('{$this->_getRoute()}.create');",
                "Route::get('/{$this->_getRoute()}/show/{{$replacements['{{modelNameLowerCase}}']}}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Show::class)->name('{$this->_getRoute()}.show');",
                "Route::get('/{$this->_getRoute()}/update/{{$replacements['{{modelNameLowerCase}}']}}', \\$this->livewireNamespace\\{$replacements['{{modelNamePluralUpperCase}}']}\Edit::class)->name('{$this->_getRoute()}.edit');",
            ],
            'api' => [
                "Route::apiResource('" . $this->_getRoute() . "', {$this->name}Controller::class);",
            ],
            default => [
                "Route::resource('" . $this->_getRoute() . "', {$this->name}Controller::class);",
            ]
        };*/

        foreach ($lines as $line) {
            $this->info('<bg=blue;fg=white>' . $line . '</>');
        }

        $this->info('');

        return $this;
    }

    /**
     * Build the Controller Class and save in app/Http/Controllers.
     *
     * @return $this
     * @throws FileNotFoundException
     */
    protected function buildController(): static
    {
        if ($this->commandOptions['stack'] == 'livewire') {
            $this->buildLivewire();

            return $this;
        }

        echo "\n...stack..." . $this->commandOptions['stack'];
        echo "\n...generate validation..." . $this->commandOptions['generateValidation'];
        echo "\n...generate API..." . $this->commandOptions['api'];

        $apiControllerPath = $this->_getApiControllerPath($this->name);
        $apistubFolder =  'api/';

        $controllerPath = $this->_getControllerPath($this->name);

        if ($this->files->exists($controllerPath) && $this->ask('Already exist Controller. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Controller ...');

        $replace = $this->buildReplacements();

        $stubFolder =  '';

        $modelData = "";
        $modelVariables = "";
        foreach ($this->relationsModelsArray as $modelName) {
            $modelName = trim(str_replace("$", "", trim($modelName)));
            $modelVariables .= "'" . Str::camel($modelName) . "',";
            $modelData .= "\n " . "$" . Str::camel($modelName) . "=" . str_replace("$", "", trim($modelName)) . "::get();";
        }
        $modelVariables = rtrim($modelVariables, ',');




        $relationsLoad = array(
            '{{relationsCompact}}' => $modelVariables,
            '{{relationsData}}' => $modelData,
        );
        $replace = array_merge($relationsLoad, $this->buildReplacements());


        if ($this->commandOptions['api'] == 'yes') {


            if ($this->commandOptions['generateValidation'] == "yes") {
                /* use validation controller request stub */
                $controllerTemplate = str_replace(
                    array_keys($replace),
                    array_values($replace),
                    $this->getStub($apistubFolder . 'ControllerValidate')
                );
                $this->write($apiControllerPath, $controllerTemplate);
            } else {
                /* use validation controller  stub */

                $controllerTemplate = str_replace(
                    array_keys($replace),
                    array_values($replace),
                    $this->getStub($apistubFolder . 'Controller')
                );
                $this->write($apiControllerPath, $controllerTemplate);
            }


            $resourcePath = $this->_getResourcePath($this->name);
            $resourceTemplate = str_replace(
                array_keys($replace),
                array_values($replace),
                $this->getStub($apistubFolder . 'Resource')
            );
            $this->write($resourcePath, $resourceTemplate);
        }


        if ($this->commandOptions['generateValidation'] == "yes") {

            /* use validation controller request stub */
            $controllerTemplate = str_replace(
                array_keys($replace),
                array_values($replace),
                $this->getStub($stubFolder . 'ControllerValidate')
            );
            $this->write($controllerPath, $controllerTemplate);


            // Make Request Class
            $replace = array_merge($this->buildReplacements(), $this->modelReplacements());
            $requestPath = $this->_getRequestPath($this->name);
            $this->info('Creating Request Class ...');

            $requestTemplate = str_replace(
                array_keys($replace),
                array_values($replace),
                $this->getStub('Request')
            );

            $this->write($requestPath, $requestTemplate);
        } else {
            /* use validation controller  stub */
            $controllerTemplate = str_replace(
                array_keys($replace),
                array_values($replace),
                $this->getStub($stubFolder . 'Controller')
            );
            $this->write($controllerPath, $controllerTemplate);
        }



        return $this;
    }

    protected function buildLivewire(): void
    {
        $this->info('Creating Livewire Component ...');

        $folder = ucfirst(Str::plural($this->name));
        $replace = array_merge($this->buildReplacements(), $this->modelReplacements());

        foreach (['Index', 'Show', 'Edit', 'Create'] as $component) {
            $componentPath = $this->_getLivewirePath($folder . '/' . $component);

            $componentTemplate = str_replace(
                array_keys($replace),
                array_values($replace),
                $this->getStub('livewire/' . $component)
            );

            $this->write($componentPath, $componentTemplate);
        }

        // Form
        $formPath = $this->_getLivewirePath('Forms/' . $this->name . 'Form');

        $componentTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('livewire/Form')
        );

        $this->write($formPath, $componentTemplate);
    }

    /**
     * @return $this
     * @throws FileNotFoundException
     *
     */
    protected function buildModel(): static
    {
        $modelPath = $this->_getModelPath($this->name);

        if ($this->files->exists($modelPath) && $this->ask('Already exist Model. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Model ...');

        // Make the models attributes and replacement
        $replace = array_merge($this->buildReplacements(), $this->modelReplacements());

        $modelTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('Model')
        );

        $this->write($modelPath, $modelTemplate);



        return $this;
    }

    /**
     * @return $this
     * @throws FileNotFoundException
     *
     * @throws Exception
     */
    protected function buildViews(): static
    {
        if ($this->commandOptions['stack'] == 'api') {
            return $this;
        }

        $this->info('Creating Views ...');

        $tableHead = "\n";
        $tableBody = "\n";
        $viewRows = "\n";
        $form = "\n";

        foreach ($this->getFilteredColumns() as $column) {
            $title = Str::title(str_replace('_', ' ', $column));

            $tableHead .= $this->getHead($title);
            $tableBody .= $this->getBody($column);
            $viewRows .= $this->getField($title, $column, 'view-field');
            $form .= $this->getField($title, $column);
        }

        $replace = array_merge($this->buildReplacements(), [
            '{{tableHeader}}' => $tableHead,
            '{{tableBody}}' => $tableBody,
            '{{viewRows}}' => $viewRows,
            '{{form}}' => $form,
        ]);

        $this->buildLayout();

        foreach (['index', 'create', 'edit', 'form', 'show'] as $view) {
            $viewTemplate = str_replace(
                array_keys($replace),
                array_values($replace),
                $this->getStub("views/{$this->commandOptions['stack']}/$view")
            );

            $this->write($this->_getViewPath($view), $viewTemplate);
        }

        return $this;
    }

    /**
     * Make the class name from table name.
     *
     * @return string
     */
    private function _buildClassName(): string
    {
        return Str::studly(Str::singular($this->table));
    }
}
