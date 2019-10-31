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

        if ($file->status & File::STATUS_INPUT_NEEDED) {
            $this->add('file_'.$file->id.'_action', Field::STATIC, [
                'tag'        => 'h5',
                'label_show' => false,
                'value'      => __('File Action'),
            ]);
            $this->add('file_'.$file->id.'_mode', Field::CHOICE, [
                'rules'         => 'required',
                'label'         => __('Action'),
                'label_show'    => false,
                'choices'       => [
                    File::MODE_HASH         => __('Hash'),
                    File::MODE_LIST_CREATE  => __('New suppression list'),
                    File::MODE_LIST_APPEND  => __('Add to an existing list'),
                    File::MODE_LIST_REPLACE => __('Replace a suppression list'),
                    File::MODE_SCRUB        => __('Scrub'),
                ],
                'attr'          => [
                    'class' => 'form-control col-md-5',
                ],
                'selected'      => $file->mode ?? File::MODE_HASH,
                'default_value' => File::MODE_HASH,
                'expanded'      => false,
            ]);

            if ($file->columns) {
                $this->add('file_'.$file->id.'_columns', Field::STATIC, [
                    'tag'        => 'h5',
                    'label_show' => false,
                    'value'      => __('Column Types'),
                ]);
                $hashHelper  = new HashHelper();
                $hashOptions = [null => __('No Hash')] + $hashHelper->listChoices();
                foreach ($file->columns as $columnIndex => $column) {
                    $label = $this->columnName($column['name'], $columnIndex);
                    array_walk($column['samples'], 'strip_tags');
                    $fileType = $column['type'] ?? FileAnalysisHelper::TYPE_UNKNOWN;
                    $this->add('file_'.$file->id.'_column_type_'.$columnIndex, Field::CHOICE, [
                        'label'         => $label,
                        'label_show'    => true,
                        'choices'       => [
                            FileAnalysisHelper::TYPE_EMAIL   => __('Email Address'),
                            FileAnalysisHelper::TYPE_PHONE   => __('Phone Number'),
                            FileAnalysisHelper::TYPE_UNKNOWN => __('Other'),
                        ],
                        'selected'      => $column['type'] ?? FileAnalysisHelper::TYPE_UNKNOWN,
                        'default_value' => FileAnalysisHelper::TYPE_UNKNOWN,
                        'attr'          => [
                            'class' => 'form-control col-md-5',
                        ],
                        // @todo - Expand/collapse this to the side, otherwise it gets in the way.
                        // 'help_block'     => [
                        //     'text' => '<strong>'.__('Samples:').'</strong><br/>'.implode('<br/>', $column['samples']),
                        // ],
                    ]);

                    // @todo - Only show this if the above is a known type.
                    $this->add('file_'.$file->id.'_column_hash_'.$columnIndex, Field::CHOICE, [
                        'label'         => $label.' '.__('Hash Used'),
                        'label_show'    => false,
                        'choices'       => $hashOptions,
                        'selected'      => $column['hash'] ?? null,
                        'default_value' => null,
                        'attr'          => [
                            'class' => 'form-control col-md-5',
                        ],
                        'wrapper'       => [
                            'class' => 'form-group'.($fileType & FileAnalysisHelper::TYPE_UNKNOWN ? ' invisible' : ''),
                        ],
                        // 'help_block'    => [
                        //     'text' => __('Samples:').'<br/>'.implode('<br/>', $column['samples']),
                        // ],
                    ]);
                }
            }

            // $this->add('input_settings', Field::TEXTAREA);

            $this->add('submit', Field::BUTTON_SUBMIT, [
                'label' => __('<i class="fa fa-check"></i> '.'Begin'),
                'attr'  => [
                    'class' => 'btn btn-primary pull-right',
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
}
