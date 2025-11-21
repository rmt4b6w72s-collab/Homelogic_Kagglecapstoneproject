# Quick Deployment Checklist

## ✅ Pre-Deployment Completed

- [x] Database migration executed (`2025_11_21_024916_create_resident_documents_table`)
- [x] Frontend assets built for production (`npm run build:production`)
- [x] Laravel caches optimized (config, routes, views)
- [x] Storage directory created (`storage/app/public/resident-documents`)
- [x] Storage permissions set (775)
- [x] API routes registered and cached
- [x] All code files created and verified
- [x] No linting errors

## 📋 Post-Deployment Verification

### 1. Database
```bash
php artisan migrate:status
# Verify: 2025_11_21_024916_create_resident_documents_table shows as "Ran"
```

### 2. Storage
```bash
ls -la storage/app/public/resident-documents
# Should show directory exists with proper permissions
ls -la public/storage
# Should show symbolic link exists
```

### 3. Routes
```bash
php artisan route:list --path=resident-documents
# Should show 6 routes (index, store, show, update, destroy, download)
```

### 4. Frontend
- [ ] Hard refresh browser (Ctrl+F5 / Cmd+Shift+R)
- [ ] Navigate to a resident detail page
- [ ] Verify "Documents" tab appears
- [ ] Test uploading a document
- [ ] Verify document appears in list
- [ ] Test download functionality

### 5. Filament Admin
- [ ] Navigate to Residents > Edit a resident
- [ ] Verify "Documents" relation manager tab appears
- [ ] Test creating a document from Filament
- [ ] Test editing a document
- [ ] Test deleting a document

### 6. Appointment Integration
- [ ] Navigate to Appointments
- [ ] Complete an appointment
- [ ] Verify document upload option appears
- [ ] Upload a document when completing
- [ ] Verify document is linked to appointment

## 🔧 Quick Commands Reference

### Clear All Caches
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Rebuild Frontend
```bash
npm run build:production
```

### Check Storage
```bash
php artisan storage:link
ls -la storage/app/public/resident-documents
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

## 🚨 Troubleshooting

### Documents Not Uploading
1. Check file permissions: `chmod -R 775 storage/app/public/resident-documents`
2. Check storage link: `php artisan storage:link`
3. Check Laravel logs: `tail -f storage/logs/laravel.log`
4. Check browser console for JavaScript errors

### Documents Not Showing
1. Hard refresh browser cache
2. Clear Laravel view cache: `php artisan view:clear`
3. Rebuild frontend: `npm run build:production`

### Permission Errors
1. Check user has `view_resident_documents` permission
2. Check user has `upload_resident_documents` permission
3. Verify in Administration > Roles & Permissions

## 📝 Files Changed Summary

**New Files (5):**
- `app/Models/ResidentDocument.php`
- `app/Http/Controllers/Api/ResidentDocumentController.php`
- `app/Filament/Resources/ResidentDocumentResource.php`
- `app/Filament/Resources/ResidentResource/RelationManagers/DocumentsRelationManager.php`
- `resources/js/components/ResidentDocuments.jsx`

**Modified Files (9):**
- `app/Models/Resident.php`
- `app/Filament/Resources/ResidentResource.php`
- `app/Filament/Resources/ResidentResource/Pages/CreateResident.php`
- `app/Filament/Resources/AppointmentResource.php`
- `app/Http/Controllers/Api/AppointmentController.php`
- `routes/api.php`
- `resources/js/pages/caregiver/ResidentDetailPage.jsx`
- `resources/js/pages/Appointments.jsx`
- `resources/js/components/Layout.jsx`

**Migration:**
- `database/migrations/2025_11_21_024916_create_resident_documents_table.php`
