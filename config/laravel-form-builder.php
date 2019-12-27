<?php
return [
    'defaults'          => [
        'wrapper_class'       => 'form-inline mb-3 row',
        'wrapper_error_class' => '',
        'label_class'         => 'col-md-2',
        'field_class'         => 'form-control col-md-9',
        'field_error_class'   => 'is-invalid',
        'help_block_class'    => 'form-text text-muted m-3',
        'error_class'         => 'invalid-feedback d-block text-danger ml-4 mt-2 mb-4',
        'required_class'      => 'required',
        'static'              => [
            'field_class' => 'form-control-plaintext',
        ],
        'checkbox'            => [
            'wrapper_class' => 'custom-control custom-switch',
            'field_class'   => 'custom-control-input',
            'label_class'   => 'custom-control-label',
        ],
        // Custom classes used for single selects. Multiples require custom classes when used.
        'choice'              => [
            'choice_options' => [
                'label_class'   => 'col-md-2',
                'wrapper_class' => 'custom-control mb-3 row',
            ],
        ],
        'radio'               => [
            'wrapper_class'  => 'form-check',
            'field_class'    => 'form-check-input',
            'label_class'    => 'form-check-label',
            'choice_options' => [
                'wrapper_class' => 'custom-control custom-radio',
                'label_class'   => 'custom-control-label',
                'field_class'   => 'custom-control-input',
            ],
        ],
        'submit'              => [
            'wrapper_class' => 'form-group',
            'field_class'   => 'btn btn-primary',
        ],
        'reset'               => [
            'wrapper_class' => 'form-group',
            'field_class'   => 'btn btn-primary',
        ],
    ],
    // Templates
    'form'              => 'laravel-form-builder::form',
    'text'              => 'laravel-form-builder::text',
    'textarea'          => 'laravel-form-builder::textarea',
    'button'            => 'laravel-form-builder::button',
    'buttongroup'       => 'laravel-form-builder::buttongroup',
    'radio'             => 'laravel-form-builder::radio',
    'checkbox'          => 'laravel-form-builder::checkbox',
    'select'            => 'laravel-form-builder::select',
    'choice'            => 'laravel-form-builder::choice',
    'repeated'          => 'laravel-form-builder::repeated',
    'child_form'        => 'laravel-form-builder::child_form',
    'collection'        => 'laravel-form-builder::collection',
    'static'            => 'laravel-form-builder::static',
    // Remove the laravel-form-builder:: prefix above when using template_prefix
    'template_prefix'   => '',
    'default_namespace' => '',
    'custom_fields'     => [
        //
    ],
];
