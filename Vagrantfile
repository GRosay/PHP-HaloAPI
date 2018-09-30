Vagrant.configure("2") do |config|
  config.vm.provider "virtualbox" do |v|
    v.customize ["setextradata", :id, "VBoxInternal2/SharedFoldersEnableSymlinksCreate/v-root", "1"]
  end
  config.vm.box = "bento/ubuntu-16.04"
  config.vm.provision :shell, path: "bootstrap.sh"
  config.vm.network :forwarded_port, guest: 80, host: 8092
  config.vm.network :forwarded_port, guest: 3306, host: 3307
end
