#!/usr/bin/env bash

# Based on https://gist.github.com/rrosiek/8190550


# Installs
echo -e "\n--- BOOTSTRAP.SH / LOG AT: vm_build.log ---\n"
echo -e "\n--- UPDATING PACKAGES LIST ---\n"
apt-get -qq update >> /vagrant/vm_build.log 2>&1

echo -e "\n--- INSTALL CURL ---\n"
apt-get -y install curl >> /vagrant/vm_build.log 2>&1
apt-get -y install php-curl >> /vagrant/vm_build.log 2>&1

echo -e "\n--- INSTALL PHP ---\n"
apt-get -y install php apache2 libapache2-mod-php php-curl php-gd php-mysql php-gettext php7.0-zip composer >> /vagrant/vm_build.log 2>&1

echo -e "\n--- INSTALL XDEBUG ---\n"
apt-get -y install php-xdebug >> /vagrant/vm_build.log 2>&1

echo -e "\n--- CONFIGURE XDEBUG ---\n"
echo '# Added for xdebug' >> /etc/php/7.0/apache2/php.ini
xdebugPath=$(find /usr/lib/php -name 'xdebug.so')
echo "zend_extension=\"$xdebugPath\"" >> /etc/php/7.0/apache2/php.ini
echo 'xdebug.remote_autostart=1' >> /etc/php/7.0/apache2/php.ini
echo 'xdebug.remote_enable=1' >> /etc/php/7.0/apache2/php.ini
echo 'xdebug.remote_host = localhost' >> /etc/php/7.0/apache2/php.ini
echo 'xdebug.remote_port=9000' >> /etc/php/7.0/apache2/php.ini
echo 'xdebug.remote_connect_back = 1' >> /etc/php/7.0/apache2/php.ini
echo 'xdebug.idekey = PhpStorm' >> /etc/php/7.0/apache2/php.ini
echo 'xdebug.profiler_enable=1' >> /etc/php/7.0/apache2/php.ini
echo 'xdebug.profiler_output_dir=/vagrant/tmp' >> /etc/php/7.0/apache2/php.ini
echo 'xdebug.remote_log=vagrant/xdebug.log' >> /etc/php/7.0/apache2/php.ini
echo 'xdebug.default_enable = 1' >> /etc/php/7.0/apache2/php.ini

echo -e "\n--- INSTALL COMPOSER ---\n"
curl --silent https://getcomposer.org/installer | php >> /vagrant/vm_build.log 2>&1
mv composer.phar /usr/local/bin/composer

echo -e "\n--- ENABLE REWRITING ---\n"
a2enmod rewrite >> /vagrant/vm_build.log 2>&1
sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf >> /vagrant/vm_build.log 2>&1
cp -arp /etc/apache2/mods-available/headers.load  /etc/apache2/mods-enabled/headers.load >> /vagrant/vm_build.log 2>&1

echo -e "\n--- SETUP VAGRANT AS WEB ROOT ---\n"
sed -i "s/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www/g" /etc/apache2/sites-enabled/000-default.conf >> /vagrant/vm_build.log 2>&1
if ! [ -L /var/www ]; then
  rm -rf /var/www >> /vagrant/vm_build.log 2>&1
  ln -fs /vagrant/ /var/www >> /vagrant/vm_build.log 2>&1
fi

echo -e "\n--- ENABLE PHP ERRORS ---\n"
sed -i "s/error_reporting = .*/error_reporting = ~E_DEPRECATED & E_ALL/" /etc/php/7.0/apache2/php.ini
sed -i "s/display_errors = .*/display_errors = On/" /etc/php/7.0/apache2/php.ini

echo -e "\n--- DISABLING SENDFILE IN APACHE TO AVOID VIRTUALBOX BUG ---\n" #https://www.vagrantup.com/docs/synced-folders/virtualbox.html
echo 'EnableSendfile Off' >> /etc/apache2/apache2.conf

echo -e "\n--- INSTALL LANGUAGE PACKS ---\n"
/usr/share/locales/install-language-pack fr_FR
/usr/share/locales/install-language-pack de_DE

echo -e "\n--- RESTARTING APACHE ---\n"
service apache2 restart >> /vagrant/vm_build.log 2>&1