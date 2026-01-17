# Perbaikan Modular & Filament v5

## Masalah yang Ditemukan

### 1. **MemberResource Bukan di Plugin IAM**
- ❌ Resource ada di `app/Filament/App/Resources/MemberResource.php` 
- ✅ Sekarang di `plugins/iam/src/Filament/Resources/Members/MemberResource.php`

**Alasan**: Aplikasi ini **modular**. Semua IAM resources harus di plugin `plugins/iam`, bukan di app.

### 2. **Query Error: Column 'pivot' not found**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'pivot' in 'order clause'
```

**Penyebab**: Table mencoba sort berdasarkan `pivot.joined_at` tapi Eloquent tidak bisa sort pivot column di query level.

**Solusi**: 
- Hapus sorting pada pivot columns
- Load pivot data menggunakan `getStateUsing()` untuk display saja
- Sorting dimatikan dengan `->sortable(false)`

### 3. **Syntax Filament v4 (Lama)**
Dokumentasi Filament v5 menunjukkan perubahan:

#### ❌ Syntax Lama (v4):
```php
$table
    ->actions([...])      // ❌ Deprecated
    ->bulkActions([...])  // ❌ Deprecated
```

#### ✅ Syntax Baru (v5):
```php
$table
    ->recordActions([...])    // ✅ Per-row actions
    ->toolbarActions([...])   // ✅ Bulk actions di toolbar
    ->headerActions([...])    // ✅ Header actions (create, import, dll)
```

## Perbaikan yang Dilakukan

### 1. MemberResource Dipindahkan ke Plugin IAM

**Struktur Baru:**
```
plugins/iam/src/Filament/Resources/
├── Members/
│   ├── MemberResource.php
│   └── Pages/
│       └── ListMembers.php
├── Tenants/
├── Users/
└── Roles/
```

### 2. Resource Compatible dengan/tanpa Tenancy

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(function (Builder $query) {
            // Jika dalam konteks tenant (panel App), filter by tenant
            if ($tenant = \Filament\Facades\Filament::getTenant()) {
                $query->whereHas('tenants', function (Builder $q) use ($tenant) {
                    $q->where('tenants.id', $tenant->id);
                });
            }
            
            $query->with(['tenants']);
        })
```

**Kenapa Penting?**
- ✅ Tenancy **opsional** (bisa di-disable)
- ✅ Panel App **opsional** (hanya ada jika tenancy enabled)
- ✅ Resource tetap bekerja di both modes

### 3. Pivot Columns Tidak Sortable

```php
TextColumn::make('role')
    ->getStateUsing(function ($record) {
        if ($tenant = \Filament\Facades\Filament::getTenant()) {
            $membership = $record->tenants()
                ->where('tenants.id', $tenant->id)
                ->first();
            
            return $membership?->pivot->role ?? TenantRole::VIEWER->value;
        }
        
        return null;
    })
    ->badge()
    ->sortable(false),  // ✅ Pivot columns tidak bisa di-sort di query level
```

### 4. Filament v5 Syntax Updated

#### MemberResource
```php
->recordActions([      // ✅ v5: Per-row actions
    Action::make('updateRole')...
    Action::make('remove')...
])
->headerActions([      // ✅ v5: Header actions
    Action::make('invite')...
])
```

#### MembersRelationManager
```php
->recordActions([      // ✅ v5: Changed from ->actions()
    ActionGroup::make([
        Action::make('updateRole')...
        DeleteAction::make('remove')...
    ]),
])
```

### 5. Navigation Icon Type Fix

```php
use BackedEnum;

class MemberResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
}
```

## Arsitektur Akhir

### Panel Admin (Global - No Tenancy)
```
/admin
├── /users          → UserResource (global)
├── /roles          → RoleResource (global)
├── /tenants        → TenantResource (global CRUD)
└── /tenant-members → TenantMemberResource (global overview)
```

### Panel App (Multitenant - Optional)
```
/app/{tenant}
└── /members        → MemberResource (tenant-scoped)
```

**Catatan Penting:**
- Panel App **hanya ada** jika `config('iam.tenant.enabled') === true`
- MemberResource **hanya muncul** di navigation jika dalam tenant context
- Implementasi menggunakan `shouldRegisterNavigation()`:

```php
public static function shouldRegisterNavigation(): bool
{
    return \Filament\Facades\Filament::getTenant() !== null;
}
```

## Testing

### Verifikasi Routes
```bash
php artisan route:list --json | jq -r '.[] | select(.uri | contains("app/{tenant}/members")) | .uri'
# Output: app/{tenant}/members ✅
```

### Verifikasi Resource Config
```php
// Via Tinker
\Sekeco\Iam\Filament\Resources\Members\MemberResource::getPages()
// Returns: ["index" => ListMembers::class] ✅
```

## Lessons Learned

### 1. **Aplikasi Modular Requires Discipline**
- ❌ Jangan taruh IAM resources di `app/`
- ✅ Semua IAM code harus di `plugins/iam/`

### 2. **Tenancy is Optional**
- Semua IAM features harus compatible dengan/tanpa tenancy
- Gunakan `Filament::getTenant()` untuk conditional logic
- Jangan asumsi tenant context selalu ada

### 3. **Filament v5 Breaking Changes**
- `->actions()` → `->recordActions()`
- `->bulkActions()` → `->toolbarActions()`
- Selalu cek docs terbaru dengan `search-docs` tool

### 4. **Pivot Data Cannot Be Sorted in Query**
- Pivot columns harus menggunakan `getStateUsing()`
- Harus di-set `->sortable(false)`
- Sorting pivot data memerlukan custom query logic

## Checklist untuk Resource Baru

Saat membuat Filament resource baru di aplikasi modular ini:

- [ ] Resource di `plugins/{plugin}/src/Filament/Resources/`
- [ ] Gunakan `->recordActions()` bukan `->actions()`
- [ ] Gunakan `->headerActions()` untuk create/import actions
- [ ] Gunakan `->toolbarActions()` untuk bulk actions
- [ ] Cek tenancy dengan `Filament::getTenant()`
- [ ] Pivot columns set `->sortable(false)`
- [ ] Navigation icon type: `string|BackedEnum|null`
- [ ] Test dengan tenancy enabled DAN disabled

## File Changes

### Created:
- `plugins/iam/src/Filament/Resources/Members/MemberResource.php`
- `plugins/iam/src/Filament/Resources/Members/Pages/ListMembers.php`

### Modified:
- `plugins/iam/src/Filament/Resources/Tenants/RelationManagers/MembersRelationManager.php`
  - Changed `->actions()` to `->recordActions()`

### Deleted:
- `app/Filament/App/` (entire directory)

## Resources Inventory

| Resource | Location | Panel | Tenant Aware | Notes |
|----------|----------|-------|--------------|-------|
| UserResource | `plugins/iam` | Admin | No | Global user management |
| RoleResource | `plugins/iam` | Admin | No | Spatie permissions |
| TenantResource | `plugins/iam` | Admin | No | CRUD all tenants |
| TenantMemberResource | `plugins/iam` | Admin | No | Global overview of memberships |
| MemberResource | `plugins/iam` | App | Yes | Tenant-scoped member management |

**Total IAM Resources:** 5
**Semua di:** `plugins/iam/` ✅
