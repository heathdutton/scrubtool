<?php

namespace App\Forms;

use App\Helpers\ActionDefaults;
use App\Helpers\FileSuppressionListHelper;
use App\Helpers\HashHelper;
use App\Models\File;
use App\Models\SuppressionList;
use Kris\LaravelFormBuilder\Field;
use Kris\LaravelFormBuilder\Form;

/**
 * Class FileForm
 *
 * @package App\Forms
 */
class FileForm extends Form
{
    /**
     * @return mixed|void
     */
    public function buildForm()
    {
        /** @var File $file */
        $file = $this->getData('file');

        if (!$file || !empty($file->deleted_at)) {
            return;
        }

        if ($file->status & File::STATUS_INPUT_NEEDED) {
            $ownedListOptions   = [];
            $globalListOptions  = [];
            $requiredLists      = [];
            $classModePrefix    = 'file-mode file-mode-hidden file-mode-';
            $classChoiceField   = 'custom-control custom-select';
            $classChoiceWrapper = config('laravel-form-builder.defaults.choice.choice_options.wrapper_class');
            $classChoiceLabel   = config('laravel-form-builder.defaults.choice.choice_options.label_class');
            $classCheckWrapper  = config('laravel-form-builder.defaults.checkbox.wrapper_class');

            // Get user's own suppression lists as options to scrub against.
            if ($file->user && $file->user->suppressionLists) {
                foreach ($file->user->suppressionLists as $list) {
                    $ownedListOptions[$list->id] = $list->name ?? $list->id;
                    if ($list->required) {
                        $requiredLists[$list->id] = true;
                    }
                }
            }
            asort($ownedListOptions);

            // Add global suppression list options for scrubbing.
            foreach (SuppressionList::withoutTrashed()->where('global', true)->get() as $list) {
                $label = $list->name ?? $list->id;
                if ($list->required) {
                    $label                    .= ' ('.__('Required').')';
                    $requiredLists[$list->id] = true;
                }
                $globalListOptions[$list->id] = $label;
            }
            asort($globalListOptions);

            $this->formOptions = [
                'method' => 'POST',
                'url'    => route('file.store', ['id' => $file->id]),
            ];

            $modeOptions = [
                'rules'      => 'required',
                'label'      => __('I want to'),
                'label_attr' => [
                    'class' => $classChoiceLabel,
                ],
                'attr'       => [
                    'class' => $classChoiceField.' col-md-2',
                ],
                'label_show' => true,
                'choices'    => [],
                'selected'   => ActionDefaults::getDefault('mode') ?? $file->mode,
                'wrapper'    => [
                    'class' => $classChoiceWrapper,
                ],
            ];
            if ($ownedListOptions || $globalListOptions) {
                $modeOptions['choices'][File::MODE_SCRUB] = __('Scrub this file');
            }
            if ($file->user) {
                $modeOptions['choices'][File::MODE_LIST_CREATE] = __('Create a new list');
                if ($ownedListOptions) {
                    $modeOptions['choices'][File::MODE_LIST_APPEND]  = __('Append a list');
                    $modeOptions['choices'][File::MODE_LIST_REPLACE] = __('Replace a list');
                }
            }
            $modeOptions['choices'][File::MODE_HASH] = __('Hash this file');
            if (!$file->user) {
                $modeOptions['attr']['data-toggle']         = 'tooltip';
                $modeOptions['attr']['data-trigger']        = 'focus';
                $modeOptions['attr']['data-placement']      = 'right';
                $modeOptions['attr']['data-original-title'] = __(
                    'You can scrub or hash your file anonymously. '.
                    'To manage your own suppression lists<br/>'.
                    'please <a href=":login">Login</a> or <a href=":register">Register</a>.',
                    [
                        'login'    => route('login'),
                        'register' => route('register'),
                    ]
                );
            }
            $this->add('mode', Field::CHOICE, $modeOptions);

            if ($ownedListOptions) {
                $this->add('suppression_list_append', Field::CHOICE, [
                    'label'      => __('Append list'),
                    'label_attr' => [
                        'class' => $classChoiceLabel,
                    ],
                    'attr'       => [
                        'class' => $classChoiceField.' col-md-9',
                    ],
                    'label_show' => true,
                    'choices'    => $ownedListOptions,
                    'selected'   => ActionDefaults::getDefault('suppression_list_append'),
                    'wrapper'    => [
                        'class' => $classChoiceWrapper.' '.$classModePrefix.File::MODE_LIST_APPEND,
                    ],
                ]);
                $this->add('suppression_list_replace', Field::CHOICE, [
                    'label'      => __('Replace list'),
                    'label_attr' => [
                        'class' => $classChoiceLabel,
                    ],
                    'attr'       => [
                        'class' => $classChoiceField.' col-md-9',
                    ],
                    'label_show' => true,
                    'choices'    => $ownedListOptions,
                    'selected'   => ActionDefaults::getDefault('suppression_list_replace'),
                    'wrapper'    => [
                        'class' => $classChoiceWrapper.' '.$classModePrefix.File::MODE_LIST_REPLACE,
                    ],
                ]);
            }

            if ($ownedListOptions || $globalListOptions) {
                $this->add('static_suppression_list_use', Field::STATIC, [
                    'tag'        => 'label',
                    'label_show' => false,
                    'value'      => __('Scrub this file using:'),
                    'wrapper'    => [
                        'class' => 'ml-4 '.$classModePrefix.File::MODE_SCRUB,
                    ],
                ]);
                $allListOptions = $ownedListOptions + $globalListOptions;
                foreach ($allListOptions as $listId => $label) {
                    $options            = [];
                    $options['label']   = $label;
                    $options['value']   = $listId;
                    $options['attr']    = [];
                    $options['wrapper'] = [
                        'class' => $classCheckWrapper.' mb-2 ml-4 '.$classModePrefix.File::MODE_SCRUB,
                    ];
                    if (count($allListOptions) <= 5) {
                        $options['checked'] = 'checked';
                    }
                    $fieldName = 'suppression_list_use_'.$listId;
                    if (isset($requiredLists[$listId]) || count($allListOptions) === 1) {
                        // This will create a hidden input with the static value, and a disabled visible field.
                        $options['required'] = true;
                        $options['checked']  = 'checked';
                        $this->add($fieldName, Field::HIDDEN, $options);
                        $options['attr']['disabled'] = 'disabled';
                        $fieldName                   = 'suppression_list_use_disabled_'.$listId;
                    }
                    $this->add($fieldName, Field::CHECKBOX, $options);
                };
                // @todo - Auto select all link/button. Possibly handle a selection list better if there are MANY.
                // if (count($listChoices) > 5) {
                //     $this->add('static_suppression_list_all', Field::CHECKBOX, [
                //         'label'  => __('All of the Above'),
                //         'value'  => 1,
                //     ]);
                // }
                // @todo - Link/button for creating a new suppression list?
                // if (count($listChoices) > 5) {
                //     $this->add('static_suppression_list_all', Field::CHECKBOX, [
                //         'label'  => __('All of the Above'),
                //         'value'  => 1,
                //     ]);
                // }
            }

            if ($file->columns) {
                $this->add('static_columns', Field::STATIC, [
                    'tag'        => 'h5',
                    'label_show' => false,
                    'value'      => __('Columns'),
                    'wrapper'    => [
                        'class' => 'mt-3',
                    ],
                ]);
                $hashHelper        = new HashHelper();
                $hashOptionsIn     = [null => __('Is plain text')];
                $hashOptionsOut    = [null => __('Leave as-is')];
                $hiddenColumns     = 0;
                $columnTypes       = [];
                $columnTypes[null] = __('Other data');
                foreach (FileSuppressionListHelper::COLUMN_TYPES as $type) {
                    $columnTypes[$type] = __('column_types.plural.'.$type);
                }
                foreach ($hashHelper->listChoices() as $key => $value) {
                    $hashOptionsIn[$key]  = __('Is a :hash hash', ['hash' => $value]);
                    $hashOptionsOut[$key] = __('Convert to :hash hash', ['hash' => $value]);
                }
                foreach ($file->columns as $columnIndex => $column) {
                    $label             = $this->columnName($column['name'], $columnIndex);
                    $column['samples'] = array_filter($column['samples'] ?? [__('None')]);
                    $column['filled']  = $column['filled'] ?? false;
                    $class             = $classChoiceWrapper;
                    $columnName        = !empty($column['type']) && !empty($columnTypes[$column['type']]) ? $columnTypes[$column['type']] : __('data');
                    $columnIcon        = !empty($column['type']) ? '<i class="fa fa-'.__('column_types.icons.'.$column['type']).'"></i>&nbsp;' : '';
                    $hashName          = !empty($column['hash']) && isset($hashOptionsIn[$column['hash']['id']]) ? $hashOptionsIn[$column['hash']['id']] : __('Plaintext');
                    if (empty($column['filled'])) {
                        $class .= ' column-empty column-empty-hidden';
                    } else {
                        $class .= ' column-filled';
                    }
                    array_walk($column['samples'], 'strip_tags');
                    if (!$column['filled']) {
                        $hiddenColumns++;
                        $tooltip = __('Column appears to be empty.');
                    } else {
                        $tooltip = __(':hash :name detected.', [
                                'hash' => $hashName,
                                'name' => $columnIcon.' '.$columnName,
                            ]).'</br>'.
                            __('Samples').':<br/>&nbsp;&nbsp;'.
                            implode('<br/>&nbsp;&nbsp;', $column['samples']);
                    }
                    $this->add('column_type_'.$columnIndex, Field::CHOICE, [
                        'label'         => $label,
                        'label_show'    => true,
                        'choices'       => $columnTypes,
                        'selected'      => $column['type'] ?? null,
                        'default_value' => null,
                        'attr'          => [
                            'class'               => $classChoiceField.' col-md-3',
                            'data-toggle'         => 'tooltip',
                            'data-placement'      => 'right',
                            'data-original-title' => $tooltip,
                        ],
                        'label_attr'    => [
                            'class' => $classChoiceLabel,
                        ],
                        'wrapper'       => [
                            'class' => $class,
                        ],
                    ]);

                    // Do not give hash input options if no hash was detected.
                    if (!empty($column['hash'])) {

                        // @todo - Hide these if the field type is unknown/other.
                        $this->add('column_hash_input_'.$columnIndex, Field::CHOICE, [
                            'label'         => ' ',
                            'label_attr'    => [
                                'class' => $classChoiceLabel,
                            ],
                            'attr'          => [
                                'class' => $classChoiceField.' col-md-3',
                            ],
                            'label_show'    => true,
                            'choices'       => $hashOptionsIn,
                            'selected'      => $column['hash']['id'] ?? null,
                            'default_value' => null,
                            'wrapper'       => [
                                'class' => $class.' ml-4',
                            ],
                        ]);
                    }

                    $this->add('column_hash_output_'.$columnIndex, Field::CHOICE, [
                        'label'         => ' ',
                        'label_attr'    => [
                            'class' => $classChoiceLabel,
                        ],
                        'attr'          => [
                            'class' => $classChoiceField.' col-md-3',
                        ],
                        'choices'       => $hashOptionsOut,
                        'selected'      => null, // $column['hash']['id'] ?? null,
                        'default_value' => null,
                        'wrapper'       => [
                            'class' => $class.' '.$classModePrefix.File::MODE_HASH // .' ml-4',
                        ],
                    ]);
                }
                if (count($file->columns) && $hiddenColumns) {
                    $this->add('show_all', Field::CHECKBOX, [
                        'label'   => $hiddenColumns > 1 ? __('Show :count extra columns',
                            ['count' => $hiddenColumns]) : __('Show 1 extra column'),
                        'wrapper' => [
                            'class' => $classCheckWrapper.' col-md-3 pull-left mt-5',
                        ],
                    ]);
                }
            }

            $this->add('submit_'.File::MODE_HASH, Field::BUTTON_SUBMIT, [
                'label' => '<i class="fa fa-hashtag"></i> '.__('Hash File'),
                'attr'  => [
                    'class' => 'btn btn-info float-right mb-3 mt-4 '.$classModePrefix.File::MODE_HASH,
                ],
            ]);
            $this->add('submit_'.File::MODE_LIST_APPEND, Field::BUTTON_SUBMIT, [
                'label' => '<i class="fa fa-plus"></i> '.__('Append Suppression List'),
                'attr'  => [
                    'class' => 'btn btn-info float-right mb-3 mt-4 '.$classModePrefix.File::MODE_LIST_APPEND,
                ],
            ]);
            $this->add('submit_'.File::MODE_LIST_CREATE, Field::BUTTON_SUBMIT, [
                'label' => '<i class="fa fa-check"></i> '.__('Create Suppression List'),
                'attr'  => [
                    'class' => 'btn btn-info float-right mb-3 mt-4 '.$classModePrefix.File::MODE_LIST_CREATE,
                ],
            ]);
            $this->add('submit_'.File::MODE_LIST_REPLACE, Field::BUTTON_SUBMIT, [
                'label' => '<i class="fa fa-plus-square"></i> '.__('Replace Suppression List'),
                'attr'  => [
                    'class' => 'btn btn-warning float-right mb-3 mt-4 '.$classModePrefix.File::MODE_LIST_REPLACE,
                ],
            ]);
            $this->add('submit_'.File::MODE_SCRUB, Field::BUTTON_SUBMIT, [
                'label' => '<i class="fa fa-filter"></i> '.__('Scrub File'),
                'attr'  => [
                    'class' => 'btn btn-info float-right mb-3 mt-4 '.$classModePrefix.File::MODE_SCRUB,
                ],
            ]);
        }
    }

    private function columnName($columnName = null, $columnIndex = 0)
    {
        if (!empty(trim($columnName))) {
            return strip_tags($columnName);
        }
        // $columnIndex++;
        for ($r = ''; $columnIndex >= 0; $columnIndex = intval($columnIndex / 26) - 1) {
            $r = chr($columnIndex % 26 + 0x41).$r;
        }

        return __('Column '.$r);
    }


    /**
     * Optionally change the validation result, and/or add error messages.
     *
     * @param  Form  $mainForm
     * @param  bool  $isValid
     *
     * @return void|array
     */
    public function alterValid(Form $mainForm, &$isValid)
    {
        // @todo - Validation to ensure the user has rights to push to this list.
        // return ['list_id' => ['Some other error about the Name field.']];

        // @todo - Ensure that we don't mix hash types with email/phone fields.

        // @todo - Ensure the list to scrub against has coverage for the file provided.
    }
}
