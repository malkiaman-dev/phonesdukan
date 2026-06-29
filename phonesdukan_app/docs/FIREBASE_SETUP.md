# Firebase Cloud Messaging — Backend Integration Guide

## Quick Setup Checklist

- [ ] Create Firebase project at https://console.firebase.google.com
- [ ] Add Android app: `com.phonesdukan.app`
- [ ] Download `google-services.json` → `android/app/google-services.json`
- [ ] Run `flutterfire configure` to generate `lib/firebase_options.dart`
- [ ] Enable Cloud Messaging API in Google Cloud Console
- [ ] Test with Firebase Console → Messaging → Send test message

## Notification Topics

The app auto-subscribes users to these topics on launch:

| Topic | Use Case |
|-------|----------|
| `general` | Site-wide announcements |
| `promotions` | Sales and seasonal campaigns |
| `order_updates` | Order status changes |
| `new_offers` | New product launches |

## Send via Firebase Console

1. Firebase Console → Engage → Messaging → **Create campaign**
2. Target: Topic → e.g. `promotions`
3. Add notification title and body
4. Under **Additional options** → Custom data:
   - Key: `url`
   - Value: `https://phonesdukan.com/your-page/`

Tapping the notification opens that URL in the WebView.

## Send via REST API (PHP example)

```php
<?php
$serverKey = 'YOUR_FIREBASE_SERVER_KEY'; // Project Settings → Cloud Messaging → Server key

$payload = [
    'to' => '/topics/promotions',
    'notification' => [
        'title' => 'Flash Sale!',
        'body'  => 'Up to 20% off selected mobiles',
    ],
    'data' => [
        'url' => 'https://phonesdukan.com/mobiles/',
    ],
];

$ch = curl_init('https://fcm.googleapis.com/fcm/send');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: key=' . $serverKey,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);
curl_close($ch);
```

## Send to Specific User (token-based)

Collect FCM token from app via your API endpoint, then:

```json
{
  "to": "DEVICE_FCM_TOKEN_HERE",
  "notification": {
    "title": "Order Shipped",
    "body": "Your order #1234 is on the way!"
  },
  "data": {
    "url": "https://phonesdukan.com/dashboard/orders/"
  }
}
```

## Future Backend Endpoint

Add an API on your website to register tokens:

```
POST /api/register-fcm-token
Body: { "user_id": 123, "fcm_token": "...", "platform": "android" }
```

Store in database table `user_fcm_tokens` for targeted order notifications.
