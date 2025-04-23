# Deploy

To EC2.

```bash
[local] ssh -i ~/.ssh/exnota-docs-ec2.pem ubuntu@18.188.113.184

[server] sudo su exnota-docs

[server] cd ~/www

[server] git pull origin main

[server]  php artisan queue:restart
```

## Worker config

In /etc/supervisor/conf.d/exnota-docs-workers.conf.

### Update supervisor with changes to config:

```bash
# Make changes
sudo supervisorctl reread
sudo supervisorctl update

# Check status
sudo supervisorctl status
```
