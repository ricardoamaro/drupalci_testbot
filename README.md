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

## Instructions:

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
### 6- Examples:   
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
Run all tests using 4 CPUs, 1 core patch against D8:   
```
sudo \
TESTGROUPS="--all" \
CONCURRENCY="4" \
DRUPALBRANCH="8.x" \ PATCH="https://drupal.org/files/issues/1942178-config-schema-user-28.patch,." \
./run.sh
```
Get a list of all avaliable tests to run:
```
sudo \
DRUPALBRANCH="8.x" \
RUNSCRIPT="/usr/bin/php ./scripts/run-tests.sh --list" \
./run.sh
```

And that's it.


### Some default environment variables that you can override

```
DRUPALVERSION=""
DRUPALBRANCH="7"
IDENTIFIER="BUILD_$(date +%Y_%m_%d_%H%M%S)"
REPODIR="$HOME/testbotdata"
BUILDSDIR="$REPODIR"
WORKSPACE="$BUILDSDIR/$IDENTIFIER/"
DEPENDENCIES="" # module1,module2,module2...
PATCH="" # patch_location,apply_dir;patch_location,apply_dir;...
DBUSER="drupaltestbot" 
DBPASS="drupaltestbotpw"
DBTYPE="mysql"
DBLINK="--link=drupaltestbot-db:db"
CMD=""
PHPVERSION="5.4"
CONCURRENCY="4" #How many cpus to use per run

TESTGROUPS="--class NonDefaultBlockAdmin" #TESTS TO RUN eg.--all, --> https://api.drupal.org/api/drupal/classes/8

RUNSCRIPT="php ./scripts/run-tests.sh --php /usr/bin/php --url 'http://localhost' --color --concurrency ${CONCURRENCY} --xml '/var/workspace/results' ${TESTGROUPS} "
```

### What tests can I run?
```
drush eval 'var_dump(simpletest_test_get_all());'
```

If you need to remove the old web image just run this sequence:
```
sudo docker images | grep "drupal/testbot-web"
sudo docker rmi {imageID}
```

### Clean up all 
While i am developing i use this to rm all old instances
```
sudo docker ps -a | awk '{print $1}' | xargs -n1 -I {} sudo docker rm {}
``` 

## Current Structure:
```
.
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
│           ├── Dockerfile
│           ├── run-client.sh
│           ├── run-server.sh
│           ├── startup.sh
│           └── stop-server.sh
├── README.md
└── supervisord
    ├── build.sh
    ├── default
    ├── Dockerfile
    ├── php-cli.ini
    ├── php.ini
    ├── run.sh
    └── supervisord.conf

```
###CREDITS:
jthorson
ricardoamaro
nickschuch
beejeebus


## Contributing
Feel free to fork and contribute to this code. :)

1. Fork the repo
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

