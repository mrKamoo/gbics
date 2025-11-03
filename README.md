# Asset Manager - GBIC Management System

Application complète de gestion d'assets réseau (GBICs, switches, patch cords) avec suivi d'inventaire, affectations et mouvements de stock.

## 🏗️ Architecture

- **Backend**: Laravel 12 + PHP 8.3 + SQLite/MySQL
- **Frontend**: Vue 3 + Vite + Tailwind CSS
- **API**: RESTful avec Laravel Sanctum
- **Permissions**: Spatie Laravel Permission (4 rôles)

## 🚀 Quick Start

### Backend
```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

### Frontend
```bash
cd vue-frontend
npm install
npm run dev
```

**Accès par défaut**:
- Frontend: http://localhost:5173
- Backend API: http://localhost:8000
- Admin: admin@example.com / password

## ✅ Fonctionnalités Implémentées

### Backend (Complet)
- ✅ Authentification avec approbation admin
- ✅ GBICs, Switches, Patch Cords (CRUD + barcode + historique)
- ✅ Assignments (affectations avec validations)
- ✅ Stock Movements (5 types de mouvements)
- ✅ Sites, Racks, Bays (gestion localisation)
- ✅ 4 rôles + 24 permissions

### Frontend (Base)
- ✅ Layout responsive avec sidebar
- ✅ Authentification (Login, Register, Approval)
- ✅ Navigation avec guards
- ✅ Dashboard
- ✅ Stores Pinia (Auth + Assets)
- ✅ API service avec intercepteurs

## 📋 Prochaines Étapes

- [ ] Pages complètes avec DataTables
- [ ] Formulaires modals CRUD
- [ ] Scanner code-barres
- [ ] Scraping catalogue FS.com
- [ ] Alertes automatiques
- [ ] Rapports PDF
- [ ] Graphiques statistiques

## 📚 Documentation Complète

Voir [CLAUDE.md](./CLAUDE.md) pour le plan complet du projet et les spécifications techniques détaillées.
