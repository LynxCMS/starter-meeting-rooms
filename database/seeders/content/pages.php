<?php

declare(strict_types=1);

/*
 * The home page, the sections' introductions, the offices and the legal pages, in English and
 * French. Keys are page paths; 'new' pages are made under the home page with that template.
 * 'shared' values are set once: repeater items carry their own language maps.
 */
return [
    '/' => [
        'en' => [
            'summary' => 'Counsel for every creature that keeps its feet wet.',
            'tagline' => 'We don’t do dry cases.',
            'body' => '<p>Castor &amp; Otters is a wildlife law firm in Vancouver and Montreal. For four generations we have represented the residents of lakes, rivers and their shores: their homes, their waters and their right to be loud at dawn.</p><p>We read the fine print, the zoning map and, when it matters, the riverbed.</p>',
        ],
        'fr' => [
            'summary' => 'Des conseils pour toutes les créatures qui ont les pieds dans l’eau.',
            'tagline' => 'Chez nous, aucun dossier ne reste à sec.',
            'body' => '<p>Castor &amp; Otters est un cabinet de droit de la faune établi à Vancouver et à Montréal. Depuis quatre générations, nous représentons les résidants des lacs, des rivières et de leurs rives : leurs foyers, leurs eaux et leur droit d’être bruyants à l’aube.</p><p>Nous lisons les petits caractères, le plan de zonage et, quand il le faut, le lit de la rivière.</p>',
        ],
        'shared' => [
            'highlights' => [
                ['values' => [
                    'figure' => ['en' => '4 generations', 'fr' => '4 générations'],
                    'figure_note' => ['en' => 'of lawyers who could swim before they could read', 'fr' => 'd’avocats qui nageaient avant de savoir lire'],
                ]],
                ['values' => [
                    'figure' => ['en' => '2 offices', 'fr' => '2 bureaux'],
                    'figure_note' => ['en' => 'one on the Pacific, one on the Saint-Laurent, both above the high-water mark', 'fr' => 'un sur le Pacifique, un sur le Saint-Laurent, tous deux au-dessus des hautes eaux'],
                ]],
                ['values' => [
                    'figure' => ['en' => '0 hours', 'fr' => '0 heure'],
                    'figure_note' => ['en' => 'billed during hibernation', 'fr' => 'facturée pendant l’hibernation'],
                ]],
            ],
        ],
    ],
    '/practice-areas/' => [
        'en' => ['summary' => 'Five practices, one watershed. Most of our files touch more than one.'],
        'fr' => ['summary' => 'Cinq pratiques, un seul bassin versant. La plupart de nos dossiers en touchent plus d’une.'],
    ],
    '/team/' => [
        'en' => ['summary' => 'Ten lawyers and paralegals, two offices, and more teeth than most firms our size.'],
        'fr' => ['summary' => 'Dix avocats et parajuristes, deux bureaux, et plus de dents que la plupart des cabinets de notre taille.'],
    ],
    '/news/' => [
        'en' => ['summary' => 'Recent cases, firm news, and the occasional award we gave a very good speech for.'],
        'fr' => ['summary' => 'Dossiers récents, nouvelles du cabinet, et quelques prix pour lesquels nous avons fait de très beaux discours.'],
    ],
    '/offices/' => [
        'en' => ['summary' => 'One office on the Pacific, one on the Saint-Laurent. Both a short swim from court.'],
        'fr' => ['summary' => 'Un bureau sur le Pacifique, un sur le Saint-Laurent. Les deux à quelques brasses du palais de justice.'],
    ],
    '/offices/vancouver/' => [
        'en' => ['summary' => 'Our first office, a converted boathouse on False Creek. The Lodge, our largest meeting room, was the boat shed.'],
        'fr' => ['summary' => 'Notre premier bureau, un ancien hangar à bateaux sur False Creek. The Lodge, notre plus grande salle de réunion, était le garage à canots.'],
    ],
    '/offices/montreal/' => [
        'en' => ['summary' => 'In the Old Port, a short swim from the Saint-Laurent, with two meeting rooms named after our neighbours.'],
        'fr' => ['summary' => 'Dans le Vieux-Port, à quelques brasses du Saint-Laurent, avec deux salles de réunion nommées d’après nos voisins.'],
    ],
    '/contact/' => [
        'en' => [
            'summary' => 'Tell us what is happening on your shore. A first consultation is free, and usually short.',
            'body' => '<p>Call the office nearest your watershed, or write to <a href="mailto:hello@castor-otters.test">hello@castor-otters.test</a>. We answer within two business days, faster during spawning season.</p><p>We meet clients in our offices, on site, or, for clients who prefer it, in the water.</p>',
        ],
        'fr' => [
            'summary' => 'Racontez-nous ce qui se passe sur votre rive. La première consultation est gratuite, et généralement brève.',
            'body' => '<p>Appelez le bureau le plus proche de votre bassin versant, ou écrivez à <a href="mailto:bonjour@castor-otters.test">bonjour@castor-otters.test</a>. Nous répondons en deux jours ouvrables, plus vite en période de frai.</p><p>Nous rencontrons nos clients à nos bureaux, sur place ou, s’ils le préfèrent, dans l’eau.</p>',
        ],
    ],
    '/not-found/' => [
        'fr_name' => 'page-introuvable',
        'shared' => ['illustration' => 'muskrat'],
        'en' => [
            'title' => 'This page swam off',
            'summary' => 'Our muskrat searched every channel and couldn’t find it. It may have moved, or never existed.',
        ],
        'fr' => [
            'title' => 'Cette page a filé à la nage',
            'summary' => 'Notre rat musqué a fouillé chaque chenal sans la trouver. Elle a peut-être déménagé, ou n’a jamais existé.',
        ],
    ],
    '/legal/' => [
        'new' => 'page', 'fr_name' => 'mentions-legales',
        'en' => [
            'title' => 'Legal notice',
            'body' => '<p>Castor &amp; Otters, Inc. is a fictional law firm. This site shows what Lynx CMS can do, and nothing on it is legal advice, for animals or anyone else.</p><p>Reading this site does not make us your lawyers. Only a signed engagement letter does, and we do not accept them by paw print.</p>',
        ],
        'fr' => [
            'title' => 'Mentions légales',
            'body' => '<p>Castor &amp; Otters, inc. est un cabinet fictif. Ce site montre ce que Lynx CMS sait faire, et rien de ce qu’il contient n’est un avis juridique, pour les animaux ou qui que ce soit.</p><p>La lecture de ce site ne fait pas de nous vos avocats. Seule une lettre de mandat signée le fait, et nous ne les acceptons pas sur empreinte de patte.</p>',
        ],
    ],
    '/privacy/' => [
        'new' => 'page', 'fr_name' => 'confidentialite',
        'en' => [
            'title' => 'Privacy',
            'body' => '<p>This site sets one cookie when you open the staff’s rooms with their password, so you are not asked again for a month. It keeps no analytics and sends nothing elsewhere: its fonts are served from here.</p>',
        ],
        'fr' => [
            'title' => 'Confidentialité',
            'body' => '<p>Ce site dépose un seul témoin lorsque vous ouvrez les salles du personnel avec leur mot de passe, pour ne pas vous le redemander pendant un mois. Il ne fait aucune mesure d’audience et n’envoie rien ailleurs : ses polices sont servies d’ici.</p>',
        ],
    ],
];
