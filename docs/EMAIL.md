# Email Notification Setup

RFQ 儲存成功後，可用 Resend 寄後台通知信。

## config/app.php
```php
'email' => [
  'enabled' => true,
  'provider' => 'resend',
  'resend_api_key' => 're_xxxxxxxxx',
  'from' => 'RFQ <no-reply@phoohotshu.tw>',
  'to' => '你的收件信箱',
],
```

## 原則
- Database commit 成功 = RFQ 成功。
- Email 只是通知，不是資料來源。
- Resend 失敗時 API 仍回傳已成功保存的 RFQ ID。
- 正式啟用前需在 Resend 驗證寄件網域。
