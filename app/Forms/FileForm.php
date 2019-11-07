<?php

namespace App\Forms;

use App\File;
use App\Helpers\FileAnalysisHelper;
use App\Helpers\HashHelper;
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
            $this->formOptions = [
                'method' => 'POST',
                'url'    => route('file.store', ['id' => $file->id]),
            ];

            $this->add('static_action', Field::STATIC, [
                'tag'        => 'h5',
                'label_show' => false,
                'value'      => __('File Action'),
            ]);

            $this->add('mode', Field::CHOICE, [
                'rules'         => 'required',
                'label'         => __('Action'),
                'label_show'    => false,
                'choices'       => [
                    File::MODE_HASH        => __('Hash'),
                    File::MODE_LIST_CREATE => __('New suppression list'),
                    // @todo - Add these when functionality is done.
                    // File::MODE_LIST_APPEND  => __('Add to an existing list'),
                    // File::MODE_LIST_REPLACE => __('Replace a suppression list'),
                    File::MODE_SCRUB       => __('Scrub'),
                ],
                'attr'          => [
                    'class' => 'form-control col-md-3',
                ],
                'selected'      => $file->mode ?? File::MODE_HASH,
                'default_value' => File::MODE_HASH,
                'expanded'      => false,
            ]);

            // @todo - Add select for suppression lists to replace/append.

            // @todo - Add checkboxes for global and custom suppression lists to scrub against.
            // $lists = SuppressionList::all();
            // $this->add('list_id_append', Field::CHOICE, [
            //     'rules'      => 'required',
            //     'label'      => __('List to Append'),
            //     'label_show' => true,
            //     'choices'    => [
            //
            //     ],
            //     'attr'       => [
            //         'class' => 'form-control col-md-3',
            //     ],
            //     // 'selected'      => $file->mode ?? File::MODE_HASH,
            //     // 'default_value' => File::MODE_HASH,
            //     'expanded'   => true,
            // ]);

            if ($file->columns) {
                $this->add('static_columns', Field::STATIC, [
                    'tag'        => 'h5',
                    'label_show' => false,
                    'value'      => __('Column Types'),
                ]);
                $hashHelper  = new HashHelper();
                $hashOptions = [null => __('Plain Text')] + $hashHelper->listChoices();
                foreach ($file->columns as $columnIndex => $column) {
                    $label = $this->columnName($column['name'], $columnIndex);
                    array_walk($column['samples'], 'strip_tags');
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
                        'attr'          => [
                            'class' => 'form-control col-md-3 pull-right '.(empty($column['filled']) ? 'filled' : 'empty'),
                        ],
                        'label_attr'    => [
                            'data-toggle'         => 'tooltip',
                            'data-placement'      => 'right',
                            'data-original-title' => '<strong>'.__('Samples').':</strong><br/><br/>'.
                                implode('<br/>', $column['samples']),
                        ],
                    ]);

                    // Do not give hash input options if no hash was detected.
                    if (!empty($column['hash'])) {
                        // @todo - Only show this if the above is a known type.
                        $this->add('column_hash_input_'.$columnIndex, Field::CHOICE, [
                            'label'         => $label.' '.__('Hash Used'),
                            'label_show'    => true,
                            'choices'       => $hashOptions,
                            'selected'      => $column['hash'] ?? null,
                            'default_value' => null,
                            'attr'          => [
                                'class' => 'form-control col-md-3 pull-right '.(empty($column['filled']) ? 'filled' : 'empty'),
                            ],
                            'wrapper'       => [
                                'class' => 'form-group '.($column['type'] ? '' : ' invisible'),
                            ],
                        ]);
                    }

                    // @todo - Only show this if Plain Text is selected above or there is no hash, and the field type is defined as phone or email.
                    $this->add('column_hash_output_'.$columnIndex, Field::CHOICE, [
                        'label'         => $label.' '.__('Output Hash'),
                        'label_show'    => true,
                        'choices'       => $hashOptions,
                        'selected'      => $column['hash'] ?? null,
                        'default_value' => null,
                        'attr'          => [
                            'class' => 'form-control col-md-3 pull-right ml-4 '.(empty($column['filled']) ? 'filled' : 'empty'),
                        ],
                        'wrapper'       => [
                            'class' => 'form-group',
                        ],
                    ]);
                }
            }

            $this->add('submit', Field::BUTTON_SUBMIT, [
                'label' => '<i class="fa fa-check"></i> '.__('Begin'),
                'attr'  => [
                    'class' => 'btn btn-info pull-right mb-3',
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
