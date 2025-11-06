# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

# 📋 APPLICATION GESTION ASSETS RÉSEAU

**Current Status**: Fresh Laravel 12 installation - Implementation in progress

---

## 🚀 QUICK START COMMANDS

### Initial Setup
```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Development server
composer run dev
# This runs: server (port 8000) + queue worker + logs + vite (hot reload)
```

### Daily Development
```bash
# Run development servers (all-in-one)
composer run dev

# Run tests
composer run test

# Code style (Laravel Pint)
./vendor/bin/pint

# Clear caches
php artisan optimize:clear
```

### Database & Migrations
```bash
# Run migrations
php artisan migrate

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Create migration
php artisan make:migration create_table_name

# Create model with migration
php artisan make:model ModelName -m
```

### Artisan Commands

#### FS.com Catalog Management (CSV Import - IMPLEMENTED ✅)
```bash
# Generate CSV template with examples
php artisan fscom:template [filename]

# Import products from CSV
php artisan fscom:import catalogue.csv [--dry-run] [--show-errors]

# Export products to CSV
php artisan fscom:export [filename] [--category=gbic|patch_cord]

# Test site access (for debugging scraper)
php artisan fscom:test [url]

# Sync FS.com catalog (web scraping - currently blocked by Cloudflare)
php artisan fscom:sync
```

**Note:** Web scraping is currently blocked by FS.com protections. Use CSV import instead (see `docs/CSV_IMPORT_GUIDE.md`).

**Admin Web Interface:** FS.com catalog can also be managed via the web interface at `/admin/fscom-catalog` (requires admin role). See `docs/ADMIN_CSV_MANAGEMENT.md` for details.

#### Other Commands (to be implemented)
```bash
# Check and generate alerts
php artisan alerts:check

# Seed roles and permissions
php artisan db:seed --class=RoleSeeder
```

### Environment Configuration
Key `.env` variables to configure:

```env
# Application
APP_NAME="Asset Manager"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (MySQL recommended)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=asset_manager
DB_USERNAME=root
DB_PASSWORD=

# Queue (database driver for development)
QUEUE_CONNECTION=database

# FS.com Scraping (to be implemented)
FSCOM_BASE_URL=https://www.fs.com/fr
FSCOM_SYNC_ENABLED=true
FSCOM_SYNC_SCHEDULE=daily

# Laravel Sanctum (for API auth)
SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000
```

### Testing
```bash
# Run all tests
composer run test

# Run specific test file
php artisan test --filter=ExampleTest

# Run tests with coverage
php artisan test --coverage

# Create test
php artisan make:test UserTest           # Feature test
php artisan make:test UserTest --unit    # Unit test
```

---

## 🏗️ ARCHITECTURE & KEY PATTERNS

### Tech Stack (Implemented)
- **Backend**: Laravel 12.x on PHP 8.3.16
- **Database**: MySQL (configured via .env)
- **Frontend**: Vite + Tailwind CSS 4.0
- **Queue**: Database driver (default)
- **Testing**: PHPUnit 11.5

### Laravel 12 Specifics
- Uses new `bootstrap/app.php` structure (no HTTP Kernel)
- Middleware defined via `->withMiddleware()` in app.php
- Exception handling via `->withExceptions()`
- Service providers in `bootstrap/providers.php`
- Default SQLite for testing (database/database.sqlite)

### Controller Organization Pattern
The project follows a modular controller structure:

```
app/Http/Controllers/
├── Auth/              # Authentication (login, register, user management)
├── Admin/             # User approval, roles
├── Asset/             # GBICs, PatchCords, Switches
├── Inventory/         # Stock, Movements, Assignments
├── Location/          # Sites, Racks, Bays
├── Catalog/           # FS.com product catalog
└── Report/            # Dashboard, PDF reports
```

### Model Relationships
Key polymorphic relationships to implement:

1. **Assignments** (polymorphic):
   - `assignable_type` + `assignable_id` → Gbic or PatchCord
   - belongs to Switch via `switch_id`
   - UNIQUE constraint: one assignment per port at a time

2. **StockMovements** (polymorphic):
   - `movable_type` + `movable_id` → Gbic, PatchCord, or Switch
   - tracks movement between sites

3. **Alerts** (polymorphic):
   - `related_type` + `related_id` → any asset type
   - low_stock, warranty_expiring, maintenance_due

### Permissions System (Spatie)
Four role hierarchy:
- `super_admin`: All permissions (*)
- `admin`: User management + full asset control
- `technician`: Asset CRUD + assignments + movements
- `reader`: Read-only access

Permission naming convention: `{resource}.{action}` (e.g., `assets.create`, `users.approve`)

### Barcode System
- Auto-generated on asset creation (GBICs, PatchCords, Switches)
- Unique constraint in database
- Used for quick lookup via scanner or API
- Service: `BarcodeGeneratorService` (to be implemented)

### FS.com Scraping Strategy
- Service: `FsComScraperService` with Guzzle + DomCrawler
- Command: `php artisan fscom:sync` (scheduled daily)
- Stores products in `fs_com_products` table
- Upsert pattern (update if exists, insert if new)
- Fields: SKU, name, specs (JSON), price, image_url

---

## 🏗️ PROJECT SPECIFICATION (Full Details Below)

## 🏗️ ARCHITECTURE GLOBALE

### Stack Technique
```
Backend (API)
├── Laravel 11.x
├── PHP 8.2+
├── MySQL 8.0+
├── Laravel Sanctum (authentification API)
├── Spatie Laravel Permission (rôles/permissions)
└── DomCrawler + Guzzle (scraping FS.com)

Frontend (SPA)
├── Vue.js 3 (Composition API + <script setup>)
├── Vue Router 4
├── Pinia (state management)
├── Tailwind CSS 3
├── Axios (requêtes API)
├── VueUse (composables utilitaires)
└── Vue-Barcode-Reader (lecture codes-barres)

Outils complémentaires
├── Laravel Scheduler (sync FS.com automatique)
├── Laravel Queue (jobs asynchrones)
├── DomPDF ou Snappy (génération PDF)
└── Intervention Image (codes-barres)
```

---

## 📁 STRUCTURE DES DOSSIERS

### Backend (Laravel)
```
laravel-backend/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── SyncFsComProducts.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── AuthController.php
│   │   │   │   └── UserManagementController.php
│   │   │   ├── Admin/
│   │   │   │   ├── UserApprovalController.php
│   │   │   │   └── RoleController.php
│   │   │   ├── Asset/
│   │   │   │   ├── GbicController.php
│   │   │   │   ├── PatchCordController.php
│   │   │   │   └── SwitchController.php
│   │   │   ├── Inventory/
│   │   │   │   ├── StockController.php
│   │   │   │   ├── StockMovementController.php
│   │   │   │   └── AssignmentController.php
│   │   │   ├── Location/
│   │   │   │   ├── SiteController.php
│   │   │   │   ├── RackController.php
│   │   │   │   └── BayController.php
│   │   │   ├── Catalog/
│   │   │   │   └── FsComProductController.php
│   │   │   └── Report/
│   │   │       ├── DashboardController.php
│   │   │       └── PdfReportController.php
│   │   ├── Middleware/
│   │   │   ├── CheckRole.php
│   │   │   └── CheckUserApproved.php
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   ├── Asset/
│   │   │   └── Inventory/
│   │   └── Resources/
│   │       ├── GbicResource.php
│   │       ├── SwitchResource.php
│   │       └── UserResource.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Site.php
│   │   ├── Rack.php
│   │   ├── Bay.php
│   │   ├── FsComProduct.php
│   │   ├── Gbic.php
│   │   ├── PatchCord.php
│   │   ├── Switch.php
│   │   ├── SwitchModel.php
│   │   ├── StockMovement.php
│   │   ├── Assignment.php
│   │   └── Alert.php
│   ├── Services/
│   │   ├── FsComScraperService.php
│   │   ├── BarcodeGeneratorService.php
│   │   ├── StockService.php
│   │   └── ReportService.php
│   └── Jobs/
│       └── SyncFsComCatalog.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
└── config/
    └── fscom.php
```

### Frontend (Vue.js)
```
vue-frontend/
├── src/
│   ├── assets/
│   │   └── styles/
│   │       └── main.css
│   ├── components/
│   │   ├── layout/
│   │   │   ├── AppHeader.vue
│   │   │   ├── AppSidebar.vue
│   │   │   └── AppFooter.vue
│   │   ├── common/
│   │   │   ├── DataTable.vue
│   │   │   ├── SearchBar.vue
│   │   │   ├── Pagination.vue
│   │   │   ├── Modal.vue
│   │   │   ├── Alert.vue
│   │   │   └── BarcodeScanner.vue
│   │   ├── auth/
│   │   │   ├── LoginForm.vue
│   │   │   └── RegisterForm.vue
│   │   ├── assets/
│   │   │   ├── GbicForm.vue
│   │   │   ├── GbicList.vue
│   │   │   ├── SwitchForm.vue
│   │   │   ├── SwitchList.vue
│   │   │   ├── PatchCordForm.vue
│   │   │   └── PatchCordList.vue
│   │   ├── inventory/
│   │   │   ├── StockOverview.vue
│   │   │   ├── MovementForm.vue
│   │   │   ├── MovementHistory.vue
│   │   │   ├── AssignmentForm.vue
│   │   │   └── AlertsList.vue
│   │   ├── location/
│   │   │   ├── SiteManager.vue
│   │   │   ├── RackManager.vue
│   │   │   └── LocationTree.vue
│   │   ├── admin/
│   │   │   ├── UserApproval.vue
│   │   │   ├── UserManagement.vue
│   │   │   └── RoleManagement.vue
│   │   └── dashboard/
│   │       ├── StatsCards.vue
│   │       ├── StockChart.vue
│   │       └── RecentActivity.vue
│   ├── views/
│   │   ├── auth/
│   │   │   ├── Login.vue
│   │   │   ├── Register.vue
│   │   │   └── PendingApproval.vue
│   │   ├── Dashboard.vue
│   │   ├── assets/
│   │   │   ├── Gbics.vue
│   │   │   ├── Switches.vue
│   │   │   └── PatchCords.vue
│   │   ├── inventory/
│   │   │   ├── Stock.vue
│   │   │   ├── Movements.vue
│   │   │   └── Assignments.vue
│   │   ├── locations/
│   │   │   └── LocationManagement.vue
│   │   ├── admin/
│   │   │   ├── Users.vue
│   │   │   └── Settings.vue
│   │   └── reports/
│   │       └── Reports.vue
│   ├── stores/
│   │   ├── auth.js
│   │   ├── assets.js
│   │   ├── inventory.js
│   │   └── locations.js
│   ├── router/
│   │   └── index.js
│   ├── services/
│   │   └── api.js
│   ├── composables/
│   │   ├── useAuth.js
│   │   ├── usePermissions.js
│   │   └── useBarcode.js
│   ├── utils/
│   │   ├── constants.js
│   │   └── helpers.js
│   ├── App.vue
│   └── main.js
├── public/
├── index.html
├── package.json
├── vite.config.js
└── tailwind.config.js
```

---

## 🗄️ MODÈLE DE DONNÉES (BDD)

### Schéma des tables

#### **1. users**
```sql
id (bigint, PK)
name (varchar)
email (varchar, unique)
password (varchar)
is_approved (boolean, default: false)
approved_by (bigint, FK users, nullable)
approved_at (timestamp, nullable)
email_verified_at (timestamp, nullable)
remember_token (varchar, nullable)
created_at (timestamp)
updated_at (timestamp)
```

#### **2. roles** (Spatie)
```sql
id (bigint, PK)
name (varchar) // super_admin, admin, technician, reader
guard_name (varchar)
created_at (timestamp)
updated_at (timestamp)
```

#### **3. permissions** (Spatie)
```sql
id (bigint, PK)
name (varchar)
guard_name (varchar)
created_at (timestamp)
updated_at (timestamp)
```

#### **4. sites**
```sql
id (bigint, PK)
name (varchar)
address (text, nullable)
city (varchar, nullable)
country (varchar, nullable)
contact_name (varchar, nullable)
contact_phone (varchar, nullable)
created_at (timestamp)
updated_at (timestamp)
```

#### **5. racks**
```sql
id (bigint, PK)
site_id (bigint, FK sites)
name (varchar)
location (varchar, nullable) // ex: "Salle serveur A"
units (integer) // nombre d'U
created_at (timestamp)
updated_at (timestamp)
```

#### **6. bays**
```sql
id (bigint, PK)
rack_id (bigint, FK racks)
position (integer) // position en U
name (varchar, nullable)
created_at (timestamp)
updated_at (timestamp)
```

#### **7. fs_com_products** (Catalogue FS.com)
```sql
id (bigint, PK)
fs_com_id (varchar, unique) // ID produit FS.com
sku (varchar, unique)
name (varchar)
category (enum: 'gbic', 'patch_cord')
description (text, nullable)
specifications (json, nullable)
price (decimal, nullable)
currency (varchar, nullable)
url (varchar, nullable)
image_url (varchar, nullable)
last_synced_at (timestamp)
created_at (timestamp)
updated_at (timestamp)
```

#### **8. switch_models**
```sql
id (bigint, PK)
manufacturer (varchar) // Cisco, Juniper, Arista...
model (varchar)
port_count (integer)
port_types (json) // [{type: 'SFP+', count: 24}, {type: 'QSFP', count: 4}]
description (text, nullable)
created_at (timestamp)
updated_at (timestamp)
```

#### **9. switches**
```sql
id (bigint, PK)
switch_model_id (bigint, FK switch_models)
bay_id (bigint, FK bays, nullable)
serial_number (varchar, unique)
asset_tag (varchar, unique, nullable)
barcode (varchar, unique)
hostname (varchar, nullable)
status (enum: 'in_stock', 'deployed', 'maintenance', 'retired')
purchase_date (date, nullable)
warranty_end (date, nullable)
notes (text, nullable)
created_at (timestamp)
updated_at (timestamp)
```

#### **10. gbics**
```sql
id (bigint, PK)
fs_com_product_id (bigint, FK fs_com_products, nullable)
serial_number (varchar, unique)
barcode (varchar, unique)
status (enum: 'in_stock', 'assigned', 'faulty', 'retired')
purchase_date (date, nullable)
warranty_end (date, nullable)
notes (text, nullable)
created_at (timestamp)
updated_at (timestamp)
```

#### **11. patch_cords**
```sql
id (bigint, PK)
fs_com_product_id (bigint, FK fs_com_products, nullable)
serial_number (varchar, nullable)
barcode (varchar, unique)
length (decimal) // en mètres
connector_type_a (varchar) // LC, SC, ST...
connector_type_b (varchar)
fiber_type (enum: 'single_mode', 'multi_mode')
status (enum: 'in_stock', 'deployed', 'faulty', 'retired')
purchase_date (date, nullable)
notes (text, nullable)
created_at (timestamp)
updated_at (timestamp)
```

#### **12. assignments** (Affectations)
```sql
id (bigint, PK)
assignable_type (varchar) // Gbic, PatchCord
assignable_id (bigint)
switch_id (bigint, FK switches)
port_number (integer)
assigned_at (timestamp)
unassigned_at (timestamp, nullable)
assigned_by (bigint, FK users)
unassigned_by (bigint, FK users, nullable)
notes (text, nullable)
created_at (timestamp)
updated_at (timestamp)

INDEX: (assignable_type, assignable_id)
UNIQUE: (switch_id, port_number) WHERE unassigned_at IS NULL
```

#### **13. stock_movements**
```sql
id (bigint, PK)
movable_type (varchar) // Gbic, PatchCord, Switch
movable_id (bigint)
movement_type (enum: 'in', 'out', 'transfer', 'return', 'adjustment')
from_site_id (bigint, FK sites, nullable)
to_site_id (bigint, FK sites, nullable)
quantity (integer, default: 1)
reason (text, nullable)
performed_by (bigint, FK users)
performed_at (timestamp)
created_at (timestamp)
updated_at (timestamp)

INDEX: (movable_type, movable_id)
```

#### **14. alerts**
```sql
id (bigint, PK)
alert_type (enum: 'low_stock', 'warranty_expiring', 'maintenance_due')
severity (enum: 'info', 'warning', 'critical')
title (varchar)
message (text)
related_type (varchar, nullable)
related_id (bigint, nullable)
is_read (boolean, default: false)
read_by (bigint, FK users, nullable)
read_at (timestamp, nullable)
created_at (timestamp)
updated_at (timestamp)

INDEX: (related_type, related_id)
```

---

## 🔌 API ENDPOINTS

### **Authentication** (`/api/auth`)
```
POST   /register                 # Inscription (status: pending)
POST   /login                    # Connexion
POST   /logout                   # Déconnexion
GET    /me                       # Profil utilisateur
PUT    /me                       # Mise à jour profil
```

### **Admin - User Management** (`/api/admin/users`)
```
GET    /pending                  # Liste utilisateurs en attente
POST   /{id}/approve             # Approuver un utilisateur
POST   /{id}/reject              # Rejeter un utilisateur
GET    /                         # Liste tous les utilisateurs
PUT    /{id}/role                # Modifier le rôle
DELETE /{id}                     # Supprimer un utilisateur
```

### **Sites** (`/api/sites`)
```
GET    /                         # Liste des sites
POST   /                         # Créer un site
GET    /{id}                     # Détail d'un site
PUT    /{id}                     # Modifier un site
DELETE /{id}                     # Supprimer un site
GET    /{id}/racks               # Racks d'un site
```

### **Racks** (`/api/racks`)
```
GET    /                         # Liste des racks
POST   /                         # Créer un rack
GET    /{id}                     # Détail d'un rack
PUT    /{id}                     # Modifier un rack
DELETE /{id}                     # Supprimer un rack
GET    /{id}/bays                # Baies d'un rack
```

### **Bays** (`/api/bays`)
```
GET    /                         # Liste des baies
POST   /                         # Créer une baie
GET    /{id}                     # Détail d'une baie
PUT    /{id}                     # Modifier une baie
DELETE /{id}                     # Supprimer une baie
```

### **FS.com Catalog** (`/api/catalog/fscom`)
```
GET    /products                 # Liste produits FS.com
GET    /products/{id}            # Détail produit
POST   /sync                     # Forcer synchronisation
GET    /categories               # Catégories disponibles
GET    /search                   # Recherche produits
```

### **Switch Models** (`/api/switch-models`)
```
GET    /                         # Liste des modèles
POST   /                         # Créer un modèle
GET    /{id}                     # Détail d'un modèle
PUT    /{id}                     # Modifier un modèle
DELETE /{id}                     # Supprimer un modèle
```

### **Switches** (`/api/switches`)
```
GET    /                         # Liste des switchs
POST   /                         # Créer un switch
GET    /{id}                     # Détail d'un switch
PUT    /{id}                     # Modifier un switch
DELETE /{id}                     # Supprimer un switch
GET    /{id}/ports               # État des ports
GET    /{id}/assignments         # Affectations sur ce switch
POST   /barcode/{barcode}        # Recherche par code-barres
```

### **GBICs** (`/api/gbics`)
```
GET    /                         # Liste des GBICs
POST   /                         # Créer un GBIC
GET    /{id}                     # Détail d'un GBIC
PUT    /{id}                     # Modifier un GBIC
DELETE /{id}                     # Supprimer un GBIC
GET    /{id}/history             # Historique des affectations
POST   /barcode/{barcode}        # Recherche par code-barres
POST   /bulk-import              # Import en masse
```

### **Patch Cords** (`/api/patch-cords`)
```
GET    /                         # Liste des jarretières
POST   /                         # Créer une jarretière
GET    /{id}                     # Détail d'une jarretière
PUT    /{id}                     # Modifier une jarretière
DELETE /{id}                     # Supprimer une jarretière
POST   /barcode/{barcode}        # Recherche par code-barres
```

### **Assignments** (`/api/assignments`)
```
GET    /                         # Liste des affectations
POST   /                         # Créer une affectation
GET    /{id}                     # Détail d'une affectation
DELETE /{id}                     # Retirer une affectation
GET    /switch/{switchId}        # Affectations d'un switch
GET    /history                  # Historique complet
```

### **Stock Movements** (`/api/stock/movements`)
```
GET    /                         # Liste des mouvements
POST   /                         # Enregistrer un mouvement
GET    /{id}                     # Détail d'un mouvement
GET    /asset/{type}/{id}        # Mouvements d'un asset
GET    /statistics               # Statistiques
```

### **Alerts** (`/api/alerts`)
```
GET    /                         # Liste des alertes
GET    /unread                   # Alertes non lues
PUT    /{id}/read                # Marquer comme lue
DELETE /{id}                     # Supprimer une alerte
POST   /check                    # Vérifier et créer alertes
```

### **Dashboard** (`/api/dashboard`)
```
GET    /stats                    # Statistiques globales
GET    /recent-activity          # Activité récente
GET    /stock-overview           # Vue d'ensemble du stock
GET    /alerts-summary           # Résumé des alertes
```

### **Reports** (`/api/reports`)
```
GET    /inventory                # Rapport d'inventaire (JSON)
GET    /inventory/pdf            # Rapport d'inventaire (PDF)
GET    /movements                # Rapport mouvements (JSON)
GET    /movements/pdf            # Rapport mouvements (PDF)
POST   /custom                   # Rapport personnalisé
GET    /labels/{type}/{id}       # Étiquette code-barres (PDF)
```

---

## 🔐 SYSTÈME DE PERMISSIONS

### Rôles et permissions

```php
// config/permissions.php

'roles' => [
    'super_admin' => [
        'permissions' => ['*'] // Toutes les permissions
    ],
    
    'admin' => [
        'permissions' => [
            'users.approve',
            'users.manage',
            'assets.create',
            'assets.update',
            'assets.delete',
            'assets.view',
            'stock.manage',
            'locations.manage',
            'reports.generate',
            'catalog.sync',
        ]
    ],
    
    'technician' => [
        'permissions' => [
            'assets.create',
            'assets.update',
            'assets.view',
            'stock.movements',
            'assignments.manage',
            'reports.view',
        ]
    ],
    
    'reader' => [
        'permissions' => [
            'assets.view',
            'stock.view',
            'reports.view',
        ]
    ],
]
```

---

## 🕷️ SCRAPING FS.COM

### Service de scraping

**Stratégie :**
1. Identifier les pages produits GBICs et patch cords sur FS.com
2. Parser les informations : SKU, nom, specs, prix, images
3. Stocker dans `fs_com_products`
4. Planifier une synchro quotidienne via Laravel Scheduler

**URLs cibles (exemples) :**
- GBICs : `https://www.fs.com/fr/products/optical-modules.html`
- Patch Cords : `https://www.fs.com/fr/products/fiber-cables.html`

**Service PHP :**
```php
// app/Services/FsComScraperService.php

class FsComScraperService
{
    public function scrapeCategory(string $category)
    {
        // 1. Récupérer la liste des produits
        // 2. Pour chaque produit, scraper la page détail
        // 3. Extraire : SKU, nom, description, specs (JSON), prix, image
        // 4. Upsert dans fs_com_products
    }
    
    public function scrapeProduct(string $url)
    {
        // Parser une page produit individuelle
    }
    
    public function syncAll()
    {
        $this->scrapeCategory('gbics');
        $this->scrapeCategory('patch_cords');
    }
}
```

**Command Laravel :**
```php
// app/Console/Commands/SyncFsComProducts.php
php artisan fscom:sync
```

**Scheduler (app/Console/Kernel.php) :**
```php
$schedule->command('fscom:sync')->daily();
```

---

## 📊 FONCTIONNALITÉS DÉTAILLÉES

### 1. **Dashboard**
- Nombre total d'assets par catégorie
- Stock disponible vs déployé
- Alertes actives (stock bas, garantie expirante)
- Graphiques : évolution du stock, mouvements récents
- Dernières activités (affectations, mouvements)

### 2. **Gestion des GBICs**
- **Création** : 
  - Sélection produit FS.com ou saisie manuelle
  - Génération automatique code-barres
  - Numéro de série unique
- **Liste** : filtres (statut, site, modèle), recherche
- **Affectation** : sélection switch + numéro de port
- **Historique** : toutes les affectations passées

### 3. **Gestion des Switches**
- **Création** :
  - Sélection modèle (pré-créé)
  - Localisation (site → rack → baie)
  - Génération code-barres
- **Vue ports** : 
  - Visualisation de l'état de chaque port
  - Affichage GBIC/patch cord assigné
  - Disponibilité en un coup d'œil

### 4. **Mouvements de stock**
- **Types** :
  - Entrée (achat, réception)
  - Sortie (mise au rebut, perte)
  - Transfert (entre sites)
  - Retour (depuis déploiement vers stock)
  - Ajustement (correction inventaire)
- **Historique** : traçabilité complète avec utilisateur et date

### 5. **Alertes automatiques**
- **Stock bas** : seuil configurable par produit
- **Garantie expirante** : alerte à 30, 60, 90 jours
- **Assets non affectés depuis X jours**
- Notifications dans l'interface + email (optionnel)

### 6. **Scan code-barres**
- Composant Vue avec caméra
- Recherche instantanée d'asset
- Actions rapides : affectation, mouvement, consultation

### 7. **Rapports PDF**
- **Inventaire global** : par site, par catégorie
- **Étiquettes code-barres** : impression pour nouveaux assets
- **Rapport de mouvements** : période sélectionnable
- **Affectations actives** : par switch ou globales

---

## 🎨 INTERFACE UTILISATEUR (Vue.js)

### Composants clés

#### Layout
```
AppHeader : logo, utilisateur, notifications, déconnexion
AppSidebar : menu navigation (rôle-based)
AppFooter : infos application
```

#### Pages principales

**Dashboard** :
- 4 cards stats (total assets, stock dispo, alertes, mouvements du mois)
- Graphique ligne : évolution stock 6 derniers mois
- Tableau : dernières activités
- Liste alertes non lues

**Gestion Assets** :
- DataTable avec tri, filtres, pagination
- Actions : voir, éditer, supprimer (selon rôle)
- Bouton "Ajouter" (modal formulaire)
- Export CSV/PDF

**Affectations** :
- Sélecteur switch (avec aperçu ports)
- Drag & drop GBIC vers port (idéal UX)
- Validation disponibilité port
- Confirmation avec historique

**Scanner** :
- Vue plein écran avec caméra
- Détection automatique code-barres
- Fiche asset + actions rapides

---

## 🚀 ÉTAPES DE DÉVELOPPEMENT

### Phase 1 : Setup & Auth (Semaine 1)
1. Installation Laravel + Vue.js
2. Configuration BDD, migrations
3. Système authentification (Sanctum)
4. Rôles et permissions (Spatie)
5. Middleware approbation utilisateur
6. Frontend : login, register, layout de base

### Phase 2 : Localisations (Semaine 2)
7. CRUD Sites
8. CRUD Racks
9. CRUD Bays
10. Arborescence visuelle (Vue)
11. Validation hiérarchie (site > rack > bay)

### Phase 3 : Catalogue FS.com (Semaine 3)
12. Service scraping FS.com
13. Command Laravel sync
14. Scheduler quotidien
15. Interface consultation catalogue
16. Recherche et filtres

### Phase 4 : Assets (Semaine 4-5)
17. CRUD Switch Models
18. CRUD Switches (avec localisation)
19. CRUD GBICs (avec lien produits FS.com)
20. CRUD Patch Cords
21. Génération codes-barres
22. Interfaces Vue.js complètes

### Phase 5 : Inventaire (Semaine 6)
23. Système affectations (switch/port)
24. Mouvements de stock
25. Historique complet
26. Gestion états (in_stock, deployed, etc.)
27. Règles métier (unicité affectation port)

### Phase 6 : Alertes & Dashboard (Semaine 7)
28. Service génération alertes
29. Command vérification quotidienne
30. Dashboard avec statistiques
31. Graphiques (Chart.js ou similaire)
32. Notifications temps réel

### Phase 7 : Scanner & Rapports (Semaine 8)
33. Composant scan code-barres
34. Recherche par code-barres (API)
35. Génération PDF rapports
36. Étiquettes imprimables
37. Export CSV/Excel

### Phase 8 : Tests & Déploiement (Semaine 9)
38. Tests unitaires backend
39. Tests composants Vue
40. Tests E2E (Cypress/Playwright)
41. Optimisations performances
42. Documentation
43. Déploiement serveur dédié

---

## 📦 COMMANDES UTILES

### Backend (Laravel)
```bash
# Installation
composer install
php artisan key:generate
php artisan migrate --seed

# Créer rôles de base
php artisan db:seed --class=RoleSeeder

# Sync FS.com
php artisan fscom:sync

# Vérifier alertes
php artisan alerts:check

# Queue worker (jobs asynchrones)
php artisan queue:work
```

### Frontend (Vue.js)
```bash
# Installation
npm install

# Dev
npm run dev

# Build production
npm run build

# Preview
npm run preview
```

---

## 🔧 CONFIGURATION SERVEUR

### Prérequis
- PHP 8.2+ (avec extensions : pdo_mysql, mbstring, xml, bcmath, gd)
- MySQL 8.0+
- Node.js 18+ (pour build Vue.js)
- Composer
- Nginx ou Apache

### Nginx (exemple)
```nginx
server {
    listen 80;
    server_name assets.example.com;
    root /var/www/laravel-backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Servir le frontend Vue.js
server {
    listen 80;
    server_name app.assets.example.com;
    root /var/www/vue-frontend/dist;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

---

## 📝 FICHIERS DE CONFIGURATION

### `.env` Laravel
```env
APP_NAME="Asset Manager"
APP_URL=http://assets.example.com
FRONTEND_URL=http://app.assets.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=asset_manager
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=app.assets.example.com

FSCOM_BASE_URL=https://www.fs.com/fr
FSCOM_SYNC_ENABLED=true
FSCOM_SYNC_SCHEDULE=daily

QUEUE_CONNECTION=database
```

### `.env` Vue.js
```env
VITE_API_URL=http://assets.example.com/api
VITE_APP_NAME=Asset Manager
```

---

## 📚 DOCUMENTATION À CRÉER

1. **README.md** : installation, configuration
2. **API.md** : documentation complète des endpoints
3. **DEPLOYMENT.md** : guide de déploiement
4. **USER_GUIDE.md** : guide utilisateur (avec screenshots)
5. **CONTRIBUTING.md** : conventions de code

---

## ✅ CHECKLIST FINALE

### Sécurité
- [ ] Validation des inputs (Form Requests)
- [ ] Protection CSRF
- [ ] Rate limiting API
- [ ] Sanitization données scraping
- [ ] HTTPS en production
- [ ] Politique CORS stricte

### Performance
- [ ] Indexes BDD sur colonnes recherchées
- [ ] Eager loading relations (éviter N+1)
- [ ] Cache (Redis) pour catalogue FS.com
- [ ] Pagination résultats API
- [ ] Lazy loading images frontend
- [ ] Build optimisé Vue.js (minification)

### UX
- [ ] Feedback visuel actions (toasts, loaders)
- [ ] Gestion erreurs claire
- [ ] Responsive design (mobile/tablet)
- [ ] Accessibilité (ARIA labels)
- [ ] Raccourcis clavier (power users)
- [ ] Mode sombre (optionnel)

---


