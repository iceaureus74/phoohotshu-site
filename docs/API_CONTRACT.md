# RFQ API Contract v4.6

## Endpoint
`POST /api/submit-rfq.php`

Content type: `multipart/form-data`

## Required fields
- `customer_name`
- `contact_value`
- `consent_privacy=1`

## Optional fields
- `city`
- `district`
- `address_text`
- `area_ping`
- `space_type`
- `request_type`
- `product_interest`
- `existing_floor`
- `floor_issue`
- `furniture_condition`
- `preferred_timing`
- `customer_note`
- `landing_page`
- `source_page`
- `referrer_url`
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_content`
- `utm_term`
- `consent_case_publication` (default 0)
- `photos[]` (max 5)

## Photos
Allowed:
- JPEG
- PNG
- WebP

Default max:
- 8 MB each
- 5 files total

Validation:
- PHP upload error
- size
- `finfo` MIME type
- `getimagesize`
- random server-side filename
- SHA-256
- private storage

## Success
HTTP 201

```json
{
  "ok": true,
  "rfq_no": "PH-20260820-A1B2C3",
  "photo_count": 3
}
```

## Important
A real success response is returned only after:
1. database insert succeeds;
2. private files are stored;
3. photo rows are inserted;
4. status history is inserted;
5. database transaction commits.

Email is intentionally NOT part of the success condition.
