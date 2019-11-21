<?php

use App\SuppressionListSupport;

return [
    SuppressionListSupport::TYPE_AGE => 'age',
    SuppressionListSupport::TYPE_DOB => 'birthdate',
    SuppressionListSupport::TYPE_NAME_FIRST => 'firstname',
    SuppressionListSupport::TYPE_NAME_LAST => 'lastname',
    SuppressionListSupport::TYPE_L_ADDRESS1 => 'address1',
    SuppressionListSupport::TYPE_L_ADDRESS2 => 'address2',
    SuppressionListSupport::TYPE_L_CITY => 'city',
    SuppressionListSupport::TYPE_L_ZIP => 'zip',
    SuppressionListSupport::TYPE_L_COUNTRY => 'country',
    SuppressionListSupport::TYPE_EMAIL => 'email',
    SuppressionListSupport::TYPE_PHONE => 'phone',
    SuppressionListSupport::TYPE_HASH => 'hash',
    'icons' => [
        SuppressionListSupport::TYPE_AGE => 'birthday-cake',
        SuppressionListSupport::TYPE_DOB => 'birthday-cake',
        SuppressionListSupport::TYPE_NAME_FIRST => 'address-card',
        SuppressionListSupport::TYPE_NAME_LAST => 'address-card',
        SuppressionListSupport::TYPE_L_ADDRESS1 => 'home',
        SuppressionListSupport::TYPE_L_ADDRESS2 => 'home',
        SuppressionListSupport::TYPE_L_CITY => 'city',
        SuppressionListSupport::TYPE_L_ZIP => 'mail-bulk',
        SuppressionListSupport::TYPE_L_COUNTRY => 'globe-americas',
        SuppressionListSupport::TYPE_EMAIL => 'envelope',
        SuppressionListSupport::TYPE_PHONE => 'phone',
        SuppressionListSupport::TYPE_HASH => 'hashtag',
    ]
];
