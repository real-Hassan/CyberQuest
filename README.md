```markdown
# CyberQuest Platform

A LAMP stack cybersecurity learning platform.

## Setup Instructions

To run this project locally, you will need a standard LAMP (Linux, Apache, MySQL, PHP) stack installed.

### 1. Database Setup

1. Open MySQL in your terminal:
   ```bash
   sudo mysql

```

2. Create the database:
```sql
CREATE DATABASE cyberquest_db;

```


3. Import the database structure:
```bash
mysql -u root -p cyberquest_db < cyberquest_db.sql

```



> **Note:** Check `config/db.php` and update the database credentials if your local MySQL uses a different username or password.

### 2. Apache Virtual Host Configuration

Create a new configuration file for Apache to serve this site.

1. Open the file in your text editor:
```bash
sudo nano /etc/apache2/sites-available/cyberQuest.conf

```


2. Paste the following configuration:
```apache
<VirtualHost *:80>
    ServerName cyberquest.local
    DocumentRoot /var/www/cyberQuest/public

    <Directory /var/www/cyberQuest/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/cyberQuest_error.log
    CustomLog ${APACHE_LOG_DIR}/cyberQuest_access.log combined
</VirtualHost>

```


3. Enable the site and restart Apache:
```bash
sudo a2ensite cyberQuest.conf
sudo systemctl restart apache2

```



### 3. Local DNS Mapping (/etc/hosts)

To make `http://cyberquest.local` resolve correctly on your machine:

1. Edit your hosts file:
```bash
sudo nano /etc/hosts

```


2. Add the following line at the top:
```text
127.0.0.1   cyberquest.local

```


3. Save the file. You can now access the platform in your browser at `http://cyberquest.local/index.php`.

```

```
