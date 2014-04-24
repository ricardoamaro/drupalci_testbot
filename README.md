## Docker Drupal testbots on your local box!

### Test your Drupal patches locally with docker.

This repo contains a recipe for making a [Docker](http://docker.io) containers for Drupal patch testing, using Linux, Apache, PHP and MySQL/sqlite. 

This is part of the core code powering the future version of [Drupal automated-testing](https://drupal.org/automated-testing) infrastructure at http://qa.drupal.org .

#### Why is this awesome?
a) Test patches on your local box or http://qa.drupal.org  
b) Test multiple patches and multiple modules at once.      
c) Test any Drupal version.  
d) Get realtime output.  
e) Choose mysql, sqlite (more comming soon)  
f) Choose PHP5.3/5.4/5.5  
g) Test offline.   
h) It's really really easy!
    
    
    
## Quick Linux Instructions (for the impatient):

### (re)Build all and start containers (only once): 
```
git clone {thisrepo}
cd modernizing_testbot__dockerfiles
sudo ./build_all.sh cleanup
```

## Quick Vagrant MAC/Windows instructions:
This will not run natively since it's a Virtualbox VM
and you need to install Vagrant.

```
git clone {thisrepo}
cd modernizing_testbot__dockerfiles
vagrant up

```    

### Run some group tests:
```
sudo TESTGROUPS="Action,Bootstrap" DRUPALBRANCH="8.x" PATCH="/path/to/your.patch,." ./run.sh
```
See more examples bellow on: "6- RUN EXAMPLES"   

    
    
## Full Instructions:

### 1- Install docker:
```
curl get.docker.io | sudo sh -x
```

### 2- Clone this repo somewhere in your Linux box
```
git clone {thisrepo}
cd modernizing_testbot__dockerfiles
```
### 3- Build the database image 
```
cd distributed/database/mysql
sudo ./build.sh 
```
### 4- Start the DB container and check it's running on port 3306
```
cd distributed/database/mysql
sudo ./run-server.sh 
```

### 5- Build the WEB image
```
cd distributed/apachephp/
sudo ./build.sh 5.4
```
### 6- RUN EXAMPLES:

**Results will be available at:**  
**{USERHOME}/testbotdata/BUILD_{DATE}/results**  
**and at the live running terminal**

Run 'search_api' module tests, with one patch against D8 and git sandbox:
```
sudo TESTGROUPS="--module 'search_api'" \
DEPENDENCIES_GIT="http://git.drupal.org/sandbox/daeron/2091893.git,master" \
PATCH="https://drupal.org/files/issues/2232253-3.patch,modules/2091893" \
DRUPALBRANCH="8.x" \
./run.sh
```

Run Action and Node tests, 2 LOCAL patches, using 4 CPUs, against D8:
```
cd distributed/apachephp/

sudo \
TESTGROUPS="Action,Node" \
CONCURRENCY="4" \
DRUPALBRANCH="8.x" \
PATCH="/tmp/1942178-config-schema-user-28.patch,.;/tmp/1942178-config-schema-30.patch,." \
./run.sh
```
Run all tests using 4 CPUs, 1 core patch against D8:   
```
cd distributed/apachephp/

sudo \
TESTGROUPS="--all" \
CONCURRENCY="4" \
DRUPALBRANCH="8.x" \ 
PATCH="https://drupal.org/files/issues/1942178-config-schema-user-28.patch,." \
./run.sh
```

Run all tests using 6 CPUs, 2 patches and 2 modules on D7.26:  
```
cd distributed/apachephp/

sudo \
TESTGROUPS="--all" \
CONCURRENCY="6" \
DRUPALBRANCH="7.26" \
DEPENDENCIES="flag,payment"  \
PATCH="https://drupal.org/files/issues/flag_fix_global_flag_uid_2087797_3.patch,sites/all/modules/flag;https://drupal.org/files/issues/payment_2114785_8.patch,sites/all/modules/payment" \
./run.sh 
```


And that's it.


### Some default environment variables that you can override

```
DRUPALBRANCH="8.x"
DRUPALVERSION=""
IDENTIFIER="BUILD_$(date +%Y_%m_%d_%H%M%S)"
REPODIR="$HOME/testbotdata"
UPDATEREPO="false"  # true to force repos update
BUILDSDIR="$REPODIR"
WORKSPACE="$BUILDSDIR/$IDENTIFIER/"
DEPENDENCIES=""     # module1,module2,module2...
DEPENDENCIES_GIT="" # gitrepo1,branch;gitrepo2,branch;...
DEPENDENCIES_TGZ="" # TODO
PATCH=""            # patch_url,apply_dir;patch_url,apply_dir;...
DBUSER="drupaltestbot" 
DBPASS="drupaltestbotpw"
DBTYPE="mysql"
DBLINK="--link=drupaltestbot-db:db"
CMD=""              # Eg. enter container shell with CMD="/bin/bash"
VERBOSE="false"     # true will give verbose
PHPVERSION="5.4"
CONCURRENCY="4"     # How many cpus to use per run
TESTGROUPS="--class 'Drupal\block\Tests\NonDefaultBlockAdminTest'" #TESTS TO RUN eg.--all
RUNSCRIPT="php ./scripts/run-tests.sh --php /usr/bin/php --url 'http://localhost' --color --concurrency ${CONCURRENCY} --xml '/var/workspace/results' ${TESTGROUPS} "
```

### What tests can I run?
```
sudo \
DRUPALBRANCH="8.x" \
RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" \
./run.sh
```

If you need to remove the old web image just run this sequence:
```
sudo docker images | grep "drupal/testbot-web" | awk '{print $3}' | xargs -n1 -I {} sudo docker rm {}
```

### 7 - Clean Up 

a) Results will be saved at: 
**{USERHOME}/testbotdata/BUILD_{DATE}/results/** 
so you can delete testbotdata/BUILD_{DATE} after you collect your information

b) Docker generates several runs: 
While i am developing i use this to rm all old instances
```
sudo docker ps -a | awk '{print $1}' | xargs -n1 -I {} sudo docker rm {}
``` 

## Current Structure:
```
.
├── build_all.sh
├── D7TestGroupsClasses.txt
├── D8TestGroupsClasses.txt
├── distributed
│   ├── apachephp
│   │   ├── build.sh
│   │   ├── conf
│   │   │   ├── apache2
│   │   │   │   └── vhost.conf
│   │   │   ├── php5
│   │   │   │   ├── apache2.ini
│   │   │   │   ├── apc.ini
│   │   │   │   └── cli.ini
│   │   │   ├── scripts
│   │   │   │   ├── foreground.sh
│   │   │   │   └── start.sh
│   │   │   └── supervisor
│   │   │       └── supervisord.conf
│   │   ├── Dockerfile -> Dockerfile-PHP5.4
│   │   ├── Dockerfile-PHP5.3
│   │   ├── Dockerfile-PHP5.4
│   │   ├── Dockerfile-PHP5.5
│   │   └── run.sh
│   └── database
│       └── mysql
│           ├── build.sh
│           ├── conf
│           │   └── startup.sh
│           ├── Dockerfile
│           ├── run-client.sh
│           ├── run-server.sh
│           └── stop-server.sh
├── patch.p1
├── provision.sh
├── README.md
├── run.sh -> ./distributed/apachephp/run.sh
├── supervisord
│   ├── build.sh
│   ├── default
│   ├── Dockerfile
│   ├── php-cli.ini
│   ├── php.ini
│   ├── run.sh
│   └── supervisord.conf
└── Vagrantfile

```
###CREDITS:
jthorson
ricardoamaro
nickschuch
beejeebus
dasrecht


## Contributing
Feel free to fork and contribute to this code. :)

1. Fork the repo
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

