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
            $user = $file->user()->getRelated()->first();

            $this->formOptions = [
                'method' => 'POST',
                'url'    => route('file.store', ['id' => $file->id]),
            ];

            $this->add('static_action', Field::STATIC, [
                'tag'        => 'h5',
                'label_show' => false,
                'value'      => __('File Action'),
            ]);

            // @todo - Add select for suppression lists to replace/append.
            /** @var User $user */
            $listChoices                     = [];
            $actionChoices                   = [];
            $actionChoices[File::MODE_HASH]  = __('Hash');
            $actionChoices[File::MODE_SCRUB] = __('Scrub');
            if ($user) {
                $actionChoices[File::MODE_LIST_CREATE] = __('New suppression list');
                // @todo - Add these when functionality is done.
                // $actionChoices[File::MODE_LIST_APPEND]  = __('Add to an existing list');
                // $actionChoices[File::MODE_LIST_REPLACE] = __('Replace a suppression list');
            }
            $this->add('mode', Field::CHOICE, [
                'rules'         => 'required',
                'label'         => __('Action'),
                'label_show'    => false,
                'choices'       => $actionChoices,
                'selected'      => $file->mode ?? File::MODE_HASH,
                'default_value' => File::MODE_HASH,
            ]);

            // Get user's own suppression lists as options to scrub against.
            if ($user) {
                $lists = $user->lists()->getRelated()->all();
                if ($lists) {
                    // @todo - Provide a selection option for the user's current self-owned suppression lists.
                    foreach ($lists as $list) {
                        $listChoices[$list->id] = $list->name ?? $list->id;
                    }
                }
            }
            asort($listChoices);

            $this->add('suppression_list_append', Field::CHOICE, [
                'label'      => __('List to Append'),
                'label_show' => true,
                'choices'    => $listChoices,
            ]);

            // Add global suppression list options for scrubbing.
            foreach (SuppressionList::withoutTrashed()->where('global', true)->get() as $list) {
                $listChoices[$list->id] = $list->name ?? $list->id;
            }
            asort($listChoices);

            if ($listChoices) {
                $this->add('suppression_list_use', Field::CHOICE, [
                    'label'      => __('List/s to use for scrubbing'),
                    'label_show' => true,
                    'choices'    => $listChoices,
                    'multiple'   => true,
                    'expanded'   => true,
                ]);
            }

            if ($file->columns) {
                $this->add('static_columns', Field::STATIC, [
                    'tag'        => 'h5',
                    'label_show' => false,
                    'value'      => __('Column Types'),
                ]);
                $hashHelper    = new HashHelper();
                $hashOptions   = [null => __('Plain Text')] + $hashHelper->listChoices();
                $hiddenColumns = 0;
                foreach ($file->columns as $columnIndex => $column) {
                    $column['filled']  = $column['filled'] ?? false;
                    $class             = !empty($column['filled']) ? 'column-filled' : 'column-empty';
                    $label             = $this->columnName($column['name'], $columnIndex);
                    $column['samples'] = $column['samples'] ?? [__('None')];
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
                        // 'attr'          => [
                        //     'class' => 'form-control col-md-3 pull-right '.(empty($column['filled']) ? 'filled' : 'empty'),
                        // ],
                        'label_attr'    => [
                            'data-toggle'         => 'tooltip',
                            'data-placement'      => 'right',
                            'data-original-title' => '<strong>'.__('Samples').':</strong><br/><br/>'.
                                implode('<br/>', $column['samples']),
                        ],
                        'wrapper'       => [
                            'class' => 'form-group '.$class,
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
                            // 'attr'          => [
                            //     'class' => 'form-control col-md-3 pull-right '.(empty($column['filled']) ? 'filled' : 'empty'),
                            // ],
                            'wrapper'       => [
                                'class' => 'form-group '.($column['type'] ? '' : ' d-none').' '.$class,
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
                        // 'attr'          => [
                        //     'class' => 'form-control col-md-3 pull-right ml-4 '.(empty($column['filled']) ? 'filled' : 'empty'),
                        // ],
                        'wrapper'       => [
                            'class' => 'form-group '.$class,
                        ],
                    ]);
                }
                if (count($file->columns) && $hiddenColumns) {
                    $this->add('show_all', Field::CHECKBOX, [
                        'label' => __('Show other columns').' ('.$hiddenColumns.')',
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
