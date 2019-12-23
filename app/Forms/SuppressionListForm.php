<?php

namespace App\Forms;

use App\Models\SuppressionList;
use Kris\LaravelFormBuilder\Field;
use Kris\LaravelFormBuilder\Form;

/**
 * Class FileForm
 *
 * @package App\Forms
 */
class SuppressionListForm extends Form
{

    /**
     * @return mixed|void
     */
    public function buildForm()
    {
        /** @var SuppressionList $suppressionList */
        $suppressionList = $this->getData('suppressionList');

        if (!$suppressionList || !empty($suppressionList->deleted_at)) {
            return;
        }

        $this->formOptions = [
            'method' => 'POST',
            'url'    => route('suppressionList.store', ['id' => $suppressionList->id]),
        ];

        $this->add('name', Field::TEXT, [
            'label'         => __('Name'),
            'label_show'    => true,
            'rules'         => 'required|min:3',
            'default_value' => $suppressionList->name,
        ]);

        $this->add('description', Field::TEXTAREA, [
            'label'         => __('Description'),
            'label_show'    => true,
            'rules'         => 'max:10000',
            'default_value' => $suppressionList->description,
            'attr'          => [
                'data-toggle'         => 'tooltip',
                'data-placement'      => 'bottom',
                'data-original-title' => __('The description and name will be visible to any that you share the suppression list with.'),
            ],
        ]);

        $this->add('private', Field::CHECKBOX, [
            'label'         => __('Private'),
            'rules'         => 'boolean',
            'default_value' => (bool) $suppressionList->private,
            'label_attr'    => [
                'data-toggle'         => 'tooltip',
                'data-placement'      => 'right',
                'data-original-title' => __('Disables shared links so that others cannot us this suppression list.'),
            ],
        ]);

        $this->add('required', Field::CHECKBOX, [
            'label'         => __('Required'),
            'rules'         => 'boolean',
            'default_value' => (bool) $suppressionList->required,
            'label_attr'    => [
                'data-toggle'         => 'tooltip',
                'data-placement'      => 'right',
                'data-original-title' => __('Forces use of this suppression list whenever you scrub files, so long as it applies to the file in question.'),
            ],
        ]);

        $this->add('submit', Field::BUTTON_SUBMIT, [
            'label' => '<i class="fa fa-check"></i> '.__('Submit'),
            'attr'  => [
                'class' => 'btn btn-info float-right mb-3 mt-3',
            ],
        ]);

        $this->add('cancel', Field::BUTTON_RESET, [
            'label' => '<i class="fa fa-times"></i> '.__('Cancel'),
            'attr'  => [
                'class'   => 'btn btn-secondary float-right',
                'onclick' => 'window.history.back()',
            ],
        ]);
    }

    /**
     * @param  array  $values
     *
     * @return void
     */
    public function alterFieldValues(array &$values)
    {
        $casts = (new SuppressionList())->getCasts();
        foreach ($values as $key => &$value) {
            if (isset($casts[$key]) && 'boolean' === $casts[$key]) {
                $value = (bool) $value;
            } else {
                $value = trim($value);
            }
        }
    }
}
