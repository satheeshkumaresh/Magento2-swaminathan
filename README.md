**Magento System Requirements**

PHP version : 8.1.x

PHP settings : memory_limit=2G

PHP Module :

ext-bcmath
ext-ctype
ext-curl
ext-dom
ext-fileinfo
ext-gd
ext-hash
ext-iconv
ext-intl
ext-json
ext-libxml
ext-mbstring
ext-openssl
ext-pcre
ext-pdo_mysql
ext-simplexml
ext-soap
ext-sockets
ext-sodium
ext-xmlwriter
ext-xsl
ext-zip
lib-libxml
lib-openssl

Composer version : 2.2

Mysql version : 8.0

Redis version : 6.2

Elasticsearch version : 7.17

Varnish version  : 7.0

Authentication keys: 12ba0917885b029817ce279267c5306c

Authentication Password: ae36619baf197fbfdedf92f06dea7651


Sample Nginx 
Magento-root/nginx.conf.sample

Sample env.php
Magento-root/env.php.sample

Sample config.php
Magento-root/config.php.sample

**Install Magento**

Installation Guide:-

Clones the Magento 2 repository.

git clone git@github.com:NavabrindIT/swaminathan-magento.git

composer install

Install Magento CMD. Review values in CMD and add/change if required

sudo php bin/magento setup:install --base-url="http://swaminathan.loc" --db-host="localhost" --db-name="swaminathan_loc" --db-user="root" --db-password="12345678" --admin-firstname="admin" --admin-lastname="admin" --admin-email="admin@swaminathan.com" --admin-user="admin" --admin-password="Admin@123" --language="en_US" --currency="INR" --timezone="Asia/Kolkata" --use-rewrites="1" --backend-frontname="admin"

rm -rf var/page_cache/* var/cache/* generated/code/* generated/metadata/* var/view_preprocessed/*

bin/magento setup:upgrade

bin/magento setup:di:compile

bin/magento setup:static-content:deploy -f 

bin/magento cache:flush

bin/magento indexer:reindex

chmod -R 775 .

chown -R {web-user}:{web-user} .

find var generated pub/static pub/media app/etc  -type f -exec chmod g+w {} \;

find var generated pub/static pub/media app/etc  -type d -exec chmod g+ws {} \;

sudo chmod u+x bin/magento


**Backend Configuration:**

Backend admin users will be created if required or backend login details will be shared by the project team.

**Docker Set up:**

Docker setup will be handled by respective devops team members.

