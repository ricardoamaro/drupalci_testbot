Docker Drupal testbots 
======================
#on your local box!

This repo contains a recipe for making a [Docker](http://docker.io) container for Drupal, using Linux, Apache and MySQL. 
To build, make sure you have Docker [installed](http://www.docker.io/gettingstarted/).

This will try to go in line with [Drupal automated-testing](https://drupal.org/automated-testing).


## Install docker:
```
curl get.docker.io | sudo sh -x
```

## Clone this repo somewhere, 
```
git clone {thisrepo}
cd modernizing_testbot__dockerfiles
```
# build the database container
```
cd distributed/database/mysql
sudo ./build.sh 
```
##Start the DB container and check its running on port 3306
```
sudo ./run-server.sh 
sudo docker ps
```

# Build the WEB container
```
cd ~/modernizing_testbot__dockerfiles
cd distributed/apachephp
sudo ./build.sh 5.4
```
## To Run the web container for all tests using 4 cpu
```
sudo TESTGROUPS="--all" CONCURRENCY="4" ./run.sh 
```

## Some default environment variables

```
DRUPALVERSION="7.26"
DRUPALBRANCH="7"
CONCURRENCY="4" #How many cpus to use per run
TESTGROUPS="--class NonDefaultBlockAdmin" #TESTS TO RUN
RUNSCRIPT="php ./scripts/run-tests.sh --php /usr/bin/php --url 'http://localhost' --color --concurrency ${CONCURRENCY} --xml '/var/workspace/results' ${TESTGROUPS} "
PHPVERSION="5.4"
PATCH=""
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
│           └── startup.sh
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

## Contributing
Feel free to fork and contribute to this code. :)

1. Fork the repo
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

