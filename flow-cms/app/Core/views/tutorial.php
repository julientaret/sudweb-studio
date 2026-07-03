<?php
/** @var string $base */
/** @var array<int, string> $enabledModules liste effective (Config 'modules.enabled') */
/** @var bool $canMaintenance l'utilisateur détient-il admin.maintenance ? */
/** @var bool $canAdmin l'utilisateur détient-il admin.modules ? */

/**
 * Sections du tutoriel : id => [titre onglet, HTML du panneau].
 *
 * Chaque panneau mêle explications et petits APERÇUS illustratifs (champs factices,
 * désactivés et masqués des lecteurs d'écran) pour montrer concrètement de quoi on
 * parle. Le premier panneau est ouvert par défaut (actif posé côté serveur), donc
 * la page reste utilisable même sans JS.
 */
$sections = [
    'dashboard' => ['Tableau de bord', <<<HTML
        <h2>Le tableau de bord</h2>
        <p>C'est l'écran d'accueil (<a href="{$base}/admin">/admin</a>). Il regroupe des
        <strong>cartes d'accès rapide</strong> vers les sections auxquelles vous avez droit, et une
        <strong>visionneuse</strong> qui fait défiler les images de la bibliothèque.</p>

        <div class="demo">
            <span class="demo-tag">Aperçu — cartes d'accès rapide</span>
            <div class="demo-cards" aria-hidden="true">
                <span class="demo-card">📄 Pages</span>
                <span class="demo-card">🖼️ Médias</span>
                <span class="demo-card">👤 Utilisateurs</span>
            </div>
        </div>

        <h3>Notifications non lues</h3>
        <p>Les cartes <strong>Demandes de contact</strong> et <strong>Demandes de devis</strong> affichent
        une <strong>bulle</strong> avec le nombre de demandes non lues, dès qu'il y en a. La même bulle
        apparaît sur leur icône dans la barre latérale, y compris repliée.</p>

        <p class="muted">Astuce : le logo en haut de la barre latérale ramène toujours ici.</p>
HTML],

    'pages' => ['Les pages', <<<HTML
        <h2>Les pages</h2>
        <p>Menu <strong>Pages</strong> : la liste de vos pages, leur statut et leur adresse.
        Cliquez sur <em>+ Nouvelle page</em> pour ouvrir l'éditeur. Il a deux parties : les
        <strong>réglages</strong> (titre, slug, statut) en haut, puis le <strong>contenu</strong> en dessous.</p>

        <h3>Réglages</h3>
        <ul>
            <li><strong>Titre &amp; slug</strong> — le slug est l'adresse de la page (<code>/mon-titre</code>).
                Laissez-le vide pour le générer automatiquement depuis le titre.</li>
            <li><strong>Statut</strong> — un interrupteur <em>Brouillon / Publiée</em>. Seules les pages
                publiées sont visibles du public.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — réglages</span>
            <label>Titre de la page</label>
            <input type="text" value="Notre histoire" disabled aria-hidden="true" tabindex="-1">
            <label>Statut</label>
            <div class="demo-toggle" aria-hidden="true">
                <span>Brouillon</span><span class="on">Publiée</span>
            </div>
        </div>

        <h3>Les lignes</h3>
        <p>Le contenu d'une page est une pile de <strong>lignes</strong>, empilées de haut en bas. Chaque
        ligne est une bande horizontale qu'on découpe en <strong>1 à 4 colonnes</strong> côte à côte
        (boutons <em>1 · 2 · 3 · 4</em> en haut de la ligne). Exemple courant : deux colonnes pour poser
        un texte à gauche et une image à droite.</p>
        <ul>
            <li><strong>Ajouter</strong> une ligne : bouton <em>+ Ajouter une ligne</em> sous le contenu.</li>
            <li><strong>Réordonner</strong> : saisissez la ligne par sa poignée (⠿) et glissez-la
                au-dessus ou en dessous d'une autre.</li>
            <li><strong>Changer le nombre de colonnes</strong> : cliquez 1, 2, 3 ou 4. Si vous réduisez,
                rien n'est perdu : le contenu des colonnes retirées passe dans la dernière conservée.</li>
            <li><strong>Sur mobile</strong>, les colonnes s'empilent automatiquement.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — une ligne de 2 colonnes</span>
            <div class="demo-row" aria-hidden="true">
                <div class="demo-col"><span class="demo-block">Titre</span><span class="demo-block">Texte</span></div>
                <div class="demo-col"><span class="demo-block">Image</span></div>
            </div>
        </div>

        <h3>Les blocs</h3>
        <p>Dans une colonne, le contenu se construit avec des <strong>blocs</strong>, ajoutés via les
        boutons <em>+ Titre / + Texte / + Image</em> au bas de la colonne. On en empile autant que voulu.</p>
        <ul>
            <li><strong>Titre</strong> — un intertitre (niveaux 1 à 3) pour structurer la page.</li>
            <li><strong>Texte</strong> — un éditeur enrichi : gras, italique, souligné, listes, liens,
                citation, titres, alignement et couleur. Pour les paragraphes.</li>
            <li><strong>Image</strong> — choisie dans la bibliothèque via une fenêtre de vignettes ;
                un texte alternatif facultatif la décrit. Deux réglages d'aspect en vignettes cliquables :
                <strong>coins arrondis</strong> (aucun à complet) et <strong>ombre portée</strong>
                (aucune à marquée).</li>
            <li><strong>Déplacer</strong> : chaque bloc a une poignée (⠿) ; glissez-le dans sa colonne
                ou vers une autre colonne, même d'une autre ligne.</li>
            <li><strong>Supprimer</strong> : le lien <em>Supprimer</em> en haut du bloc.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — ajouter un bloc</span>
            <div class="demo-bar" aria-hidden="true">
                <span class="demo-pill">+ Titre</span>
                <span class="demo-pill">+ Texte</span>
                <span class="demo-pill">+ Image</span>
            </div>
        </div>

        <h3>Animation au scroll</h3>
        <p>Chaque bloc a, dans son en-tête, deux menus <strong>Animation</strong> et
        <strong>Délai</strong> : un effet (fondu, zoom…) qui se joue quand le bloc apparaît à l'écran en
        faisant défiler la page, avec un délai optionnel avant qu'il ne se déclenche.</p>

        <div class="demo">
            <span class="demo-tag">Aperçu — en-tête d'un bloc</span>
            <div class="demo-bar" aria-hidden="true">
                <span class="demo-pill">Animation : Fondu (bas → haut) ▾</span>
                <span class="demo-pill">Délai : 200 ms ▾</span>
            </div>
        </div>

        <p class="muted">N'oubliez pas d'<strong>Enregistrer</strong> — en bas de l'éditeur, ou via le
        bouton flottant qui reste visible en bas de l'écran sur les pages longues.</p>
HTML],

    'agenda' => ['L\'agenda', <<<HTML
        <h2>L'agenda</h2>
        <p>Menu <strong>Agenda</strong> : vos <strong>événements</strong> (rendez-vous, portes ouvertes,
        expositions…). Chaque événement a sa propre adresse publique <code>/agenda/mon-evenement</code>.
        Cliquez sur <em>+ Nouvel événement</em> pour ouvrir l'éditeur.</p>

        <h3>Réglages</h3>
        <ul>
            <li><strong>Titre &amp; slug</strong> — comme pour une page : le slug est l'adresse, laissé vide
                il se génère depuis le titre.</li>
            <li><strong>Date de début</strong> — obligatoire. <strong>Date de fin</strong> — facultative, pour
                un événement qui s'étale sur plusieurs jours. Sur le site, on lit alors « Du … au … ».</li>
            <li><strong>Image vignette</strong> — une image de la bibliothèque, choisie dans la même fenêtre
                de vignettes que les blocs image. Elle illustre l'événement en haut de sa page.</li>
            <li><strong>Statut</strong> — interrupteur <em>Brouillon / Publié</em> : seuls les événements
                publiés sont visibles du public.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — réglages d'un événement</span>
            <label>Titre de l'événement</label>
            <input type="text" value="Portes ouvertes 2026" disabled aria-hidden="true" tabindex="-1">
            <div class="demo-dates" aria-hidden="true">
                <div><label>Date de début</label><input type="text" value="12/06/2026" disabled tabindex="-1"></div>
                <div><label>Date de fin</label><input type="text" value="14/06/2026" disabled tabindex="-1"></div>
            </div>
            <label>Image vignette</label>
            <div class="demo-thumb" aria-hidden="true">
                <span class="demo-thumb-box">🖼️</span>
                <span class="demo-pill">Choisir une image</span>
            </div>
        </div>

        <h3>Le contenu</h3>
        <p>Sous les réglages, le contenu se compose <strong>exactement comme une page</strong> : des
        <strong>lignes</strong> découpées en colonnes, remplies de <strong>blocs</strong> (Titre, Texte,
        Image). Reportez-vous à la rubrique <strong>Les pages</strong> pour le détail des lignes et des blocs —
        le fonctionnement est identique.</p>

        <p class="muted">Les événements récemment modifiés apparaissent aussi dans
        <strong>Dernières modifications</strong> du tableau de bord, marqués d'une étiquette « Événement ».</p>
HTML],

    'actualites' => ['Les actualités', <<<HTML
        <h2>Les actualités</h2>
        <p>Menu <strong>Actualités</strong> : vos <strong>articles d'actualité</strong> (annonces,
        nouveautés, communiqués…). Chaque actualité a sa propre adresse publique
        <code>/actualites/mon-article</code>. Cliquez sur <em>+ Nouvelle actualité</em> pour ouvrir l'éditeur.</p>

        <h3>Réglages</h3>
        <ul>
            <li><strong>Titre &amp; slug</strong> — comme pour une page : le slug est l'adresse, laissé vide
                il se génère depuis le titre.</li>
            <li><strong>Date de publication</strong> — obligatoire, pré-remplie au jour même. C'est la date
                affichée sur le site (« Publié le … ») et qui ordonne la liste.</li>
            <li><strong>Image vignette</strong> — une image de la bibliothèque, choisie dans la même fenêtre
                de vignettes que les blocs image. Elle illustre l'actualité en haut de sa page.</li>
            <li><strong>Statut</strong> — interrupteur <em>Brouillon / Publiée</em> : seules les actualités
                publiées sont visibles du public.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — réglages d'une actualité</span>
            <label>Titre de l'actualité</label>
            <input type="text" value="Rentrée 2026 : ce qui change" disabled aria-hidden="true" tabindex="-1">
            <label>Date de publication</label>
            <input type="text" value="12/06/2026" disabled aria-hidden="true" tabindex="-1">
            <label>Image vignette</label>
            <div class="demo-thumb" aria-hidden="true">
                <span class="demo-thumb-box">🖼️</span>
                <span class="demo-pill">Choisir une image</span>
            </div>
        </div>

        <h3>Le contenu</h3>
        <p>Sous les réglages, le contenu se compose <strong>exactement comme une page</strong> : des
        <strong>lignes</strong> découpées en colonnes, remplies de <strong>blocs</strong> (Titre, Texte,
        Image). Reportez-vous à la rubrique <strong>Les pages</strong> pour le détail des lignes et des blocs —
        le fonctionnement est identique.</p>

        <p class="muted">Les actualités récemment modifiées apparaissent aussi dans
        <strong>Dernières modifications</strong> du tableau de bord, marquées d'une étiquette « Actualité ».</p>
HTML],

    'realisations' => ['Les réalisations', <<<HTML
        <h2>Les réalisations</h2>
        <p>Menu <strong>Réalisations</strong> : votre <strong>portfolio</strong> (chantiers, projets,
        références…). Chaque réalisation est une fiche simple — un <strong>titre</strong>, une
        <strong>description</strong> et une <strong>photo</strong>. Cliquez sur
        <em>+ Nouvelle réalisation</em> pour ouvrir l'éditeur.</p>

        <h3>Composer une réalisation</h3>
        <ul>
            <li><strong>Titre</strong> — le nom de la réalisation.</li>
            <li><strong>Description</strong> — un <strong>éditeur de texte riche</strong> (gras, listes,
                liens, titres…), pour présenter le projet en quelques lignes.</li>
            <li><strong>Photo</strong> — une image de la bibliothèque, choisie dans la même fenêtre de
                vignettes que les blocs image.</li>
            <li><strong>Ordre d'affichage</strong> — un nombre : les plus petits passent en premier.
                Pratique pour mettre en avant vos plus belles réalisations.</li>
            <li><strong>Statut</strong> — interrupteur <em>Brouillon / Publiée</em> : seules les
                réalisations publiées apparaissent sur le site.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — une réalisation</span>
            <label>Titre de la réalisation</label>
            <input type="text" value="Rénovation d'une villa à Carcassonne" disabled aria-hidden="true" tabindex="-1">
            <label>Photo</label>
            <div class="demo-thumb" aria-hidden="true">
                <span class="demo-thumb-box">🖼️</span>
                <span class="demo-pill">Choisir une image</span>
            </div>
        </div>

        <h3>Afficher les réalisations sur le site</h3>
        <p>Les réalisations ne s'affichent pas seules : on les pose sur une page avec le
        <strong>bloc « Réalisations »</strong>. Dans l'éditeur d'une page, ajoutez ce bloc à l'endroit
        voulu ; vous pouvez lui donner un <strong>titre de section</strong> et choisir <strong>combien</strong>
        de réalisations afficher. Voir la rubrique <strong>Les pages</strong> pour l'usage des blocs.</p>

        <p class="muted">Astuce : laissez en <em>Brouillon</em> les réalisations en préparation ; elles
        restent invisibles du public jusqu'à leur publication.</p>
HTML],

    'sliders' => ['Les sliders', <<<HTML
        <h2>Les sliders (diapositives)</h2>
        <p>Menu <strong>Sliders</strong> : les <strong>diapositives</strong> d'un carrousel (le bandeau
        d'images qui défile, souvent en haut de l'accueil). Chaque diapositive a un
        <strong>titre</strong>, une <strong>description</strong> et une <strong>photo</strong>. Cliquez
        sur <em>+ Nouvelle diapositive</em> pour ouvrir l'éditeur.</p>

        <h3>Composer une diapositive</h3>
        <ul>
            <li><strong>Titre</strong> — l'accroche affichée sur la diapositive.</li>
            <li><strong>Description</strong> — un <strong>éditeur de texte riche</strong> pour un court
                texte d'accompagnement (facultatif).</li>
            <li><strong>Photo</strong> — l'image de fond/illustration, choisie dans la bibliothèque.</li>
            <li><strong>Ordre d'affichage</strong> — un nombre : il fixe l'<strong>ordre de défilement</strong>
                des diapositives (les plus petits d'abord).</li>
            <li><strong>Statut</strong> — interrupteur <em>Brouillon / Publiée</em> : seules les
                diapositives publiées défilent sur le site.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — une diapositive</span>
            <label>Titre de la diapositive</label>
            <input type="text" value="Bienvenue chez nous" disabled aria-hidden="true" tabindex="-1">
            <label>Photo</label>
            <div class="demo-thumb" aria-hidden="true">
                <span class="demo-thumb-box">🖼️</span>
                <span class="demo-pill">Choisir une image</span>
            </div>
        </div>

        <p class="muted">Pour l'instant, les diapositives publiées ne s'affichent pas encore
        automatiquement sur le site public : leur intégration (emplacement, effet de défilement) se règle
        avec <strong>Sud Web Studio</strong>. De votre côté, vous gérez déjà le contenu et l'ordre des
        diapositives, prêts à être affichés dès que l'intégration est branchée.</p>
HTML],

    'legal_pages' => ['Les pages légales', <<<HTML
        <h2>Les pages légales</h2>
        <p>Menu <strong>Pages légales</strong> : vos <strong>pages juridiques</strong> (mentions légales,
        politique de confidentialité, CGU, cookies…). Deux pages existent déjà « pour commencer » ;
        vous pouvez en <strong>ajouter</strong> autant que nécessaire avec <em>+ Nouvelle page</em>.</p>
        <p>Chaque page a sa propre adresse publique à la racine du site, d'après son slug :
        <code>/mentions-legales</code>, <code>/politique-de-confidentialite</code>… — pratique pour
        les liens du pied de page.</p>

        <h3>Réglages</h3>
        <ul>
            <li><strong>Titre &amp; slug</strong> — le slug est l'adresse de la page ; laissé vide,
                il se génère depuis le titre.</li>
            <li><strong>Statut</strong> — interrupteur <em>Brouillon / Publiée</em> : seules les pages
                publiées sont visibles du public (une page en brouillon renvoie une 404).</li>
        </ul>

        <h3>Le contenu</h3>
        <p>Le contenu s'écrit dans un <strong>éditeur de texte riche</strong> (gras, titres, listes,
        liens, citations…) : pas de blocs ni de colonnes, juste du texte mis en forme — l'idéal pour
        de la prose juridique. La mise en forme est volontairement bornée pour rester propre et sûre.</p>
HTML],

    'google_reviews' => ['Les avis Google', <<<HTML
        <h2>Les avis Google</h2>
        <p>Menu <strong>Avis Google</strong> : récupère les avis laissés sur la fiche Google de
        l'établissement et permet de choisir, un par un, lesquels afficher sur le site. L'affichage se
        fait via un <strong>bloc « Avis Google »</strong> à poser dans le contenu d'une page (voir la
        rubrique <strong>Les pages</strong>).</p>

        <h3>Synchroniser</h3>
        <p>Le bouton <strong>Synchroniser les 5 derniers avis</strong> va chercher chez Google les
        <strong>5 avis les plus récents</strong> et les ajoute à la liste, sans jamais créer de doublon.
        On ne récupère que les 5 derniers à chaque fois : la liste se complète donc au fil des
        synchronisations successives.</p>
        <ul>
            <li><strong>Une synchronisation par jour</strong> : une fois lancée, le bouton se grise
                jusqu'au lendemain. C'est volontaire (les avis bougent peu et chaque appel a un coût).</li>
            <li><strong>Curation conservée</strong> : un avis que vous avez masqué le reste après une
                nouvelle synchronisation.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — synchronisation</span>
            <div class="demo-bar" aria-hidden="true">
                <span class="demo-pill">⟳ Synchroniser les 5 derniers avis</span>
            </div>
        </div>

        <h3>Afficher ou masquer un avis</h3>
        <p>Chaque avis de la liste porte un bouton <strong>Afficher / Masquer</strong> : vous décidez
        ainsi ce que voient les visiteurs. Pratique pour écarter un avis hors sujet ou ne garder que les
        plus parlants. Seuls les avis « Affichés » apparaissent dans le bloc sur le site.</p>

        <div class="demo">
            <span class="demo-tag">Aperçu — un avis dans la liste</span>
            <div class="demo-review" aria-hidden="true">
                <div class="demo-review-head">
                    <strong>Marie L.</strong>
                    <span class="demo-stars">★★★★★</span>
                    <span class="demo-chip on">Affiché</span>
                </div>
                <p>« Accueil au top et travail soigné, je recommande vivement. »</p>
            </div>
        </div>

        <p class="muted">Le choix de l'établissement Google est réglé une fois pour toutes par
        <strong>Sud Web Studio</strong> ; côté gestion du site, vous n'avez qu'à synchroniser et choisir
        les avis à mettre en avant.</p>
HTML],

    'horaires' => ['Les horaires d\'ouverture', <<<HTML
        <h2>Les horaires d'ouverture</h2>
        <p>Menu <strong>Horaires d'ouverture</strong> : réglez les horaires de votre établissement,
        jour par jour, et affichez-les sur le site via un <strong>bloc « Horaires d'ouverture »</strong>
        à poser dans le contenu d'une page (voir la rubrique <strong>Les pages</strong>).</p>

        <h3>Horaires hebdomadaires</h3>
        <p>Pour chaque jour de la semaine, indiquez l'heure d'<strong>ouverture</strong> et de
        <strong>fermeture</strong>, ou cochez <strong>Fermé</strong> si l'établissement n'ouvre pas ce
        jour-là. Sur le site, les jours consécutifs ayant les mêmes horaires s'affichent groupés
        (ex. « Lundi – Vendredi »).</p>

        <h3>Fermetures exceptionnelles</h3>
        <p>Congés, jours fériés ou toute autre fermeture ponctuelle : ajoutez une ligne avec une date
        de début, une date de fin et un motif facultatif (ex. « Congés d'été »). Elles s'affichent sur
        le site tant qu'elles ne sont pas passées, puis disparaissent automatiquement.</p>

        <div class="demo">
            <span class="demo-tag">Aperçu — le bloc sur le site</span>
            <div class="demo-bar" aria-hidden="true">
                <span class="demo-pill">Lundi – Vendredi · 8h30 – 18h00</span>
                <span class="demo-pill">Dimanche · Fermé</span>
            </div>
        </div>
HTML],

    'media' => ['Les médias', <<<HTML
        <h2>Les médias</h2>
        <p>Menu <strong>Médias</strong> : la bibliothèque d'images partagée par tout le site. C'est ici
        qu'on téléverse et qu'on organise les visuels utilisés par les pages.</p>

        <h3>Téléverser</h3>
        <p>Glissez <strong>une ou plusieurs images d'un coup</strong> sur la zone d'envoi (ou cliquez-la
        pour les choisir). Chaque image s'envoie avec sa propre <strong>barre de progression</strong>, puis
        passe au vert une fois terminée — la liste se met à jour automatiquement à la fin.</p>
        <p>À l'arrivée, Flow <strong>ré-encode</strong> l'image (sécurité) et génère automatiquement
        plusieurs largeurs — <strong>mobile, tablette et ordinateur</strong> — en version <strong>WebP</strong>
        plus légère. Sur le site, le navigateur du visiteur télécharge tout seul la largeur la mieux adaptée à
        son écran : les pages restent <strong>rapides sur mobile</strong> sans rien régler de votre côté.
        Si une image est refusée (mauvais type ou trop lourde, 5 Mo max), elle passe en rouge avec le motif,
        sans bloquer les autres.</p>

        <div class="demo">
            <span class="demo-tag">Aperçu — envoi</span>
            <div class="demo-drop" aria-hidden="true">⤓ Glissez vos images ici, ou cliquez pour téléverser</div>
        </div>

        <h3>Les dossiers</h3>
        <p>Les dossiers servent à <strong>ranger</strong> la bibliothèque par thème, au lieu d'une seule
        liste qui s'allonge sans fin. C'est purement du classement visuel.</p>
        <ul>
            <li><strong>Créer</strong> un dossier (ex. « Accueil », « Équipe », « Produits »), puis y
                déposer des images.</li>
            <li><strong>Filtrer</strong> : cliquez un dossier pour n'afficher que ses images.</li>
            <li><strong>Déplacer</strong> une image d'un dossier à l'autre ne change <em>ni son adresse,
                ni les pages qui l'utilisent</em> : tout est référencé par identifiant.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — dossiers</span>
            <div class="demo-folders" aria-hidden="true">
                <span class="demo-folder">📁 Accueil</span>
                <span class="demo-folder">📁 Équipe</span>
                <span class="demo-folder">📁 Produits</span>
            </div>
        </div>

        <h3>Recadrer (crop)</h3>
        <p>Le recadrage permet de <strong>découper</strong> une image : garder un visage, obtenir un
        format carré pour une vignette, retirer un bord… Vous tracez sur l'image la zone à conserver,
        et Flow en produit une <strong>nouvelle version</strong>.</p>
        <p>En haut, une barre de <strong>formats</strong> permet de verrouiller les proportions de la zone
        avant de la tracer :</p>
        <ul>
            <li><strong>Libre</strong> — aucune contrainte, vous dessinez la zone à main levée.</li>
            <li><strong>Bannière 16:5</strong> — un bandeau large et court, idéal en haut de page.</li>
            <li><strong>Paysage 3:2</strong> — le format photo classique, plus large que haut.</li>
            <li><strong>Carré 1:1</strong> — parfait pour une vignette ou un portrait.</li>
        </ul>
        <ul>
            <li><strong>L'original est toujours préservé</strong> : un crop ne l'écrase jamais, on peut
                revenir en arrière.</li>
            <li>Après recadrage, toutes les largeurs (mobile, tablette, ordinateur + WebP) sont
                <strong>régénérées</strong> à partir du résultat.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — zone de recadrage</span>
            <div class="demo-crop" aria-hidden="true"><span class="demo-crop-sel"></span></div>
        </div>

        <p class="muted">Rappel : les pages pointent vers l'image par identifiant, jamais par URL — déplacer
        ou régénérer une image ne casse donc aucune page.</p>
HTML],

    'documents' => ['Les documents', <<<HTML
        <h2>Les documents</h2>
        <p>Menu <strong>Documents</strong> : la bibliothèque des fichiers à proposer sur le site —
        <strong>PDF, Word, Excel, PowerPoint, OpenDocument, texte</strong> (plaquettes, formulaires,
        menus, comptes rendus…). On les téléverse ici une fois, puis on réutilise leur lien partout.</p>

        <h3>Téléverser</h3>
        <p>Glissez <strong>un ou plusieurs fichiers d'un coup</strong> sur la zone d'envoi (ou cliquez-la
        pour les choisir). Chaque fichier s'envoie avec sa propre <strong>barre de progression</strong>.
        Si un fichier est refusé (type non autorisé ou trop lourd, 20 Mo max), il passe en rouge sans
        bloquer les autres.</p>

        <div class="demo">
            <span class="demo-tag">Aperçu — envoi</span>
            <div class="demo-drop" aria-hidden="true">⤓ Glissez vos documents ici, ou cliquez pour téléverser</div>
        </div>

        <h3>Réutiliser un document</h3>
        <p>Chaque document a un bouton <strong>« Copier le lien »</strong> : il copie son adresse, que vous
        collez ensuite dans un <strong>lien de texte</strong> d'une page, un bouton ou un e-mail. Le
        <strong>libellé</strong> sert d'intitulé clair (« Plaquette 2026 ») indépendant du nom du fichier.</p>

        <div class="demo">
            <span class="demo-tag">Aperçu — une carte document</span>
            <div class="demo-bar" aria-hidden="true">
                <span class="demo-pill">Ouvrir</span>
                <span class="demo-pill">Copier le lien</span>
            </div>
        </div>

        <h3>Les dossiers</h3>
        <p>Comme pour les médias, les dossiers servent à <strong>ranger</strong> la bibliothèque par thème.
        Déplacer un document d'un dossier à l'autre <em>ne change pas son lien</em> : les pages qui
        l'utilisent continuent de fonctionner.</p>

        <p class="muted">Sécurité : Flow vérifie le type réel de chaque fichier et l'enregistre sous un nom
        assaini. Seuls les types bureautiques de la liste sont acceptés.</p>
HTML],

    'users' => ['Les utilisateurs', <<<HTML
        <h2>Les utilisateurs</h2>
        <p>Menu <strong>Utilisateurs</strong> (selon vos droits) : création des comptes et attribution
        des rôles.</p>

        <ul>
            <li><strong>Rôles</strong> — chaque compte a un rôle qui détermine ses permissions
                (éditer du contenu, publier, téléverser des médias, gérer les comptes).</li>
            <li><strong>Bonne pratique</strong> — un rédacteur écrit sans publier ; un référent gère le
                contenu et les médias ; l'administrateur a tout accès.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — fiche utilisateur</span>
            <label>Nom</label>
            <input type="text" value="Jeanne Dupont" disabled aria-hidden="true" tabindex="-1">
            <label>Adresse e-mail</label>
            <input type="email" value="jeanne@exemple.fr" disabled aria-hidden="true" tabindex="-1">
            <label>Rôle</label>
            <select disabled aria-hidden="true" tabindex="-1">
                <option>Rédacteur</option>
            </select>
        </div>
HTML],

    'maintenance' => ['La maintenance', <<<HTML
        <h2>Le mode maintenance</h2>
        <p>Menu <strong>Maintenance</strong> (réservé à l'administrateur) : permet de
        <strong>fermer temporairement le site public</strong> — pour une mise à jour, une refonte
        en cours… Pendant ce temps, chaque visiteur voit une <strong>page « Site en maintenance »</strong>
        au lieu du site. <strong>L'administration, elle, reste toujours accessible</strong> : vous
        continuez à travailler normalement.</p>

        <h3>Activer / désactiver</h3>
        <p>Un simple bouton ouvre ou ferme le site. Un indicateur rappelle l'état courant
        (<em>Site en ligne</em> ou <em>Site fermé</em>).</p>

        <div class="demo">
            <span class="demo-tag">Aperçu — état du site</span>
            <div class="demo-toggle" aria-hidden="true">
                <span>Site fermé</span><span class="on">Site en ligne</span>
            </div>
        </div>

        <h3>L'URL de contournement (bypass)</h3>
        <p>Pour voir le site malgré la maintenance (vous, un client, un prestataire), Flow fournit une
        <strong>URL secrète</strong> contenant un code (un « hash »). L'ouvrir vous laisse passer
        <strong>et ajoute automatiquement votre adresse IP</strong> à la liste des accès autorisés :
        vous n'avez plus besoin du lien ensuite, depuis la même connexion.</p>
        <ul>
            <li><strong>À partager avec précaution</strong> — toute personne ayant l'URL peut voir le
                site fermé.</li>
            <li><strong>Régénérer le code</strong> — un bouton crée une nouvelle URL et
                <em>désactive l'ancienne</em>, si le lien a fuité ou par sécurité.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — URL de bypass</span>
            <div class="demo-bar" aria-hidden="true">
                <span class="demo-pill">https://monsite.fr/?acces=2f9c…&nbsp;a47b</span>
            </div>
        </div>

        <h3>Les adresses IP autorisées</h3>
        <p>La liste des <strong>adresses IP</strong> qui contournent la maintenance se remplit toute
        seule à chaque utilisation de l'URL de bypass. Vous pouvez aussi en
        <strong>ajouter une à la main</strong> (l'IP du bureau, par exemple — votre IP actuelle est
        pré-remplie pour aller plus vite) ou en <strong>retirer</strong> une.</p>

        <div class="demo">
            <span class="demo-tag">Aperçu — adresses autorisées</span>
            <div class="demo-bar" aria-hidden="true">
                <span class="demo-pill">203.0.113.4 · Retirer</span>
                <span class="demo-pill">+ Ajouter une IP</span>
            </div>
        </div>

        <p class="muted">Astuce : pensez à <strong>désactiver la maintenance</strong> une fois vos
        travaux terminés — sinon vos visiteurs (dont l'IP n'est pas listée) continueront de voir la
        page de fermeture.</p>
HTML],

    'demo' => ['Mode démonstration', <<<HTML
        <h2>Le mode démonstration</h2>
        <p>Carte <strong>Mode démonstration</strong> de l'onglet <strong>Administration</strong>
        (réservé à l'administrateur) : crée en un clic un <strong>compte de démonstration</strong> et
        quelques <strong>données factices</strong>, pratique pour faire visiter le CMS à un prospect ou
        un client sans exposer le vrai contenu du site.</p>

        <h3>Activer</h3>
        <p>Le bouton <strong>Activer le mode démonstration</strong> crée le compte et sème des exemples
        (pages, événements, actualités, réalisations, demandes de devis et de contact) dans les modules
        actuellement actifs — <strong>jamais de photo factice</strong>. L'e-mail et le mot de passe du
        compte s'affichent <strong>une seule fois, immédiatement après l'activation</strong> : notez-les
        tout de suite, ils ne réapparaîtront plus ensuite.</p>

        <div class="demo">
            <span class="demo-tag">Aperçu — identifiants affichés à l'activation</span>
            <div class="demo-bar" aria-hidden="true">
                <span class="demo-pill">demo@flow.local</span>
                <span class="demo-pill">•••••••••••• (mot de passe)</span>
            </div>
        </div>

        <h3>Ce que peut faire le compte démo</h3>
        <p>Un rôle dédié, volontairement limité : il peut <strong>créer, modifier et publier</strong> du
        contenu, et consulter les demandes de devis/contact ainsi que les statistiques. Il ne peut
        <strong>rien supprimer</strong>, et n'a accès ni aux utilisateurs, ni à l'onglet Administration,
        ni à la Maintenance — impossible de casser le site ou d'en changer les réglages.</p>

        <h3>Désactiver</h3>
        <p>Le bouton <strong>Désactiver le mode démonstration</strong> supprime intégralement le compte
        <strong>et</strong> toutes les données factices créées à l'activation, en une seule fois. Vos
        propres pages, événements, actualités, réalisations et demandes reçues ne sont
        <strong>jamais concernés</strong> : seul ce qui a été semé par le mode démo est retiré.</p>

        <p class="muted">Astuce : si vous réactivez le mode démo plus tard, un nouveau compte et de
        nouvelles données factices sont créés, avec de nouveaux identifiants.</p>
HTML],

    'stats' => ['Les statistiques', <<<HTML
        <h2>Les statistiques de visite</h2>
        <p>Menu <strong>Statistiques</strong> : un tableau de bord de la fréquentation de votre site
        public — combien de pages sont vues, par combien de visiteurs, d'où ils viennent et avec quel
        type d'appareil. Le tout <strong>respectueux de la vie privée</strong> : aucun cookie, aucun
        service tiers (pas de Google Analytics), donc <strong>aucun bandeau de consentement</strong> à
        afficher.</p>

        <h3>Ce que vous voyez</h3>
        <ul>
            <li><strong>Pages vues</strong> et <strong>visiteurs uniques</strong> sur la période choisie ;</li>
            <li><strong>Mois en cours</strong> : un graphique jour par jour, du 1er à aujourd'hui ;</li>
            <li><strong>Fréquentation par jour</strong> sur la période (7 jours, 30 jours, 90 jours, 12 mois) ;</li>
            <li><strong>Pages les plus vues</strong> et <strong>sources de trafic</strong> (sites qui vous envoient des visiteurs) ;</li>
            <li><strong>Appareils</strong> : part d'ordinateurs, mobiles et tablettes.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — période</span>
            <div class="demo-bar" aria-hidden="true">
                <span class="demo-pill">7 jours</span>
                <span class="demo-pill">30 jours</span>
                <span class="demo-pill">90 jours</span>
                <span class="demo-pill">12 mois</span>
            </div>
        </div>

        <h3>Qui est compté ?</h3>
        <p>Seuls vos <strong>vrais visiteurs</strong>. Sont automatiquement écartés : les
        <strong>robots</strong> (Google, réseaux sociaux, outils d'analyse…) et <strong>vous-même</strong>
        lorsque vous êtes connecté à l'administration. Vous pouvez aussi faire exclure l'adresse de votre
        bureau (réglage technique, côté Sud Web Studio).</p>

        <h3>Visiteurs uniques : une estimation honnête</h3>
        <p>Faute de cookie de pistage, le décompte des visiteurs uniques est <strong>volontairement
        approximatif</strong> : il reconnaît un visiteur au cours d'une même journée, mais repart de zéro
        le lendemain (impossible de vous suivre dans le temps). C'est le prix — assumé — du respect de la
        vie privée, et c'est largement suffisant pour suivre les tendances.</p>

        <p class="muted">Confidentialité : aucune donnée permettant d'identifier une personne n'est
        conservée (ni adresse IP en clair, ni navigateur). Les statistiques sont
        <strong>effacées automatiquement</strong> au-delà de quelques mois.</p>
HTML],

    'devis' => ['Les devis', <<<HTML
        <h2>Les demandes de devis</h2>
        <p>Vos visiteurs peuvent demander un devis via un <strong>pop-up</strong> qui s'ouvre depuis le
        bouton <em>« Demander un devis »</em> (dans le menu du site et en bouton flottant, sur toutes les
        pages). Le formulaire se remplit <strong>étape par étape</strong>. Chaque demande arrive dans le
        menu <strong>Demandes de devis</strong> et vous est <strong>notifiée par e-mail</strong>.</p>

        <div class="demo">
            <span class="demo-tag">Aperçu — un parcours en étapes</span>
            <div class="demo-bar" aria-hidden="true">
                <span class="demo-pill">1 · Projet</span>
                <span class="demo-pill">2 · Détails</span>
                <span class="demo-pill">3 · Coordonnées</span>
            </div>
        </div>

        <h3>Gérer les demandes</h3>
        <ul>
            <li>la <strong>liste</strong> affiche le nom, l'e-mail, un aperçu et la date de chaque demande ;</li>
            <li>en ouvrant une demande, vous voyez <strong>toutes les réponses</strong> et pouvez
            <strong>répondre par e-mail</strong> d'un clic ;</li>
            <li>chaque demande a un <strong>statut</strong> : nouvelle, lue, <em>devis envoyé</em> ou archivée ;</li>
            <li>une demande peut être <strong>supprimée</strong> à tout moment (RGPD).</li>
        </ul>

        <h3>Composer le formulaire vous-même</h3>
        <p>Bouton <strong>« Configurer le formulaire »</strong> (en haut de la liste) : vous y construisez
        le pop-up <strong>sans toucher au code</strong>.</p>
        <ul>
            <li><strong>Ajoutez des étapes</strong> et donnez-leur un titre ;</li>
            <li>dans chaque étape, <strong>ajoutez des questions</strong> : texte court, e-mail, téléphone,
            zone de texte ou <strong>liste de choix</strong> (vous saisissez alors les options, une par ligne) ;</li>
            <li>indiquez si une réponse est <strong>obligatoire</strong>, et réordonnez le tout avec les
            flèches ;</li>
            <li>cliquez sur <strong>Enregistrer</strong> : le pop-up du site se met à jour aussitôt.</li>
        </ul>

        <div class="demo">
            <span class="demo-tag">Aperçu — une question « liste de choix »</span>
            <div class="demo-bar" aria-hidden="true">
                <span class="demo-pill">Type de projet : Site vitrine ▾</span>
            </div>
        </div>

        <p class="muted">Les questions <strong>Nom</strong> et <strong>E-mail</strong> sont indispensables
        (elles servent à vous recontacter) : elles restent toujours présentes. Pas d'inquiétude pour le
        spam : protection maison intégrée, <strong>sans cookie ni service tiers</strong>.</p>
HTML],
];

/**
 * Module dont dépend chaque rubrique. Une rubrique n'apparaît que si son module
 * est actif (désactivable depuis l'onglet Administration). Les rubriques de
 * niveau Core (tableau de bord) et Users (toujours actif) n'ont pas de
 * dépendance et restent toujours visibles.
 */
$sectionModule = [
    'pages'          => 'Pages',
    'agenda'         => 'Agenda',
    'actualites'     => 'Actualites',
    'realisations'   => 'Realisations',
    'sliders'        => 'Sliders',
    'legal_pages'    => 'LegalPages',
    'google_reviews' => 'GoogleReviews',
    'horaires'       => 'Horaires',
    'devis'          => 'Devis',
    'media'          => 'Media',
    'documents'      => 'Documents',
    'stats'          => 'Stats',
];
$sections = array_filter(
    $sections,
    static fn (string $id): bool =>
        !isset($sectionModule[$id]) || in_array($sectionModule[$id], $enabledModules, true),
    ARRAY_FILTER_USE_KEY,
);

// Rubriques Maintenance et Mode démonstration : transverses (pas des modules), réservées à l'admin.
if (!($canMaintenance ?? false)) {
    unset($sections['maintenance']);
}
if (!($canAdmin ?? false)) {
    unset($sections['demo']);
}

$first = array_key_first($sections);
?>
<div class="tuto-head">
    <div class="tuto-head-text">
        <h1>Tutoriel</h1>
        <p class="muted">Choisissez une rubrique à gauche pour afficher son guide.</p>
    </div>
    <a class="tuto-support" href="mailto:contact@sudweb-studio.com?subject=<?= e(rawurlencode('Problème sur Flow CMS')) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/><line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/></svg>
        Un problème ? Contacter le support
    </a>
</div>

<div class="tuto-layout">
    <nav class="tuto-nav" data-tuto-nav aria-label="Rubriques du tutoriel">
        <?php foreach ($sections as $id => [$label, $_]): ?>
            <button type="button" class="tuto-tab<?= $id === $first ? ' active' : '' ?>" data-tab="<?= e($id) ?>">
                <?= e($label) ?>
            </button>
        <?php endforeach; ?>
    </nav>

    <div class="tuto-panels">
        <?php foreach ($sections as $id => [$_, $html]): ?>
            <section class="card tuto-panel<?= $id === $first ? ' active' : '' ?>" data-panel="<?= e($id) ?>">
                <?= $html ?>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .tuto-head { margin-bottom:1.6rem; display:flex; justify-content:space-between;
                 align-items:center; gap:1rem; flex-wrap:wrap; }
    .tuto-head h1 { margin:0 0 .35rem; }
    .tuto-head .muted { color:var(--muted); margin:0; }
    /* Bouton support (orange) — même action que sur le tableau de bord. */
    .tuto-support { display:inline-flex; align-items:center; gap:.5rem; padding:.6rem 1rem;
                    border-radius:11px; text-decoration:none; font-weight:600; font-size:.9rem;
                    color:#fff; background:linear-gradient(135deg,#ff8a73 0%,#ff6b4a 100%);
                    box-shadow:var(--shadow-sm); white-space:nowrap; }
    .tuto-support:hover { filter:brightness(1.05); }
    .tuto-support svg { width:18px; height:18px; }

    .tuto-layout { display:flex; gap:1.4rem; align-items:flex-start; }
    .tuto-nav { display:flex; flex-direction:column; gap:.3rem; flex:0 0 230px; position:sticky; top:1rem; }
    /* justify-content:flex-start surcharge le centrage flex global des <button>. */
    .tuto-tab { margin:0; justify-content:flex-start; text-align:left; padding:.7rem .9rem;
                border-radius:11px; background:transparent;
                color:var(--muted); font-weight:600; font-size:.92rem; box-shadow:none;
                transition:background .15s, color .15s; }
    .tuto-tab:hover { background:var(--bg); color:var(--txt); filter:none; box-shadow:none; }
    .tuto-tab.active { background:var(--accent-soft); color:var(--accent-600); }

    .tuto-panels { flex:1; min-width:0; }
    .tuto-panel { display:none; }
    .tuto-panel.active { display:block; }
    .tuto-panel h2 { margin:0 0 .8rem; font-size:1.15rem; }
    .tuto-panel h3 { margin:1.5rem 0 .5rem; font-size:1rem; color:var(--accent-600); }
    .tuto-panel p { margin:0 0 .8rem; line-height:1.6; }
    .tuto-panel p:last-child { margin-bottom:0; }
    .tuto-panel ul { margin:.2rem 0 .9rem; padding-left:1.2rem; }
    .tuto-panel li { margin:.4rem 0; line-height:1.55; }
    .tuto-panel code { background:var(--bg); padding:.1rem .4rem; border-radius:5px; font-size:.85em; }
    .tuto-panel a { color:var(--accent-600); }

    /* Aperçus illustratifs (champs factices). */
    .demo { margin:1.1rem 0; padding:1rem 1.1rem; border:1px dashed #cfd3e6; border-radius:13px; background:var(--bg); }
    .demo-tag { display:inline-block; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
                color:var(--muted); background:#fff; border:1px solid var(--line); border-radius:6px;
                padding:.15rem .5rem; margin-bottom:.8rem; }
    .demo label { font-size:.8rem; }
    .demo input:disabled, .demo select:disabled { background:#fff; opacity:1; color:var(--txt); cursor:default; -webkit-text-fill-color:var(--txt); }
    .demo-toggle { display:inline-flex; gap:.3rem; padding:.25rem; background:#fff; border:1px solid var(--line); border-radius:11px; }
    .demo-toggle span { padding:.4rem .95rem; border-radius:8px; font-weight:600; font-size:.82rem; color:var(--muted); }
    .demo-toggle span.on { background:#e7f6ec; color:#1c6b3a; }
    .demo-bar { display:flex; gap:.4rem; flex-wrap:wrap; }
    .demo-pill { padding:.35rem .75rem; border-radius:8px; background:var(--accent-soft); color:var(--accent-600);
                 font-size:.82rem; font-weight:600; }
    .demo-cards { display:flex; gap:.5rem; flex-wrap:wrap; }
    .demo-card { padding:.55rem .9rem; border-radius:11px; background:#fff; border:1px solid var(--line);
                 font-size:.85rem; font-weight:600; }
    .demo-drop { border:1.5px dashed #cfd3e6; border-radius:12px; padding:1.1rem; text-align:center;
                 color:var(--muted); background:#fff; font-size:.85rem; }
    .demo-folders { display:flex; gap:.45rem; flex-wrap:wrap; }
    .demo-folder { padding:.3rem .75rem; border-radius:20px; background:#fff; border:1px solid var(--line); font-size:.8rem; }
    /* Aperçu agenda : dates côte à côte + vignette. */
    .demo-dates { display:grid; grid-template-columns:1fr 1fr; gap:.8rem; margin:.2rem 0 .3rem; }
    .demo-thumb { display:flex; align-items:center; gap:.7rem; }
    .demo-thumb-box { display:grid; place-items:center; width:54px; height:54px; font-size:1.4rem;
                      background:#fff; border:1px solid var(--line); border-radius:9px; }
    @media (max-width:520px) { .demo-dates { grid-template-columns:1fr; } }
    /* Aperçu d'une ligne en colonnes. */
    .demo-row { display:grid; grid-template-columns:1fr 1fr; gap:.6rem; }
    .demo-col { display:flex; flex-direction:column; gap:.5rem; padding:.6rem; background:#fff;
                border:1px dashed var(--line); border-radius:9px; min-height:60px; }
    .demo-block { padding:.5rem .7rem; border-radius:7px; background:var(--accent-soft); color:var(--accent-600);
                  font-size:.82rem; font-weight:600; text-align:center; }
    /* Aperçu avis Google. */
    .demo-review { background:#fff; border:1px solid var(--line); border-radius:11px; padding:.8rem .9rem; }
    .demo-review p { margin:.5rem 0 0; font-size:.88rem; color:var(--txt); }
    .demo-review-head { display:flex; align-items:center; gap:.6rem; }
    .demo-stars { color:#f5a623; letter-spacing:.05em; }
    .demo-chip { margin-left:auto; font-size:.72rem; font-weight:700; padding:.15rem .55rem; border-radius:6px;
                 background:var(--bg); color:var(--muted); }
    .demo-chip.on { background:#e7f6ec; color:#1c6b3a; }
    /* Aperçu de recadrage. */
    .demo-crop { position:relative; height:150px; border-radius:10px; overflow:hidden;
                 background:linear-gradient(135deg, #c7d2fe 0%, #a5b4fc 50%, #818cf8 100%); }
    .demo-crop-sel { position:absolute; left:22%; top:18%; width:46%; height:58%;
                     border:2px dashed #fff; border-radius:4px; box-shadow:0 0 0 9999px rgba(11,13,18,.32); }

    @media (max-width:760px) {
        .tuto-layout { flex-direction:column; }
        .tuto-nav { flex:1 1 auto; width:100%; position:static; flex-direction:row; flex-wrap:wrap; }
    }
</style>

<script src="<?= e($base . '/assets/js/tutorial.js') ?>"></script>
