# v4.6 Final RFQ UAT

## Backend
- [ ] MySQL schema imported
- [ ] config/app.php exists and is NOT committed
- [ ] /api/health.php returns {"ok":true,"db":"connected"}
- [ ] private upload dir is not publicly readable
- [ ] admin password hash configured
- [ ] Resend config enabled only after domain verification

## RFQ happy path
- [ ] Open /quote/
- [ ] Fill name/contact/city/request
- [ ] Add 1–5 valid JPG/PNG/WebP photos
- [ ] Submit
- [ ] API HTTP 201
- [ ] Success page shows real RFQ ID
- [ ] rfqs row exists
- [ ] rfq_photos rows exist
- [ ] files exist in private storage
- [ ] status history starts at new
- [ ] Dashboard shows RFQ
- [ ] Admin can open RFQ detail
- [ ] Admin photo viewer works only after login
- [ ] Status update writes history

## Failure tests
- [ ] Missing privacy consent => 422
- [ ] Missing required fields => 422
- [ ] More than 5 photos rejected
- [ ] File > 8 MB rejected
- [ ] Non-image masquerading as JPG rejected
- [ ] Direct private-storage URL denied
- [ ] Wrong admin password denied
- [ ] Logout ends session
- [ ] Email failure does NOT delete RFQ

## Frontend
- [ ] Mobile form usable
- [ ] Photo examples load
- [ ] No fake success before API returns 201
- [ ] No customer PII sent to analytics
