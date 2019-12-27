<?php

namespace App\Forms;

use App\Models\File;
use Kris\LaravelFormBuilder\Field;
use Kris\LaravelFormBuilder\Form;

/**
 * Class FileForm
 *
 * @package App\Forms
 */
class FileEmailForm extends Form
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

        $class             = '';
        $email             = $file->user->email ?? $file->email ?? null;
        $this->formOptions = [
            'method' => 'POST',
            'url'    => route('file.email', ['id' => $file->id]),
        ];
        if ($email) {
            $class .= ' d-none';
        }
        $this->add('email', Field::EMAIL, [
            'label'         => __('Email'),
            'label_show'    => true,
            'rules'         => 'bail|required|email:rfc,dns',
            'label_attr'    => [
                'class' => 'col-md-2'.$class,
            ],
            'attr'          => [
                'class'               => 'form-control custom-control-inline col-md-3 pl-3',
                'data-toggle'         => 'tooltip',
                'data-placement'      => 'right',
                'data-original-title' => __('Optional: Address to be notified when this is done with a temporary download link.'),
            ],
            'wrapper'       => [
                'class' => 'custom-control mb-3'.$class,
            ],
            'default_value' => $email ?? session()->get('email', ''),
        ]);
        if ($email) {
            $this->add('static_notify', Field::STATIC, [
                'tag'        => 'a',
                'label'      => ' ',
                'label_show' => false,
                'label_attr' => [
                    'class' => 'col-md-2',
                ],
                'value'      => __(':email will be notified when done.',
                    ['email' => $email ?? 'nobody']),
                'attr'       => [
                    'class' => 'btn-sm col-md-3 text-left',
                    'id'    => 'file-email',
                ],
                'wrapper'    => [
                    'class' => 'custom-control mb-3 row',
                ],
            ]);
        }
        $this->add('submit_email', Field::BUTTON_SUBMIT, [
            'label' => '<i class="fa fa-mail-reply"></i> '.__('Notify Me when Done'),
            'attr'  => [
                'class' => 'btn btn-info mb-1 mt-4 col-md-3'.$class,
            ],
        ]);
    }
}
