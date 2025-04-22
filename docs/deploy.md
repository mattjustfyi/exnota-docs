# Deploy

To EC2.

```bash
[local] ssh -i ~/.ssh/exnota-docs-ec2.pem ubuntu@18.188.113.184

[server] cd ~/www

[server] git pull origin main

[server] php artisan queue:restart 
```
