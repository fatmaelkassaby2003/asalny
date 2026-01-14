# إصلاح إعدادات Pusher على السيرفر

## المشكلة المكتشفة

من اختبار `/api/test-pusher`:
```json
{
  "pusher_configured": false,
  "pusher_key": "",
  "broadcast_driver": "log"
}
```

**السبب**: ملف `.env` على السيرفر مش محدّث!

---

## الحل السريع

### على السيرفر، شغل:

```bash
cd /home/u141368153/public_html/AsalnyApi

# اعرض محتوى .env الحالي
cat .env | grep PUSHER

# المفروض يطلع:
# PUSHER_APP_ID=2059350
# PUSHER_APP_KEY=2afd3f716f5102b15dfc
# PUSHER_APP_SECRET=4ebf9d3800c4385c6e01
# PUSHER_APP_CLUSTER=eu
# BROADCAST_DRIVER=pusher
```

---

## لو البيانات غلط أو فاضية

**ارفع ملف** [.env.production](file:///d:/Kiyan/Asalny/.env.production) على السيرفر:

1. استخدم **FTP** أو **cPanel File Manager**
2. ارفعه في: `/home/u141368153/public_html/AsalnyApi/`
3. **احذف** الملف `.env` القديم
4. **غير اسم** `.env.production` → `.env`

---

## بعد رفع الملف، شغل:

```bash
cd /home/u141368153/public_html/AsalnyApi

# Cache الإعدادات الجديدة
php artisan config:cache

# Optimize
php artisan optimize
```

---

## التحقق من نجاح العملية

جرب الـ endpoint تاني:
```
GET https://asalny.ahdafweb.com/api/test-pusher
```

**النتيجة المتوقعة**:
```json
{
  "pusher_configured": true,
  "pusher_key": "2afd3f716f5102b15dfc",
  "broadcast_driver": "pusher"
}
```

راقب **Pusher Debug Console** - هتلاقي الرسالة ظهرت!
