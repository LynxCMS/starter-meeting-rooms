<?php

declare(strict_types=1);

/*
 * The site's words that stay with the code: labels for screen readers, plurals, and texts built
 * around a value. Everything else the owner may reword is a Site or Page text (@string) in the
 * templates.
 */
return [
    'skip' => 'Skip to the content',
    'sections' => 'Sections',
    'language' => 'Language',
    'local_time' => 'Our clocks run on :zone.',
    'guard' => 'A heron in reading glasses, guarding the door',
    'search' => [
        'label' => 'Search the site',
        'found' => '{0} Nothing found for “:query”.|{1} One page mentions “:query”.|[2,*] :count pages mention “:query”.',
    ],
    'rooms' => [
        'seats' => '{1} :count seat|[2,*] :count seats',
        'range' => ':from to :to',
    ],
];
