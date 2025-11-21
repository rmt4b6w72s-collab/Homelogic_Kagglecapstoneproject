# Post-Deployment Checklist

## ✅ Deployment Complete!

Your resident document management feature has been successfully deployed. Follow these steps to verify everything is working:

## 1. Verify Database Migration

The following migration should be marked as "Ran":
- `2025_11_21_024916_create_resident_documents_table`

**Check command:**
```bash
php artisan migrate:status | grep resident_documents
```

## 2. Test Resident Document Upload

### Via React Frontend:
1. **Navigate to a Resident**
   - Go to "My Residents" in the main menu
   - Click on any resident to view their detail page
   - Click the "Documents" tab

2. **Upload a Document**
   - Click "Add Document" button
   - Fill in:
     - Document Name (required)
     - Document Type (required)
     - Document File (required) - PDF, JPG, PNG, GIF, DOC, DOCX (max 10MB)
     - Related Appointment (optional)
     - Notes (optional)
   - Click "Create"
   - Verify document appears in the list

3. **Test Document Actions**
   - ✅ View document in list
   - ✅ Download document
   - ✅ Edit document metadata
   - ✅ Delete document

### Via Filament Admin Panel:
1. **Navigate to Residents**
   - Go to Administration > Residents
   - Edit any resident
   - Click on "Documents" tab in the relation manager

2. **Upload Document**
   - Click "Create" button
   - Fill in document details
   - Upload file
   - Save and verify

## 3. Test Document Upload During Resident Creation

1. Go to Administration > Residents > Create
2. Fill in resident information
3. In the "Documents" section, add one or more documents
4. Create the resident
5. Verify documents were saved with the resident

## 4. Test Document Upload When Completing Appointments

1. Go to Appointments
2. Find a scheduled appointment
3. Click "Mark Completed"
4. In the completion modal:
   - Add appointment notes
   - Add one or more documents (optional)
   - Click "Mark as Completed"
5. Verify:
   - Appointment status changed to "completed"
   - Documents appear in resident's document list
   - Documents are linked to the appointment

## 5. Verify Permissions

1. **Check User Permissions**
   - Go to Administration > Roles & Permissions
   - Verify roles have:
     - `view_resident_documents`
     - `upload_resident_documents`

2. **Test with Different Roles**
   - Test as Administrator (should have full access)
   - Test as Caregiver (should have access to their residents' documents)
   - Test as other roles

## 6. Verify Storage

1. **Check Storage Directory**
   ```bash
   ls -la storage/app/public/resident-documents
   ```
   - Directory should exist
   - Should have proper permissions (775)

2. **Check Storage Link**
   ```bash
   ls -la public/storage
   ```
   - Should be a symbolic link to `storage/app/public`

3. **Test File Access**
   - Upload a document
   - Try to download it
   - Verify file opens correctly

## 7. Verify API Endpoints

Test the API endpoints (if using API):
- `GET /api/v1/resident-documents?resident_id={id}` - List documents
- `POST /api/v1/resident-documents` - Upload document
- `GET /api/v1/resident-documents/{id}` - Get document
- `PUT /api/v1/resident-documents/{id}` - Update document
- `DELETE /api/v1/resident-documents/{id}` - Delete document
- `GET /api/v1/resident-documents/{id}/download` - Download file

## 8. Clear Browser Cache

**Important:** Users should hard refresh their browsers to load the new JavaScript:
- **Windows/Linux**: `Ctrl + F5` or `Ctrl + Shift + R`
- **Mac**: `Cmd + Shift + R`

## 9. Monitor for Errors

1. **Check Laravel Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Check Browser Console**
   - Open browser developer tools (F12)
   - Check Console tab for JavaScript errors
   - Check Network tab for failed API requests

## 10. Common Issues & Solutions

### Documents Not Uploading
- ✅ Check file size (must be ≤ 10MB)
- ✅ Check file type (PDF, JPG, PNG, GIF, DOC, DOCX only)
- ✅ Check storage permissions: `chmod -R 775 storage/app/public/resident-documents`
- ✅ Check storage link exists: `php artisan storage:link`
- ✅ Check Laravel logs for validation errors

### Documents Not Showing
- ✅ Hard refresh browser (Ctrl+F5)
- ✅ Clear Laravel view cache: `php artisan view:clear`
- ✅ Rebuild frontend: `npm run build:production`

### Permission Errors
- ✅ Verify user has `view_resident_documents` permission
- ✅ Verify user has `upload_resident_documents` permission
- ✅ Check role permissions in Administration > Roles & Permissions

### 422 Validation Errors
- ✅ Check browser console for specific error messages
- ✅ Verify resident_id is being sent correctly
- ✅ Check file is actually selected before upload
- ✅ Verify all required fields are filled

## 11. Performance Check

- ✅ Verify document upload completes in reasonable time
- ✅ Check file download speed
- ✅ Monitor server resources during uploads

## 12. User Training

Inform your team about:
- ✅ New "Documents" tab on resident detail pages
- ✅ Ability to upload documents when creating residents
- ✅ Ability to upload documents when completing appointments
- ✅ Document types available (Insurance, Medical, Legal, Admission, Appointment, Other)
- ✅ File size and type limitations (10MB, PDF/Images/Word docs)

## Success Criteria

✅ All migrations ran successfully  
✅ Documents can be uploaded via React UI  
✅ Documents can be uploaded via Filament admin  
✅ Documents appear in resident's document list  
✅ Documents can be downloaded  
✅ Documents can be edited and deleted  
✅ Documents can be linked to appointments  
✅ Permissions are working correctly  
✅ No errors in logs or browser console  

## Next Steps

Once everything is verified:
1. ✅ Inform your team about the new feature
2. ✅ Update any user documentation
3. ✅ Monitor usage and gather feedback
4. ✅ Consider adding more document types if needed

---

**Need Help?**
- Check `PRODUCTION_DEPLOYMENT_SUMMARY.md` for detailed feature documentation
- Check `DEPLOYMENT_CHECKLIST.md` for technical details
- Review Laravel logs: `storage/logs/laravel.log`


