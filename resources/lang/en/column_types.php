<?php

use App\SuppressionListSupport;

return [
    SuppressionListSupport::TYPE_AGE        => 'Age',
    SuppressionListSupport::TYPE_DOB        => 'Birth Date',
    SuppressionListSupport::TYPE_NAME_FIRST => 'First Name',
    SuppressionListSupport::TYPE_NAME_LAST  => 'Last Name',
    SuppressionListSupport::TYPE_L_ADDRESS1 => 'Address1',
    SuppressionListSupport::TYPE_L_ADDRESS2 => 'Address2',
    SuppressionListSupport::TYPE_L_CITY     => 'City',
    SuppressionListSupport::TYPE_L_ZIP      => 'Zip',
    SuppressionListSupport::TYPE_L_COUNTRY  => 'Country',
    SuppressionListSupport::TYPE_EMAIL      => 'Email',
    SuppressionListSupport::TYPE_PHONE      => 'Phone',
    SuppressionListSupport::TYPE_HASH       => 'Hash',
    'plural'                                => [
        SuppressionListSupport::TYPE_AGE        => 'Ages',
        SuppressionListSupport::TYPE_DOB        => 'Birth Dates',
        SuppressionListSupport::TYPE_NAME_FIRST => 'First Names',
        SuppressionListSupport::TYPE_NAME_LAST  => 'Last Names',
        SuppressionListSupport::TYPE_L_ADDRESS1 => 'Address1',
        SuppressionListSupport::TYPE_L_ADDRESS2 => 'Address2',
        SuppressionListSupport::TYPE_L_CITY     => 'Cities',
        SuppressionListSupport::TYPE_L_ZIP      => 'Zips',
        SuppressionListSupport::TYPE_L_COUNTRY  => 'Countries',
        SuppressionListSupport::TYPE_EMAIL      => 'Emails',
        SuppressionListSupport::TYPE_PHONE      => 'Numbers',
        SuppressionListSupport::TYPE_HASH       => 'Hashes',
    ],
    'icons'                                 => [
        SuppressionListSupport::TYPE_AGE        => 'birthday-cake',
        SuppressionListSupport::TYPE_DOB        => 'birthday-cake',
        SuppressionListSupport::TYPE_NAME_FIRST => 'address-card',
        SuppressionListSupport::TYPE_NAME_LAST  => 'address-card',
        SuppressionListSupport::TYPE_L_ADDRESS1 => 'home',
        SuppressionListSupport::TYPE_L_ADDRESS2 => 'home',
        SuppressionListSupport::TYPE_L_CITY     => 'city',
        SuppressionListSupport::TYPE_L_ZIP      => 'mail-bulk',
        SuppressionListSupport::TYPE_L_COUNTRY  => 'globe-americas',
        SuppressionListSupport::TYPE_EMAIL      => 'envelope',
        SuppressionListSupport::TYPE_PHONE      => 'phone',
        SuppressionListSupport::TYPE_HASH       => 'hashtag',
    ],
];
