# Troubleshooting Blank Screen Issue

## Current Issue
Seeing "@vitejs/plugin-react can't detect preamble" error on `Layout.jsx:298`.

## Steps to Fix

1. **Kill all Vite processes:**
   ```bash
   pkill -f vite
   ```

2. **Clear Vite cache:**
   ```bash
   rm -rf node_modules/.vite public/build/.vite .vite
   ```

3. **Rebuild:**
   ```bash
   npm run build
   ```

4. **Restart dev server:**
   ```bash
   npm run dev
   ```

5. **Access the app:**
   - Make sure Laravel server is running: `php artisan serve`
   - Visit: `http://127.0.0.1:8000/app`

## Alternative: Use Production Build

If dev server continues to have issues:

```bash
npm run build
php artisan serve
```

Then access: `http://127.0.0.1:8000/app`

The production build should work without the dev server running.

