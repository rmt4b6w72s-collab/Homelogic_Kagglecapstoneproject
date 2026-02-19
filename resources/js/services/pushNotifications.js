/**
 * Push Notification Service
 * Handles push notification subscription and management
 */

import api from './api';

const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY || '';

/**
 * Request push notification permission
 */
export async function requestPermission() {
  if (!('Notification' in window)) {
    console.warn('[PushNotifications] Notifications not supported');
    return false;
  }

  if (Notification.permission === 'granted') {
    return true;
  }

  const permission = await Notification.requestPermission();
  return permission === 'granted';
}

/**
 * Subscribe to push notifications
 */
export async function subscribeToPush() {
  if (!('serviceWorker' in navigator)) {
    console.warn('[PushNotifications] Service Worker not supported');
    return null;
  }

  if (!('PushManager' in window)) {
    console.warn('[PushNotifications] Push Manager not supported');
    return null;
  }

  try {
    // Request permission first
    const hasPermission = await requestPermission();
    if (!hasPermission) {
      console.warn('[PushNotifications] Permission denied');
      return null;
    }

    // Get service worker registration
    const registration = await navigator.serviceWorker.ready;

    // Check if already subscribed (re-sync with backend in case it was lost)
    let subscription = await registration.pushManager.getSubscription();
    if (subscription) {
      console.log('[PushNotifications] Already subscribed, syncing with backend');
      await sendSubscriptionToBackend(subscription).catch(() => {});
      return subscription;
    }

    // Subscribe to push service
    if (!VAPID_PUBLIC_KEY) {
      console.warn('[PushNotifications] VAPID public key not configured');
      return null;
    }

    subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
    });

    console.log('[PushNotifications] Subscribed to push notifications');

    // Send subscription to backend (uses api so Bearer token is sent)
    await sendSubscriptionToBackend(subscription);

    return subscription;
  } catch (error) {
    console.error('[PushNotifications] Failed to subscribe:', error);
    return null;
  }
}

/**
 * Unsubscribe from push notifications
 */
export async function unsubscribeFromPush() {
  if (!('serviceWorker' in navigator)) {
    return false;
  }

  try {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();

    if (subscription) {
      await subscription.unsubscribe();
      
      // Remove from backend
      await removeSubscriptionFromBackend(subscription);
      
      console.log('[PushNotifications] Unsubscribed from push notifications');
      return true;
    }

    return false;
  } catch (error) {
    console.error('[PushNotifications] Failed to unsubscribe:', error);
    return false;
  }
}

/**
 * Check if user is subscribed
 */
export async function isSubscribed() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    return false;
  }

  try {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();
    return subscription !== null;
  } catch (error) {
    console.error('[PushNotifications] Failed to check subscription:', error);
    return false;
  }
}

/**
 * Get current subscription
 */
export async function getSubscription() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    return null;
  }

  try {
    const registration = await navigator.serviceWorker.ready;
    return await registration.pushManager.getSubscription();
  } catch (error) {
    console.error('[PushNotifications] Failed to get subscription:', error);
    return null;
  }
}

/**
 * Send subscription to backend (uses api client for auth)
 */
async function sendSubscriptionToBackend(subscription) {
  try {
    const contentEncoding = subscription.options?.applicationServerKey ? 'aes128gcm' : 'aesgcm';
    await api.post('/push-subscriptions', {
      endpoint: subscription.endpoint,
      keys: {
        p256dh: arrayBufferToBase64(subscription.getKey('p256dh')),
        auth: arrayBufferToBase64(subscription.getKey('auth')),
      },
      content_encoding: contentEncoding,
    });
    console.log('[PushNotifications] Subscription saved to backend');
  } catch (error) {
    console.error('[PushNotifications] Failed to save subscription:', error);
    throw error;
  }
}

/**
 * Remove subscription from backend (uses api client for auth)
 */
async function removeSubscriptionFromBackend(subscription) {
  try {
    await api.delete('/push-subscriptions', {
      data: { endpoint: subscription.endpoint },
    });
    console.log('[PushNotifications] Subscription removed from backend');
  } catch (error) {
    console.error('[PushNotifications] Failed to remove subscription:', error);
    throw error;
  }
}

/**
 * Convert VAPID key from URL-safe base64 to Uint8Array
 */
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }

  return outputArray;
}

/**
 * Convert ArrayBuffer to base64
 */
function arrayBufferToBase64(buffer) {
  const bytes = new Uint8Array(buffer);
  let binary = '';
  for (let i = 0; i < bytes.byteLength; i++) {
    binary += String.fromCharCode(bytes[i]);
  }
  return window.btoa(binary);
}
