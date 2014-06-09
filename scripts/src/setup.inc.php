<?php
function getLinuxDistro()
{
   # declare Linux distros (extensible list)
   $distros = array 
   (
      "Arch" => "arch-release",
      "Debian" => "debian_version",
      "Fedora" => "fedora-release",
      "Ubuntu" => "lsb-release",
      'Redhat' => 'redhat-release',
      'CentOS' => 'centos-release',
   );

   # make sure we're really running on Linux
   $operating_system = php_uname('s');
   if ($operating_system != 'Linux')
   {
      return -1;
   }

   # Get everything from /etc directory.
   $directory_list = scandir('/etc');

   $flag = '';
   foreach ($distros as $name=>$file)
   {
      if (in_array($file,$directory_list))
      {
         $flag = $name;
	 break;
      } 
   }
   if (empty($flag))
   {
      return -2;
   }
   else
   {
      return $name;
   }
}



function installDependencies() {
   // apt-get update
   // apt-get install -y git mc ssh gawk grep sudo htop mysql-client php5-cli
   // apt-get autoclean
}


function installDocker()
{
      shell_exec('curl -s get.docker.io | sh 2>&1 | egrep -i -v "Ctrl|docker installed"');
}


