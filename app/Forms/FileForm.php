<?php

namespace App\Forms;

use App\File;
use App\Helpers\FileAnalysisHelper;
use App\Helpers\HashHelper;
use App\SuppressionList;
use App\User;
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
            $classModePrefix    = 'file-mode file-mode-';
            $classChoiceWrapper = config('laravel-form-builder.defaults.choice.choice_options.wrapper_class');
            $classCheckWrapper  = config('laravel-form-builder.defaults.checkbox.wrapper_class');

            $user = $file->user_id ? $file->user()->getRelated()->first() : null;

            // Get user's own suppression lists as options to scrub against.
            if ($user) {
                $lists = $user->lists()->getRelated()->all();
                if ($lists) {
                    foreach ($lists as $list) {
                        $ownedListOptions[$list->id] = $list->name ?? $list->id;
                        if ($list->required) {
                            $requiredLists[$list->id] = true;
                        }
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

            // $this->add('static_action', Field::STATIC, [
            //     'tag'        => 'h5',
            //     'label_show' => false,
            //     'value'      => __('Settings'),
            // ]);

            /** @var User $user */
            $modeChoices                  = [];
            $modeChoices[File::MODE_HASH] = __('Hash');
            if ($ownedListOptions || $globalListOptions) {
                $modeChoices[File::MODE_SCRUB] = __('Scrub');
            }
            if ($user) {
                $modeChoices[File::MODE_LIST_CREATE] = __('New list');
                if ($ownedListOptions) {
                    $modeChoices[File::MODE_LIST_APPEND]  = __('Append list');
                    $modeChoices[File::MODE_LIST_REPLACE] = __('Replace list');
                }
            }
            $this->add('mode', Field::CHOICE, [
                'rules'         => 'required',
                'label'         => __('Mode'),
                'label_show'    => true,
                'choices'       => $modeChoices,
                'selected'      => $file->mode ?? File::MODE_HASH,
                'default_value' => File::MODE_HASH,
                // 'attr'          => [
                //     'class' => 'btn btn-primary',
                // ],
                // 'wrapper'       => [
                //     'class'       => 'btn-group btn-group-toggle',
                //     'data-toggle' => 'buttons',
                // ],
                'wrapper'       => [
                    'class' => $classChoiceWrapper,
                ],
            ]);

            if (!$user) {
                $this->add('static_login', Field::STATIC, [
                    'tag'        => 'a',
                    'label_show' => false,
                    'value'      => __('Login for more options'),
                    'attr'       => [
                        'href'  => route('login'),
                        'class' => 'btn btn-secondary btn-sm ml-4 mt-3',
                    ],
                    'wrapper'    => [
                        'class' => '',
                    ],
                ]);
            }

            if ($ownedListOptions) {
                $this->add('suppression_list_append', Field::CHOICE, [
                    'label'      => __('List to Append'),
                    'label_show' => true,
                    'choices'    => $ownedListOptions,
                    'wrapper'    => [
                        'class' => $classChoiceWrapper.' '.$classModePrefix.File::MODE_LIST_APPEND,
                    ],
                ]);
                $this->add('suppression_list_replace', Field::CHOICE, [
                    'label'      => __('List to Replace'),
                    'label_show' => true,
                    'choices'    => $ownedListOptions,
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
                        'class' => $classCheckWrapper.' ml-4 '.$classModePrefix.File::MODE_SCRUB,
                    ];
                    if (count($allListOptions) <= 5) {
                        $options['checked'] = 'checked';
                    }
                    if (isset($requiredLists[$listId]) || count($allListOptions) === 1) {
                        $options['required']         = true;
                        $options['attr']['disabled'] = 'disabled';
                        $options['checked']          = 'checked';
                    }
                    $this->add('suppression_list_use_'.$listId, Field::CHECKBOX, $options);
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
                $hashHelper    = new HashHelper();
                $hashOptions   = [null => __('Plain Text')] + $hashHelper->listChoices();
                $hiddenColumns = 0;
                foreach ($file->columns as $columnIndex => $column) {
                    $label             = $this->columnName($column['name'], $columnIndex);
                    $column['samples'] = array_filter($column['samples'] ?? [__('None')]);
                    $column['filled']  = $column['filled'] ?? false;
                    if (empty($column['filled'])) {
                        $classChoiceWrapper .= ' column-empty';
                    } else {
                        $classChoiceWrapper .= ' column-filled';
                    }
                    array_walk($column['samples'], 'strip_tags');
                    if (!$column['filled']) {
                        $hiddenColumns++;
                    }
                    $this->add('column_type_'.$columnIndex, Field::CHOICE, [
                        'label'         => $label,
                        'label_show'    => true,
                        'choices'       => [
                            FileAnalysisHelper::TYPE_EMAIL => __('Email Address'),
                            FileAnalysisHelper::TYPE_PHONE => __('Phone Number'),
                            null                           => __('Other'),
                        ],
                        'selected'      => $column['type'] ?? null,
                        'default_value' => null,
                        'label_attr'    => [
                            'data-toggle'         => 'tooltip',
                            'data-placement'      => 'right',
                            'data-original-title' => '<strong>'.__('Samples').':</strong><br/>'.
                                implode('<br/>', $column['samples']),
                        ],
                        'wrapper'       => [
                            'class' => $classChoiceWrapper,
                        ],
                    ]);

                    // Do not give hash input options if no hash was detected.
                    if (!empty($column['hash'])) {

                        // @todo - Hide these if the field type is unknown/other.
                        $this->add('column_hash_input_'.$columnIndex, Field::CHOICE, [
                            'label'         => __('Hash Provided'),
                            'label_show'    => true,
                            'choices'       => $hashOptions,
                            'selected'      => $column['hash'] ?? null,
                            'default_value' => null,
                            'wrapper'       => [
                                'class' => $classChoiceWrapper,
                            ],
                        ]);
                    }

                    $this->add('column_hash_output_'.$columnIndex, Field::CHOICE, [
                        'label'         => __('Hash to Generate'),
                        'label_show'    => true,
                        'choices'       => $hashOptions,
                        'selected'      => $column['hash'] ?? null,
                        'default_value' => null,
                        'wrapper'       => [
                            'class' => $classChoiceWrapper.' '.$classModePrefix.File::MODE_HASH,
                        ],
                    ]);
                }
                if (count($file->columns) && $hiddenColumns) {
                    $this->add('show_all', Field::CHECKBOX, [
                        'label'   => __('Show other columns').' ('.$hiddenColumns.')',
                        'wrapper' => [
                            'class' => $classCheckWrapper.' pull-left mt-5',
                        ],
                    ]);
                }
            }

            $this->add('submit', Field::BUTTON_SUBMIT, [
                'label' => '<i class="fa fa-check"></i> '.__('Begin'),
                'attr'  => [
                    'class' => 'btn btn-info pull-right mb-3 mt-4',
                ],
            ]);

            // Placeholder for now:
            // $this->add('file_'.$file->id.'_progress', Field::STATIC, [
            //     'tag'        => 'span',
            //     'label_show' => false,
            //     'value'      => '',
            // ]);
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
    }
}
