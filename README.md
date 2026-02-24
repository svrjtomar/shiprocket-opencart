
# Shiprocket Order Push (Admin Only) – OpenCart Extension

## Overview
This extension allows OpenCart admins to **push orders to Shiprocket**, retry failed pushes, sync shipment statuses, and manage COD/Prepaid logic — **without adding a shipping method or affecting checkout**.

---

## Features
- Push orders to Shiprocket from Admin
- Retry failed Shiprocket pushes
- Manual “Sync Now” for shipment status
- COD / PREPAID detection (payment-code based)
- Last Sync timestamp per order
- Admin-only (no catalog impact)
- Fully uninstallable

---

## Installation
1. Go to **Admin → Extensions → Installer**
2. Upload the provided ZIP file
3. Go to **Extensions → Modifications**
4. Click **Refresh**
5. Go to **System → Users → User Groups**
6. Grant **Access** and **Modify** permission for:
   ```
   extension/module/shiprocket
   ```
7. Logout and login again

---

## Troubleshooting

### Shiprocket menu not visible
Follow these steps **in order**:

1. **Refresh Modifications**
   - Admin → Extensions → Modifications → Refresh
   - Hard refresh browser (Ctrl + F5)

2. **Check User Group Permissions**
   - System → Users → User Groups
   - Add access & modify permission:
     ```
     extension/module/shiprocket
     ```

3. **Verify Extension Installed**
   - Admin → Extensions → Installer
   - Ensure Shiprocket extension is listed

4. **Clear Cache**
   - Browser hard refresh
   - CDN / Cloudflare cache (if used)
   - Try Incognito window

5. **Check Conflicting Extensions**
   - Disable other extensions that modify admin menu
   - Refresh Modifications again

6. **Reinstall (Last Resort)**
   - Remove extension
   - Refresh Modifications
   - Re-upload ZIP
   - Refresh again

Expected menu:
```
Shiprocket
 ├─ Orders
 └─ Settings
```

---

## FAQ

### Does this add a shipping method?
❌ No. This extension is **admin-only**.

### Does it affect checkout or customers?
❌ No impact on frontend or checkout.

### How is COD / Prepaid detected?
Using OpenCart’s **payment_code**:
- `cod` → COD
- everything else → PREPAID

### Can I retry failed orders?
✅ Yes. Failed orders show a **Retry** button.

### Is background cron required?
❌ No. Manual “Sync Now” is sufficient.

---

## Common Error Messages

### “Order already pushed to Shiprocket”
- Order was already successfully pushed
- Retry only works for **failed** orders

### “Shiprocket token missing”
- Email/password not saved in Settings
- Or Shiprocket API unreachable

### “Shiprocket Error: Invalid Data”
- Order missing required fields
- Check address, phone, order items

### Admin page shows 500 error
- Usually caused by PHP syntax error
- Check `system/storage/logs/error.log`

---

## Upgrade Instructions

1. Remove old extension from **Extensions → Installer**
2. Refresh Modifications
3. Upload new ZIP
4. Refresh Modifications again
5. No database changes required

Existing Shiprocket data is preserved.

---

## Uninstall & Cleanup

### Standard Uninstall
1. Admin → Extensions → Installer → Remove
2. Extensions → Modifications → Refresh



⚠️ Optional — only if you want a clean reset.

---

## Notes
- Admin-only extension
- Safe to uninstall anytime
- No core files overwritten
- Uses OCMOD for menu injection

---

© Shiprocket Order Push – OpenCart
