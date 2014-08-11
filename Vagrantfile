# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = "debian72"
  config.vm.box_url = "https://dl.dropboxusercontent.com/u/197673519/debian-7.2.0.box"
  config.vm.provision :shell, :path => "provision.sh"
  config.vm.network :private_network, ip: "192.168.42.42"
  config.vm.synced_folder ".", "/home/vagrant/drupalci_testbot", type: "rsync"
  config.vm.define "testbot" do |testbot|
      testbot.vm.provider "virtualbox" do |v|
        v.customize [ "modifyvm", :id, "--cpus", "4" ]
        v.customize [ "modifyvm", :id, "--memory", "756" ]
        v.customize [ "modifyvm", :id, "--natdnshostresolver1", "on" ]
      end
  end
end


