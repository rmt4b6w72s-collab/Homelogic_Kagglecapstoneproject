# React Frontend Setup Instructions

## Development Mode (Recommended)

To see your React app working, you need to run **two servers**:

1. **Laravel server** (terminal 1):
   ```bash
   php artisan serve
   ```

2. **Vite dev server** (terminal 2):
   ```bash
   npm run dev
   ```

Then access your app at: `http://127.0.0.1:8000/app`

## Why Two Servers?

- **Laravel server** (port 8000): Serves the Laravel application and routes
- **Vite dev server** (port 5173): Serves React assets with hot-reloading during development

## Production Mode

For production, build the assets first:

```bash
npm run build
php artisan serve
```

Then access at: `http://127.0.0.1:8000/app`

## Troubleshooting Blank Screen

If you see a blank screen:

1. **Check if Vite dev server is running**: Look for `npm run dev` output showing port 5173
2. **Open browser console** (F12): Check for JavaScript errors
3. **Check Network tab**: Look for failed requests to port 5173
4. **Make sure both servers are running**: Laravel (8000) and Vite (5173)

## Quick Start

```bash
# Terminal 1
php artisan serve

# Terminal 2  
npm run dev

# Then visit http://127.0.0.1:8000/app
```

