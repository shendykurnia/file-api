Vagrant.configure(2) do |config|
  config.vm.box = "ubuntu/trusty64"

  config.vm.network "forwarded_port", guest: 80, host: 1234

  config.vm.provision "shell", inline: <<-SHELL
    sudo apt-get update
    sudo apt-get install -y nginx
    sudo apt-get install -y php5-fpm
    sudo apt-get install -y php5-mysql
    sudo apt-get install -y unzip
    sudo apt-get install -y wget
    
    sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password password'
    sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password password'
    sudo apt-get install -y mysql-server
    sudo apt-get install -y mysql-client
    mysql -hlocalhost -uroot -ppassword -e 'create database file_api'

    wget -O /home/vagrant/file-api.zip https://github.com/shendykurnia/file-api/archive/master.zip
    unzip /home/vagrant/file-api.zip -d /home/vagrant
    
    sudo cp /home/vagrant/file-api-master/confs/nginx-file-api /etc/nginx/sites-available/file-api
    sudo ln -s /etc/nginx/sites-available/file-api /etc/nginx/sites-enabled/file-api
    mkdir -p /home/vagrant/file-api-master/www/application/config/development
    sudo cp /home/vagrant/file-api-master/confs/database.php /home/vagrant/file-api-master/www/application/config/development/database.php
    sudo chown -R vagrant:www-data /home/vagrant/file-api-master/www
    sudo chmod -R ug+rw /home/vagrant/file-api-master/www

    mkdir -p /home/vagrant/file-api-master/www/application/logs
    sudo chown -R vagrant:www-data /home/vagrant/file-api-master/www/application/logs
    sudo chmod -R ug+rw /home/vagrant/file-api-master/www/application/logs
    mkdir -p /home/vagrant/file-api-master/www/application/cache
    sudo chown -R vagrant:www-data /home/vagrant/file-api-master/www/application/cache
    sudo chmod -R ug+rw /home/vagrant/file-api-master/www/application/cache

    mkdir -p /home/vagrant/file-api-master/uploaded-files
    sudo chown -R vagrant:www-data /home/vagrant/file-api-master/uploaded-files
    sudo chmod ug+w /home/vagrant/file-api-master/uploaded-files

    sudo service nginx reload
  SHELL
end
