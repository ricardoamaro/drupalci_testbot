## Docker Drupal testbots on your local box!
### Test your Drupal patches locally with docker.

# Jump to topic:

* Setup locally
  * [Quick Setup](#quick-linux-instructions-for-the-impatient)
  * [Full Setup](#full-instructions)
* Running Tests
  * [run.sh Examples](#6--run-examples)
  * [run.sh Options](#runsh-options)
  * [config.yml and config files](#using-a-configyml-or-config-file)
  * [run.sh Test list](#what-tests-can-i-run)
  * [Travis](#travis)
* [Cleanup](#7---clean-up)

This repo contains a recipe for making a [Docker](http://docker.io) containers for Drupal patch testing, using Linux, Apache, PHP and MariaDB/PostgreSQL/MySQL/Sqlite.

This is part of the core code powering the future version of [Drupal automated-testing](https://drupal.org/automated-testing) infrastructure at http://qa.drupal.org .


#### Why is this awesome?
a) Test patches on your local box or http://qa.drupal.org
b) Test multiple patches and multiple modules at once.
c) Test any Drupal version.
d) Get realtime output.
e) Choose mariadb, postgresql, mysql, sqlite.
f) Choose PHP5.3/5.4/5.5/5.6
g) Test offline.


## Quick Linux Instructions (for the impatient):

### (re)Build all and start containers (only once):
```
git clone {thisrepo}
cd drupalci_testbot
sudo ./scripts/build_all.sh cleanup
```

## Quick Vagrant MAC/Windows instructions:
Docker will not run natively on MAC/Windows,
Install Virtualbox, Vagrant and run:
```
git clone {thisrepo}
cd drupalci_testbot
vagrant up

```

### Run some group tests:
```
sudo DCI_TESTGROUPS="Action,Bootstrap" DCI_DRUPALBRANCH="8.0.x" DCI_PATCH="/path/to/your.patch,." ./run.sh
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
cd drupalci_testbot
```
### 3- Build the database image
```
cd containers/database/mysql
sudo ./build.sh
```
### 4- Start the DB container and check it's running on port 3306
```
cd containers/database/mysql
sudo ./run-server.sh
```

### 5- Build the WEB images
```
cd containers/base/web-base
sudo ./build.sh
cd containers/web/web-[PHP_VERSION]
sudo ./build.sh
```
### 6- RUN EXAMPLES:

**Results will be available at:**
**{USERHOME}/testbotdata/BUILD_{DATE}/results**
**and at the live running terminal**

Run 'search_api' module tests, with one patch against D8 and git sandbox:
```
sudo DCI_TESTGROUPS="--module 'search_api'" \
DCI_DEPENDENCIES_GIT="http://git.drupal.org/sandbox/daeron/2091893.git,master" \
DCI_PATCH="https://drupal.org/files/SOME_DCI_PATCH_THAT_YOU_HAVE.patch,DCI_PATCH_APPLY_DIR" \
DCI_DRUPALBRANCH="8.0.x" \
./run.sh
```

Run Action and Node tests, 2 LOCAL patches, using 4 CPUs, against D8:
```
cd containers/web/

sudo \
DCI_TESTGROUPS="Action,Node" \
DCI_CONCURRENCY="4" \
DCI_DRUPALBRANCH="8.0.x" \
DCI_PATCH="/tmp/1942178-config-schema-user-28.patch,.;/tmp/1942178-config-schema-30.patch,." \
./run.sh
```

Run all tests using 4 CPUs, 1 core patch, 1 tgz module, against D8:
```
cd containers/web/

sudo \
DCI_TESTGROUPS="--all" \
DCI_CONCURRENCY="4" \
DCI_DRUPALBRANCH="8.0.x" \
DCI_DEPENDENCIES_TGZ="http://ftp.drupal.org/files/projects/admin_menu-8.0.x-3.x-dev.tar.gz" \
DCI_PATCH="https://drupal.org/files/issues/1942178-config-schema-user-28.patch,." \
./run.sh
```

Run all tests using 6 CPUs, 2 patches and 2 modules on D7.26:
```
cd containers/web/

sudo \
DCI_TESTGROUPS="--all" \
DCI_CONCURRENCY="6" \
DCI_DRUPALBRANCH="7.26" \
DCI_DEPENDENCIES="flag,payment"  \
DCI_PATCH="https://drupal.org/files/issues/flag_fix_global_flag_uid_2087797_3.patch,sites/all/modules/flag;https://drupal.org/files/issues/payment_2114785_8.patch,sites/all/modules/payment" \
./run.sh
```


And that's it.

### ./run.sh Options
Bellow is the list of Environment Variables and defaults that can be passed to the ./run.sh runner.
These options can also be added to a **$HOME/.drupalci/config** file.

```
# Any valid Drupal branch or tag, like 8.0.x, 7.x or 7.30:
DCI_DrupalBRANCH="8.0.x"

# The identifier used by jenkins to name the Drupal docroot where all is stored:
DCI_IDENTIFIER="build_$(date +%Y_%m_%d_%H%M%S)" # Only [a-z0-9-_.] allowed

# The place where Drupal repos and DrupalDocRoot indentifiers are kept:
DCI_REPODIR="$HOME/testbotdata"

# Request the runner to update the Drupal local repo before local cloning:
DCI_UPDATEREPO="false"  # true to force repos update

# By default we put the Drupal repo and docroots on the same place, but you can have BUILDSDIR elsewhere:
DCI_BUILDSDIR="$DCI_REPODIR"

# Same for the workspace:
DCI_WORKSPACE="$DCI_BUILDSDIR/$DCI_IDENTIFIER/"

# Install modules:
DCI_DEPENDENCIES=""     # module1,module2,module2...

# Git clone sandboxes:
DCI_DEPENDENCIES_GIT="" # gitrepo1,branch;gitrepo2,branch;...

# Download tgz modules:
DCI_DEPENDENCIES_TGZ="" # module1_url.tgz,module1_url.tgz,...

# Download and patch one or several patches:
DCI_PATCH=""            # patch_url,apply_dir;patch_url,apply_dir;...

# PHP version to run tests on 5.3/5.4/5.5:
DCI_PHPVERSION="5.4"

# Database type and version selection, from mysql/mariadb/pgsql/sqlite:
DCI_DBTYPE="mysql"
DCI_DBVER="5.5"

# Username & Password
DCI_DBUSER="Drupaltestbot"
DCI_DBPASS="Drupaltestbotpw"

# Default dbcontainer and link
DCI_DBCONTAINER="drupaltestbot-db-mysql-5.5"
DCI_DBLINK="--link=drupaltestbot-db-mysql-5.5:db"

# Try to use core "none" install tests or "drush"
DCI_INSTALLER="none"

# Executes other funcionality in the container prepending CMD.
DCI_ENTRYPOINT=""

# Debug container shell with DCI_CMD="/bin/bash"
DCI_CMD=""
DCI_VERBOSE="false"     # true will give verbose

# How many cpus to use per run:
DCI_CONCURRENCY="4"

# Testgroups to run, eg. "--all":
DCI_TESTGROUPS="Bootstrap" #TESTS TO RUN eg.--all

# Default runscript
DCI_RUNSCRIPT="php ./scripts/run-tests.sh --php /usr/bin/php --url 'http://localhost' --color --concurrency ${DCI_CONCURRENCY} --xml '/var/workspace/results' ${DCI_TESTGROUPS} "
```

## Using a config.yml or config file

The runner can use one config per run instead of env variables.
- A config or a config.yml file can be placed into
  $HOME/.drupalci/config or
  $HOME/.drupalci/config.yml

  While the config file just has the bash variables per line
  the config.yml follows the yaml format, like this example:

```
DCI_DBTYPE 	: mysql
DCI_DBVER	: 5.5
DCI_PHPVERSION	: 5.6
DCI_TESTGROUPS	: Bootstrap,Action
DCI_DRUPALBRANCH : 8.0.x
DCI_RUNSCRIPT : php ./core/scripts/run-tests.sh --php /usr/bin/php --url 'http://localhost' --color --concurrency 4  --xml '/var/workspace/results'
DCI_PATCH : https://www.drupal.org/files/issues/remove-language_list-2328293-9.patch,.
DCI_CMD		: /bin/bash /start.sh
```
  Only one file type can be use per run.


if [ -f $HOME/.drupalci/config ] && [ -f $HOME/.drupalci/config.yml ];
  then
  echo "Runner can only use one config per run:"
  echo "$HOME/.drupalci/config &"
  echo "$HOME/.drupalci/config.yml are in conflict"
  echo "Please remove one or both and try again!"
  exit 1
fi

# Source $HOME/.drupalci/config environment variables:
if [ -f $HOME/.drupalci/config ];
  then
    echo "Sourcing your default variables from $HOME/.drupalci/config ";
    source $HOME/.drupalci/config;
  elif [ -f $HOME/.drupalci/config.yml ];
  then
    echo "Sourcing your default variables from $HOME/.drupalci/config.yml ";
    eval $(parse_yaml $HOME/.drupalci/config.yml);
fi



### What tests can I run?
```
sudo \
DCI_DRUPALBRANCH="8.0.x" \
DCI_RUNSCRIPT="/usr/bin/php ./core/scripts/run-tests.sh --list" \
./run.sh
```

If you need to remove the old web image just run this sequence:
```
sudo docker images | grep "drupalci/web" | awk '{print $3}' | xargs -n1 -I {} sudo docker rm {}
```

### Travis

Travis support is provided via an upstream project call PrivateTravis.

To perform a Travis build in it's simplest form run the following command:

```
$ drupalci travis > run && sh run
```

The above performs 2 tasks:
* Converts a .travis.yml file to a set of Docker commands.
* Pipes and runs the commands in a single script (to avoid PHP running Docker running PHP).

For more information on this command see the PrivateTravis documentation:

https://github.com/nickschuch/PrivateTravis

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
├── containers
│   ├── base
│   │   ├── testbot_base
│   │   └── web-base
│   │       └── conf
│   │           ├── apache2
│   │           ├── php5
│   │           ├── scripts
│   │           └── supervisor
│   ├── database
│   │   ├── mariadb-10.0
│   │   │   └── conf
│   │   ├── mariadb-5.5
│   │   │   └── conf
│   │   ├── mysql-5.5
│   │   │   └── conf
│   │   ├── pgsql-8.4
│   │   │   └── conf
│   │   └── pgsql-9.1
│   │       └── conf
│   └── web
│       ├── web-5.4
│       └── web-5.5
├── drupal
├── jobs
│   ├── phpunit
│   ├── simpletest
│   └── syntax
├── scripts
│   └── src
└── vendor
```

## PHAR

We have the ability to generate a binary for distrubtion. To build the .phar file
run the following command:

```
bin/box build
```

## Contributing
Feel free to fork and contribute to this code. :)

1. Fork the repo
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request
