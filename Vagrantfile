Vagrant.configure(2) do |config|
  config.vm.box = "ubuntu/trusty64"

  config.vm.network "forwarded_port", guest: 80, host: 1234

  config.vm.provision "shell", inline: <<-SHELL
    sudo apt-get update
    sudo apt-get install -y nginx
    sudo apt-get install -y php5-fpm 
    sudo apt-get install -y unzip
    sudo apt-get install -y wget
    wget -O /home/vagrant/file-api.zip https://github.com/shendykurnia/file-api/archive/master.zip
    unzip /home/vagrant/file-api.zip -d /home/vagrant
    sudo cp /home/vagrant/file-api-master/confs/nginx-file-api /etc/nginx/sites-available/file-api
    sudo ln -s /etc/nginx/sites-enabled/file-api /etc/nginx/sites-enabled/file-api
    sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password password'
    sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password password'
    sudo apt-get install -y mysql-server
  SHELL
end
