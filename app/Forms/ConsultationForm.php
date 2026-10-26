<?php

declare(strict_types=1);

namespace App\Forms;

use LynxCms\Forms\Definition\Form;
use LynxCms\Forms\Definition\FormField;
use LynxCms\Forms\Definition\FormStep;

/**
 * The consultation request on the contact page: what the matter is, then who to answer. Lynx keeps
 * each submission and mails the people allowed to read them (forms.view); it is shown in the
 * visitor's language.
 */
final class ConsultationForm extends Form
{
    public function key(): string
    {
        return 'consultation';
    }

    public function label(): array
    {
        return ['en' => 'Consultation request', 'fr' => 'Demande de consultation'];
    }

    public function fields(): array
    {
        return [
            FormField::radio('client')->label(['en' => 'Who are you writing for?', 'fr' => 'Pour qui écrivez-vous?'])->required()->options([
                'myself' => ['en' => 'Myself', 'fr' => 'Moi-même'],
                'family' => ['en' => 'My family, colony or flock', 'fr' => 'Ma famille, ma colonie ou ma volée'],
                'business' => ['en' => 'A business or a landowner', 'fr' => 'Une entreprise ou un propriétaire'],
                'habitat' => ['en' => 'A wetland that can’t write for itself', 'fr' => 'Un milieu humide qui ne sait pas écrire'],
            ]),
            FormField::select('area')->label(['en' => 'Which practice area?', 'fr' => 'Quel domaine?'])->required()->width(50)->options([
                'water-rights' => ['en' => 'Water rights and dams', 'fr' => 'Droit de l’eau et barrages'],
                'habitat' => ['en' => 'Habitat and land use', 'fr' => 'Habitat et aménagement du territoire'],
                'migration' => ['en' => 'Migration', 'fr' => 'Migration'],
                'fisheries' => ['en' => 'Fisheries and catch disputes', 'fr' => 'Pêcheries et litiges de prises'],
                'family' => ['en' => 'Family law', 'fr' => 'Droit de la famille'],
                'unsure' => ['en' => 'Not sure yet; it’s complicated', 'fr' => 'Je ne sais pas encore, c’est compliqué'],
            ]),
            FormField::select('office')->label(['en' => 'Nearest office', 'fr' => 'Bureau le plus proche'])->required()->width(50)->options([
                'vancouver' => 'Vancouver',
                'montreal' => 'Montréal',
            ]),
            FormField::textarea('message')->label(['en' => 'What is happening on your shore?', 'fr' => 'Que se passe-t-il sur votre rive?'])->required()->max(4000)
                ->help(['en' => 'A few lines are enough. Dates, places and who built what help.', 'fr' => 'Quelques lignes suffisent. Les dates, les lieux et qui a construit quoi nous aident.']),
            FormField::text('name')->label(['en' => 'Your name', 'fr' => 'Votre nom'])->required()->width(50),
            FormField::tel('phone')->label(['en' => 'Phone', 'fr' => 'Téléphone'])->width(50),
            FormField::email('email')->label(['en' => 'Email', 'fr' => 'Courriel'])->required()->replyTo(),
            FormField::checkbox('consent')->required()->label([
                'en' => 'I understand this is not yet legal advice, and that nobody is billed for reading it.',
                'fr' => 'Je comprends que ce n’est pas encore un avis juridique, et que personne n’est facturé pour le lire.',
            ]),
        ];
    }

    public function steps(): array
    {
        return [
            new FormStep('matter', ['en' => 'Your matter', 'fr' => 'Votre dossier'], ['client', 'area', 'office', 'message']),
            new FormStep('contact', ['en' => 'How to reach you', 'fr' => 'Pour vous joindre'], ['name', 'phone', 'email', 'consent']),
        ];
    }

    public function successMessage(): array
    {
        return [
            'en' => 'Thank you. Someone from the right office will reply within two business days, faster during spawning season.',
            'fr' => 'Merci. Quelqu’un du bon bureau vous répondra d’ici deux jours ouvrables, plus vite en période de frai.',
        ];
    }

    public function autoReply(): array
    {
        return [
            'en' => 'We received your consultation request and will reply within two business days. Castor & Otters, Inc.',
            'fr' => 'Nous avons bien reçu votre demande de consultation et vous répondrons d’ici deux jours ouvrables. Castor & Otters, inc.',
        ];
    }
}
