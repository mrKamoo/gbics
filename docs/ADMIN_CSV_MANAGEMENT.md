# 📋 Administration - FS.com Catalog CSV Management

Cette fonctionnalité permet aux administrateurs de gérer le catalogue FS.com via une interface web complète avec import/export CSV.

---

## 🎯 Fonctionnalités

### Interface Web d'Administration

L'interface se trouve à `/admin/fscom-catalog` et offre :

1. **Dashboard avec statistiques**
   - Nombre total de produits
   - Nombre de GBICs
   - Nombre de Patch Cords
   - Date de dernière synchronisation

2. **Gestion des produits**
   - Liste paginée avec recherche et filtres
   - Tri par colonnes
   - Sélection multiple pour suppression en masse
   - Vue détaillée de chaque produit

3. **Import CSV (workflow en 3 étapes)**
   - **Étape 1** : Sélection du fichier CSV (max 10MB)
   - **Étape 2** : Validation automatique avec rapport d'erreurs
   - **Étape 3** : Import avec statistiques (créés/mis à jour/erreurs)

4. **Export CSV**
   - Export complet ou par catégorie (GBIC/Patch Cord)
   - Génération de fichiers téléchargeables

5. **Génération de template**
   - Crée un fichier CSV d'exemple avec 2 produits
   - Prêt à être modifié et importé

---

## 🔌 API Endpoints

### Routes disponibles (nécessite rôle admin ou super_admin)

```
GET    /api/admin/fscom-catalog              # Liste des produits (paginée)
GET    /api/admin/fscom-catalog/stats        # Statistiques du catalogue
POST   /api/admin/fscom-catalog/template     # Générer un template CSV
POST   /api/admin/fscom-catalog/validate     # Valider un CSV avant import
POST   /api/admin/fscom-catalog/import       # Importer des produits
POST   /api/admin/fscom-catalog/export       # Exporter des produits
DELETE /api/admin/fscom-catalog/{id}         # Supprimer un produit
POST   /api/admin/fscom-catalog/bulk-delete  # Supprimer plusieurs produits
```

---

## 📄 Format du CSV

### Colonnes requises

| Colonne          | Type    | Obligatoire | Description                          |
|------------------|---------|-------------|--------------------------------------|
| `sku`            | string  | ✅          | Identifiant unique (ex: SFP-10G-SR)  |
| `name`           | string  | ✅          | Nom du produit                       |
| `category`       | enum    | ✅          | `gbic` ou `patch_cord`               |
| `description`    | string  | ❌          | Description textuelle                |
| `specifications` | JSON    | ❌          | Specs techniques en JSON             |
| `price`          | decimal | ❌          | Prix (numérique)                     |
| `currency`       | string  | ❌          | Code devise (défaut: USD)            |
| `url`            | string  | ❌          | URL FS.com                           |
| `image_url`      | string  | ❌          | URL de l'image                       |

### Exemple de fichier CSV

```csv
sku,name,category,description,specifications,price,currency,url,image_url
SFP-10G-SR,"10GBASE-SR SFP+ Transceiver",gbic,"SFP+ 10G 850nm 300m","{\"wavelength\":\"850nm\",\"distance\":\"300m\"}",15.00,USD,https://www.fs.com/fr/products/11774.html,https://example.com/image.jpg
LC-LC-OM3-1M,"LC to LC OM3 Patch Cable",patch_cord,"LC/UPC Duplex OM3 1m","{\"length\":\"1m\",\"fiber_type\":\"OM3\"}",3.50,USD,https://www.fs.com/fr/products/40197.html,https://example.com/patch.jpg
```

### Format JSON des spécifications

**Pour les GBICs :**
```json
{
  "wavelength": "850nm",
  "distance": "300m",
  "connector": "LC",
  "fiber_type": "MMF",
  "data_rate": "10G"
}
```

**Pour les Patch Cords :**
```json
{
  "length": "1m",
  "connector_a": "LC/UPC",
  "connector_b": "LC/UPC",
  "fiber_type": "OM3"
}
```

---

## 🚀 Utilisation

### Workflow d'import depuis l'interface web

1. **Accéder à la page** : `/admin/fscom-catalog`
2. **Cliquer sur "Import CSV"**
3. **Sélectionner votre fichier** (.csv, max 10MB)
4. **Valider** : le système vérifie la syntaxe et affiche les erreurs éventuelles
5. **Importer** : les produits sont créés ou mis à jour (selon le SKU)
6. **Voir les résultats** : nombre de créations, mises à jour, erreurs

### Options d'import

- **Skip errors** (défaut) : continue l'import même en cas d'erreur sur une ligne
- **Stop on error** : arrête l'import à la première erreur rencontrée

### Export

1. **Sélectionner la catégorie** (optionnel) : All, GBICs, ou Patch Cords
2. **Cliquer sur "Export All"**
3. **Le fichier CSV se télécharge** automatiquement
4. **Utilisation** : backup, modification en masse, partage

### Génération de template

1. **Cliquer sur "Generate Template"**
2. **Un fichier CSV d'exemple** est créé et téléchargé
3. **Modifier le fichier** avec vos produits
4. **Importer** via le bouton "Import CSV"

---

## 🔧 Utilisation en ligne de commande

Les commandes Artisan restent disponibles pour les scripts et automatisations :

```bash
# Générer un template
php artisan fscom:template mon_catalogue.csv

# Tester un import (dry-run)
php artisan fscom:import catalogue.csv --dry-run

# Importer des produits
php artisan fscom:import catalogue.csv

# Importer avec arrêt sur erreur
php artisan fscom:import catalogue.csv --stop-on-error

# Afficher les erreurs détaillées
php artisan fscom:import catalogue.csv --show-errors

# Exporter tous les produits
php artisan fscom:export export.csv

# Exporter une catégorie spécifique
php artisan fscom:export gbics.csv --category=gbic
php artisan fscom:export patch_cords.csv --category=patch_cord
```

---

## 📊 Validation des données

### Règles appliquées

- **SKU** : requis, string max 255 caractères
- **Nom** : requis, string max 255 caractères
- **Catégorie** : requis, doit être `gbic` ou `patch_cord`
- **Prix** : optionnel, numérique >= 0
- **URL** : optionnel, format URL valide
- **Image URL** : optionnel, format URL valide
- **Specifications** : optionnel, JSON valide ou texte

### Gestion des erreurs

**En mode "Skip errors" (défaut) :**
- Les lignes en erreur sont ignorées
- L'import continue avec les lignes valides
- Les erreurs sont loggées dans `storage/logs/laravel.log`

**En mode "Stop on error" :**
- L'import s'arrête à la première erreur
- Aucune donnée n'est importée
- L'erreur est affichée à l'utilisateur

---

## 🔐 Permissions

- **Rôles requis** : `super_admin` ou `admin`
- **Middleware** : `role:super_admin|admin` sur toutes les routes
- **Frontend** : navigation masquée pour les utilisateurs sans droits

---

## 💾 Stockage des fichiers

### Import
- **Temporaire** : `storage/app/temp/` (supprimé après validation)
- **Historique** : `storage/app/imports/` (conservé)

### Export & Template
- **Public** : `storage/app/public/` (accessible via `/storage/`)
- **Nommage** : `fscom_export_YYYY-MM-DD_HHmmss.csv`
- **Nettoyage** : manuel (les fichiers ne sont pas supprimés automatiquement)

---

## 🎨 Interface utilisateur

### Composants Vue.js

- **Fichier principal** : `vue-frontend/src/views/admin/FsComCatalog.vue`
- **Route** : `/admin/fscom-catalog`
- **Navigation** : ajoutée dans le menu Admin (rôle requis)

### Fonctionnalités UI

1. **Cards de statistiques** : aperçu rapide du catalogue
2. **Barre d'actions** : Import, Export, Generate Template
3. **Filtres** : catégorie, recherche textuelle
4. **Tableau paginé** : 15 produits par page
5. **Tri** : cliquer sur les en-têtes de colonnes
6. **Sélection multiple** : checkbox + actions en masse
7. **Modales** :
   - Import wizard (3 étapes)
   - Détails produit (view)
8. **Feedback visuel** : loaders, alertes, messages de confirmation

---

## 📝 Backend

### Controller

**Fichier** : `app/Http/Controllers/Admin/FsComCatalogController.php`

**Méthodes principales** :
- `index()` : liste paginée avec filtres
- `stats()` : statistiques du catalogue
- `generateTemplate()` : crée un CSV d'exemple
- `validateCsv()` : validation en dry-run
- `import()` : import réel
- `export()` : export vers CSV
- `destroy()` : suppression unitaire
- `bulkDelete()` : suppression multiple

### Service

**Fichier** : `app/Services/FsComImportService.php`

**Méthodes clés** :
- `importFromCsv()` : logique d'import
- `validateRecord()` : validation des données
- `importRecord()` : upsert en BDD
- `exportToCsv()` : génération CSV
- `generateTemplate()` : création template

### Package utilisé

- **league/csv** (v9.27.1) : parsing et génération CSV
- Installation : `composer require league/csv`

---

## 🧪 Tests

### Tests manuels effectués

✅ Import CSV (2 produits)
✅ Export CSV (2 produits exportés)
✅ Génération template (fichier créé)
✅ Routes API (controller fonctionnel)
✅ Composant Vue.js (créé et configuré)
✅ Router (route ajoutée)

### Tests recommandés

- [ ] Import avec erreurs de validation
- [ ] Import de gros fichiers (1000+ lignes)
- [ ] Export avec filtres de catégorie
- [ ] Suppression en masse depuis l'UI
- [ ] Gestion des doublons (SKU existants)
- [ ] Upload de fichiers non-CSV
- [ ] Permissions (accès refusé pour users non-admin)

---

## 🔄 Workflow complet

### Scénario d'utilisation typique

1. **Admin se connecte** → accède à `/admin/fscom-catalog`
2. **Clique "Generate Template"** → télécharge `fscom_template_YYYY-MM-DD.csv`
3. **Édite le CSV** → ajoute 50 GBICs avec leurs specs
4. **Clique "Import CSV"** → sélectionne son fichier
5. **Validation automatique** → 48 OK, 2 erreurs (prix invalides)
6. **Corrige les erreurs** dans le CSV
7. **Ré-importe** → 50 produits créés ✅
8. **Vérifie dans le tableau** → filtre par catégorie "gbic"
9. **Exporte pour backup** → télécharge `fscom_export_YYYY-MM-DD.csv`

---

## 📚 Ressources

### Documentation

- Guide complet : `docs/CSV_IMPORT_GUIDE.md`
- Instructions projet : `CLAUDE.md`
- Ce fichier : `docs/ADMIN_CSV_MANAGEMENT.md`

### Code source

- **Backend** :
  - Controller : `app/Http/Controllers/Admin/FsComCatalogController.php`
  - Service : `app/Services/FsComImportService.php`
  - Routes : `routes/api.php` (ligne 51-61)
  - Model : `app/Models/FsComProduct.php`

- **Frontend** :
  - Composant : `vue-frontend/src/views/admin/FsComCatalog.vue`
  - Router : `vue-frontend/src/router/index.js` (ligne 80-84)
  - API Service : `vue-frontend/src/services/api.js`

### Logs

- **Laravel logs** : `storage/logs/laravel.log`
- **Import errors** : détails dans les logs

---

## ✨ Améliorations futures possibles

- [ ] Upload drag & drop
- [ ] Aperçu du CSV avant import (preview table)
- [ ] Historique des imports (date, user, stats)
- [ ] Validation en temps réel (JavaScript côté client)
- [ ] Export en XLSX (en plus du CSV)
- [ ] Import depuis URL
- [ ] Planification d'imports automatiques (cron)
- [ ] Comparaison avant/après (diff view)
- [ ] Rollback d'import
- [ ] Notifications email après import

---

**Version** : 1.0
**Date** : 2025-11-05
**Auteur** : Claude Code
