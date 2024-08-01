<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stubs Path
    |--------------------------------------------------------------------------
    |
    | The stubs path directory to generate crud. You may configure your
    | stubs paths here, allowing you to customize the own stubs of the
    | model,controller or view. Or, you may simply stick with the CrudGenerator defaults!
    |
    | Example: 'stub_path' => resource_path('stubs/')
    | Default: "default"
    | Files:
    |       Controller.stub
    |       Model.stub
    |       Request.stub
    |       views/
    |           bootstrap/
    |               create.stub
    |               edit.stub
    |               form.stub
    |               form-field.stub
    |               index.stub
    |               show.stub
    |               view-field.stub
    */

    'stub_path' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Application Layout
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application layout. This value is used when creating
    | views for crud. Default will be the "layouts.app".
    |
    */

    'layout' => 'layouts.app',

    /* can be yes or no */
    'createLayout' => 'no',

    'model' => [
        'namespace' => 'App\Models',

        /*
         * Do not make these columns $fillable in Model or views
         */
        'unwantedColumns' => [
            'id',
            'uuid',
            'ulid',
            'password',
            'email_verified_at',
            'remember_token',
            'created_at',
            'updated_at',
            'deleted_at',
            'created_by',
            'updated_by',
            'deleted_by',
        ],
    ],

    'controller' => [
        'namespace' => 'App\Http\Controllers',
        'apiNamespace' => 'App\Http\Controllers\Api',
    ],

    /* can be tailwind,livewire,react,vue or bootstrap */
    'defaultStack' => 'bootstrap',

    /* can be yes or no */
    'generateAPI' => 'no',

    /* can be yes or no */
    'generateValidation' => 'yes',


    'eagerRelationships' => [
        /* can be false,yes or all */
        'load' => 'yes',
        /* can be yes or no if need to add relationship data with compact into view */
        'addRelationshipDataInView' => 'yes'
    ],

    'resources' => [
        'namespace' => 'App\Http\Resources',
    ],

    'livewire' => [
        'namespace' => 'App\Livewire',
    ],

    'request' => [
        'namespace' => 'App\Http\Requests',
    ],
];
