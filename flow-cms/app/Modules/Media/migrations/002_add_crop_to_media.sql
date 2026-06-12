-- Module Media — crop manuel non destructif (brief §5).
-- Le crop n'écrase jamais l'original : on stocke les coordonnées (en pixels de
-- l'original) et la régénération les ré-applique avant de produire WebP + presets.
-- NULL = pas de recadrage, on utilise l'original entier.
ALTER TABLE media
    ADD COLUMN crop_x INT UNSIGNED NULL DEFAULT NULL AFTER alt,
    ADD COLUMN crop_y INT UNSIGNED NULL DEFAULT NULL AFTER crop_x,
    ADD COLUMN crop_w INT UNSIGNED NULL DEFAULT NULL AFTER crop_y,
    ADD COLUMN crop_h INT UNSIGNED NULL DEFAULT NULL AFTER crop_w;
