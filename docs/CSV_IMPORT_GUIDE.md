# 📥 Guide d'Import CSV - Catalogue FS.com

Ce guide explique comment utiliser le système d'import CSV pour gérer le catalogue des produits FS.com.

## 🚀 Démarrage Rapide

```bash
# 1. Générer un template CSV
php artisan fscom:template

# 2. Éditer le fichier fscom_template.csv avec vos produits

# 3. Tester l'import (dry-run)
php artisan fscom:import fscom_template.csv --dry-run

# 4. Importer les produits
php artisan fscom:import fscom_template.csv
```

---

## 📋 Commandes Disponibles

### 1. `fscom:template` - Générer un template CSV

Crée un fichier CSV d'exemple avec la structure correcte et 2 produits exemples.

```bash
# Générer avec nom par défaut (fscom_template.csv)
php artisan fscom:template

# Générer avec nom personnalisé
php artisan fscom:template mon_catalogue.csv
```

**Sortie :**
- Fichier CSV avec headers
- 2 lignes d'exemple (1 GBIC + 1 Patch Cord)
- Instructions d'utilisation

---

### 2. `fscom:import` - Importer des produits

Importe les produits depuis un fichier CSV dans la base de données.

```bash
# Import normal
php artisan fscom:import catalogue.csv

# Mode dry-run (prévisualisation sans enregistrer)
php artisan fscom:import catalogue.csv --dry-run

# Arrêter à la première erreur
php artisan fscom:import catalogue.csv --stop-on-error

# Afficher toutes les erreurs
php artisan fscom:import catalogue.csv --show-errors
```

**Options :**
- `--dry-run` : Teste l'import sans modifier la BDD
- `--stop-on-error` : Arrête l'import à la première erreur (par défaut : skip)
- `--show-errors` : Affiche le détail des erreurs rencontrées

**Comportement :**
- Si le SKU existe déjà → **mise à jour** du produit
- Si le SKU est nouveau → **création** du produit
- Les erreurs sont logguées dans `storage/logs/laravel.log`

---

### 3. `fscom:export` - Exporter les produits

Exporte les produits existants vers un fichier CSV.

```bash
# Export complet
php artisan fscom:export

# Export avec nom personnalisé
php artisan fscom:export mon_export.csv

# Export d'une catégorie spécifique
php artisan fscom:export --category=gbic
php artisan fscom:export --category=patch_cord
```

**Cas d'usage :**
- Backup du catalogue
- Modification en masse (export → edit → import)
- Partage du catalogue

---

## 📝 Format du CSV

### Structure du fichier

Le fichier CSV doit contenir les colonnes suivantes (dans cet ordre) :

| Colonne          | Type    | Requis | Description                                    |
|------------------|---------|--------|------------------------------------------------|
| `sku`            | string  | ✅     | Identifiant unique du produit                  |
| `name`           | string  | ✅     | Nom du produit                                 |
| `category`       | enum    | ✅     | `gbic` ou `patch_cord`                         |
| `description`    | string  | ❌     | Description du produit                         |
| `specifications` | JSON    | ❌     | Spécifications techniques (JSON ou texte)      |
| `price`          | decimal | ❌     | Prix du produit                                |
| `currency`       | string  | ❌     | Code devise (USD, EUR, etc.) - défaut: USD     |
| `url`            | string  | ❌     | URL de la page produit sur FS.com              |
| `image_url`      | string  | ❌     | URL de l'image du produit                      |

### Exemple de CSV

```csv
sku,name,category,description,specifications,price,currency,url,image_url
SFP-10G-SR,10GBASE-SR SFP+ Transceiver,gbic,SFP+ 10G 850nm 300m,"{""wavelength"":""850nm"",""distance"":""300m""}",15.00,USD,https://www.fs.com/fr/products/11774.html,https://img-en.fs.com/file/user_manual/sfp.jpg
LC-LC-OM3-1M,LC to LC OM3 Patch Cable,patch_cord,LC/UPC Duplex OM3 1m,"{""length"":""1m"",""fiber_type"":""OM3""}",3.50,USD,https://www.fs.com/fr/products/40197.html,https://img-en.fs.com/file/user_manual/patch.jpg
```

---

## 🎯 Exemples d'Utilisation

### Cas 1 : Premier Import

```bash
# 1. Créer le template
php artisan fscom:template catalogue_gbics.csv

# 2. Éditer le fichier et ajouter vos GBICs

# 3. Tester en dry-run
php artisan fscom:import catalogue_gbics.csv --dry-run

# 4. Importer pour de vrai
php artisan fscom:import catalogue_gbics.csv
```

### Cas 2 : Mise à Jour en Masse

```bash
# 1. Exporter le catalogue actuel
php artisan fscom:export current_catalogue.csv

# 2. Éditer les prix ou descriptions

# 3. Ré-importer (les SKU existants seront mis à jour)
php artisan fscom:import current_catalogue.csv
```

### Cas 3 : Import avec Gestion d'Erreurs

```bash
# Import avec affichage des erreurs détaillées
php artisan fscom:import catalogue.csv --show-errors

# Si trop d'erreurs, arrêter à la première
php artisan fscom:import catalogue.csv --stop-on-error
```

---

## ✅ Validation des Données

Le système valide automatiquement :

| Règle                           | Erreur si...                                    |
|---------------------------------|-------------------------------------------------|
| **SKU requis**                  | Colonne `sku` vide                              |
| **Nom requis**                  | Colonne `name` vide                             |
| **Catégorie valide**            | Autre chose que `gbic` ou `patch_cord`          |
| **Prix numérique**              | Prix non numérique (ex: "ABC")                  |
| **URL valide**                  | Format d'URL invalide                           |
| **JSON specifications**         | Si JSON, doit être valide (sinon traité comme texte) |

**En cas d'erreur de validation :**
- Par défaut : la ligne est **skippée** et l'import continue
- Avec `--stop-on-error` : l'import **s'arrête** immédiatement
- Toutes les erreurs sont **loggées** dans les logs Laravel

---

## 🔧 Spécifications Techniques (JSON)

Le champ `specifications` peut contenir du JSON structuré ou du texte simple.

### Format JSON (Recommandé)

**Pour les GBICs :**
```json
{
  "wavelength": "850nm",
  "distance": "300m",
  "connector": "LC",
  "fiber_type": "MMF",
  "data_rate": "10G",
  "temperature": "0~70°C"
}
```

**Pour les Patch Cords :**
```json
{
  "length": "1m",
  "connector_a": "LC/UPC",
  "connector_b": "LC/UPC",
  "fiber_type": "OM3",
  "jacket_color": "Aqua"
}
```

### Format Texte

Si vous ne voulez pas utiliser JSON, un simple texte fonctionne aussi :
```
850nm, 300m, LC Duplex, MMF
```

---

## 📊 Statistiques d'Import

Après chaque import, vous obtenez un rapport détaillé :

```
Import Results
───────────────────────────────────────
| Total records processed | 50   |
| Products created        | 35   |  ← Nouveaux produits
| Products updated        | 10   |  ← Produits existants mis à jour
| Records skipped         | 0    |
| Errors                  | 5    |  ← Lignes en erreur
```

---

## 🐛 Dépannage

### Erreur : "File not found"

```bash
# Utiliser un chemin absolu ou relatif valide
php artisan fscom:import /chemin/complet/vers/catalogue.csv
```

### Erreur : "Category must be either gbic or patch_cord"

Vérifiez que la colonne `category` contient exactement :
- `gbic` (minuscules)
- `patch_cord` (minuscules, avec underscore)

### Erreur : "SKU is required"

Chaque ligne doit avoir un SKU unique et non vide.

### Voir les logs détaillés

```bash
tail -f storage/logs/laravel.log
```

---

## 🔄 Workflow Recommandé

### Mise en Place Initiale

1. **Récupérer catalogue FS.com** (export depuis leur site ou saisie manuelle)
2. **Générer template** : `php artisan fscom:template`
3. **Copier/coller vos données** dans le CSV
4. **Valider en dry-run** : `--dry-run`
5. **Importer** : `php artisan fscom:import`

### Maintenance Régulière

1. **Exporter** l'existant : `php artisan fscom:export`
2. **Modifier** le CSV (prix, nouveaux produits, etc.)
3. **Ré-importer** : `php artisan fscom:import`

### Backup Avant Import Important

```bash
# Exporter avant modification majeure
php artisan fscom:export backup_$(date +%Y%m%d).csv

# Faire l'import
php artisan fscom:import nouveau_catalogue.csv

# En cas de problème, restaurer le backup
php artisan fscom:import backup_20251105.csv
```

---

## 💡 Bonnes Pratiques

1. **Toujours tester en dry-run** avant un import réel
2. **Utiliser des SKU cohérents** (ex: `SFP-10G-SR`, `LC-LC-OM3-1M`)
3. **Sauvegarder régulièrement** via export
4. **Documenter les specs en JSON** pour faciliter les recherches futures
5. **Valider les URLs** avant import (éviter les 404)
6. **Utiliser UTF-8** pour l'encodage du CSV (accents, caractères spéciaux)

---

## 📚 Ressources

- **Logs** : `storage/logs/laravel.log`
- **Config scraper** : `config/fscom.php`
- **Service d'import** : `app/Services/FsComImportService.php`
- **Commandes** : `app/Console/Commands/`

---

## 🆘 Support

En cas de problème :
1. Vérifier les logs Laravel
2. Tester en `--dry-run` avec `--show-errors`
3. Valider le format du CSV (encoding UTF-8, colonnes correctes)
4. Vérifier les permissions d'écriture sur les fichiers

---

**Version:** 1.0
**Dernière mise à jour:** 2025-11-05
