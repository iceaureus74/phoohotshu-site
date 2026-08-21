# 鋪好厝 v4.6 Final Consolidated

正式網域：https://phoohotshu.tw/

## Included
- v4.5 SEO / multi-page production architecture
- Local SEO: 桃園、新竹市、新竹縣部分地區
- FSPC content
- Guides content quality + independent WebP teaching assets
- MySQL RFQ persistence
- private photo uploads
- real RFQ ID
- admin login / dashboard
- authenticated private photo viewer
- RFQ status workflow
- Resend-ready email notification
- real frontend RFQ flow

## Production rule
GitHub `main` is the single source of truth.

## Before deploy
Do not commit `config/app.php`.
Create DB and private storage configuration first.
Run docs/UAT.md after deployment.
