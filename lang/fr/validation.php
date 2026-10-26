<?php

declare(strict_types=1);

// French for the validation rules the site's forms use (the consultation request); add rules as forms need them.
return [
    'accepted' => 'Veuillez cocher cette case.',
    'email' => 'Le champ « :attribute » doit être une adresse courriel valide.',
    'in' => 'Le choix « :attribute » n’est pas valide.',
    'max' => [
        'string' => 'Le champ « :attribute » ne peut dépasser :max caractères.',
    ],
    'required' => 'Le champ « :attribute » est obligatoire.',
    'string' => 'Le champ « :attribute » doit être du texte.',
];
