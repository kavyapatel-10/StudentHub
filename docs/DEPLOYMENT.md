# Local deployment with an Apache virtual host (XAMPP)

1. Open `C:\xampp\apache\conf\extra\httpd-vhosts.conf` and add:
```apache
<VirtualHost *:80>
    ServerName studenthub.local
    DocumentRoot "C:/xampp/htdocs/studenthub"
    <Directory "C:/xampp/htdocs/studenthub">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
2. Open Notepad **as administrator**, edit `C:\Windows\System32\drivers\etc\hosts` and add the line `127.0.0.1  studenthub.local`.
3. Make sure `Include conf/extra/httpd-vhosts.conf` is not commented out in `C:\xampp\apache\conf\httpd.conf`.
4. Restart Apache and open <http://studenthub.local/>.

`AllowOverride All` is needed so the `.htaccess` files (blocking `storage/`, `includes/`, and PHP files in `uploads/`) work.

## Going further (advanced)
The static pages (HTML, CSS, JS, `data/*.json`) can be hosted on GitHub Pages or Netlify. Login, database and the API need PHP and MySQL, so
they only run on a PHP host or locally. Document that limitation in your report.

## Final checklist before submission
- [ ] Import `database/studenthub.sql` on a clean database and log in with both demo accounts
- [ ] Walk through every page from the menu (admin and student)
- [ ] Fill in `docs/TEST_REPORT.md` and attach screenshots
- [ ] Commit and push, then check the repository in the browser
