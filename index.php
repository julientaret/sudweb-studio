<?php
// Flow — racine du projet.
// L'admin (CMS) vit dans /flow-cms, le site public vit dans /frontend.
// Une requête sur la racine est redirigée vers le frontend.

header('Location: frontend/', true, 302);
exit;
