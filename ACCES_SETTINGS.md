# Comment accéder à la page Settings

## 🔍 Problème
Le lien "Settings" n'apparaît pas dans la sidebar.

## ✅ Solutions

### Solution 1: Vérifier votre rôle utilisateur

Le lien Settings n'est visible que pour les utilisateurs avec les rôles:
- `super_admin`
- `admin`

**Pour vérifier votre rôle:**
1. Ouvrez la console du navigateur (F12)
2. Tapez:
```javascript
JSON.parse(localStorage.getItem('user'))
```
3. Regardez la propriété `roles`

**Si vous n'avez pas le bon rôle, vous devez:**
- Être approuvé par un admin
- Recevoir le rôle admin ou super_admin via le backend

---

### Solution 2: Accès direct via URL (temporaire)

Même sans le lien dans la sidebar, vous pouvez accéder directement à Settings:

**URL:** http://localhost:5174/admin/settings

⚠️ **Note:** La route est protégée. Si vous n'avez pas les bons rôles, vous serez redirigé vers le Dashboard.

---

### Solution 3: Créer un super admin via le backend (recommandé)

#### Via Seeder Laravel:

1. Assurez-vous que le seeder existe:
```bash
php artisan db:seed --class=SuperAdminSeeder
```

2. Le seeder devrait créer un utilisateur avec:
   - Email: admin@example.com
   - Password: password (à changer!)
   - Rôle: super_admin
   - is_approved: true

#### Via Tinker:

```bash
php artisan tinker
```

Puis tapez:
```php
use App\Models\User;
use Spatie\Permission\Models\Role;

// Créer ou trouver le rôle super_admin
$role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

// Créer un utilisateur admin
$user = User::create([
    'name' => 'Super Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'is_approved' => true,
    'approved_at' => now()
]);

// Assigner le rôle
$user->assignRole('super_admin');
```

---

### Solution 4: Modifier temporairement la sidebar (dev only)

**⚠️ Pour le développement uniquement!**

Éditez `vue-frontend/src/components/layout/AppSidebar.vue`:

Ligne 103, remplacez:
```vue
<div v-if="authStore.hasAnyRole(['super_admin', 'admin'])" class="mt-6">
```

Par (supprimez la condition):
```vue
<div class="mt-6">
```

Cela rendra la section Administration visible pour tous les utilisateurs.

---

## 📊 Structure de Settings

Une fois accessible, vous trouverez 4 onglets:

### 1. Switch Models
- Gestion des modèles de switches
- Manufacturer, Model, Port Count, Port Types

### 2. Sites
- Gestion des sites géographiques
- Name, Address, City, Country, Contact

### 3. Racks
- Gestion des racks serveurs
- Site, Name, Location, Units (42U par défaut)

### 4. Bays
- Gestion des positions dans les racks
- Rack, Position (U), Bay Name
- Cascade: Site → Rack → Position

---

## 🔐 Permissions requises

Pour accéder à Settings:
- Route: `/admin/settings`
- Rôles requis: `super_admin` ou `admin`
- Protection: Navigation guard dans le router

---

## 🧪 Test rapide

**Console navigateur:**
```javascript
// Vérifier si vous avez accès
const authStore = JSON.parse(localStorage.getItem('user'))
console.log('User:', authStore)
console.log('Roles:', authStore?.roles)
console.log('Has admin access:', authStore?.roles?.some(r => ['super_admin', 'admin'].includes(r.name)))
```

Si la dernière ligne retourne `true`, le lien Settings devrait être visible.

---

## 📝 Notes

- Le lien Settings apparaît dans la section "Administration" de la sidebar
- Il faut être connecté ET approuvé ET avoir le bon rôle
- La page Settings est une page d'administration réservée aux admins
- Tous les fichiers sont créés et opérationnels
