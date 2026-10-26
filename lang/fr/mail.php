<?php

// Courriels des réunions (MeetingMail, resources/views/mail/meeting.blade.php) et textes d'agenda.
return [
    'subject_booked' => 'Réservé : :title, :when',
    'subject_invitation' => 'Invitation : :title, :when',
    'subject_changed' => 'Modifié : :title, :when',
    'subject_cancelled' => 'Annulé : :title, :when',
    'subject_uninvited' => 'Annulé : :title, :when',
    'subject_released' => 'Salle libérée : :title, :room',
    'subject_reminder' => 'Dans :minutes minutes : :title, :room',
    'private_title' => 'Votre réunion privée',

    'intro_booked' => 'Votre réunion est réservée. L\'invitation est jointe pour votre agenda.',
    'intro_invitation' => ':organizer vous invite à une réunion.',
    'intro_changed' => 'Cette réunion a changé. La voici telle qu\'elle est maintenant.',
    'intro_cancelled' => 'Cette réunion est annulée.',
    'intro_uninvited' => 'Vous n\'êtes plus invité à cette réunion.',
    'intro_released' => 'Personne ne s\'est présenté dans les :minutes minutes suivant le début : le reste du temps de la salle a été libéré.',
    'intro_reminder' => 'Votre réunion commence dans :minutes minutes.',
    'on_changed' => 'Ce changement vise le :date : désormais de :from à :to, salle :room.',
    'on_cancelled' => 'Seul le :date est annulé. Les autres dates restent.',

    'when' => 'Quand :',
    'where' => 'Où :',
    'organizer' => 'Organisation :',
    'invited' => 'Invités :',
    'times' => 'de :from à :to',
    'until' => 'jusqu\'au :date',
    'local_time' => 'heure de :city',
    'repeat_weekly' => 'Chaque :day',
    'repeat_biweekly' => 'Un :day sur deux',
    'repeat_monthly' => 'Chaque mois, le même :day du mois',

    'private_note' => 'Elle est privée : les écrans et vos collègues ne voient que « Occupée ».',
    'ics_request' => 'Ouvrez l\'invitation jointe pour l\'ajouter à votre agenda, ou mettre à jour celle qui s\'y trouve.',
    'ics_cancel' => 'Ouvrez le fichier joint pour la retirer de votre agenda.',
    'my_bookings' => 'Mes réservations',
    'signature' => 'Les salles de Castor & Otters',

    'footer' => 'Envoyé par les salles de réunion, qui ne réservent jamais deux fois.',

    'ics_description' => "Organisation : :organizer\nInvités : :people",
    'feed_mine' => ':name : réunions',
    'feed_busy' => 'Occupée',
    'feed_blocked' => 'Bloqué : :reason',
];
