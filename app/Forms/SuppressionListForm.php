<?php

namespace App\Forms;

use App\Models\SuppressionList;
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
    }
}
