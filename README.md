# Honeybee - Content Management Framework

This repository holds the ```honeybee/cmf``` library and ```honeybee/agavi-app```. Honeybee is a library for implementing complex content-management scenarios. The ```cmf``` provides a ddd-ready model abstraction and packages for building app-bindings, infrastructure and ui.  The ```agavi-app``` provides a web- and command-line-api that allows clients to access the model provided by the ```cmf ```.

At some point in the future the cmf and the app will be put into separate repositories.
Until then the ```cmf``` is located at: ```app/lib/Honeybee```, whereas the rest of this repository can be considered as the ```agavi-app```.

### Development setup

When successfully setup the "Honeybee-Showcase" CMS should be reachable at: https://hb-showcase.local/

See the ["Controlling system services"](#controlling-system-services) section for further information on available endpoints.

#### Checking the prerequisites

* VirtualBox: https://www.virtualbox.org/wiki/Downloads
* VirtualBox Extension Pack: https://www.virtualbox.org/wiki/Download_Old_Builds_4_2
    * either add via Menu: ```VirtualBox``` -> ```File``` -> ```Preferences``` -> ```Extensions```
    * or doubleclick the extension pack file after downloading
* Vagrant: http://downloads.vagrantup.com/
* Git: https://git-scm.com

#### Setting up ssh and git

* Add GIT_* environment-vars in ~/.bashrc:
```shell
export GIT_AUTHOR_NAME="User Name"
export GIT_AUTHOR_EMAIL="your@email.de"
export GIT_COMMITTER_NAME="$GIT_AUTHOR_NAME"
export GIT_COMMITTER_EMAIL="$GIT_AUTHOR_EMAIL"
```

* Enable ssh-agent and env-var forwarding in ~/.ssh/config:
```shell
Host *
ForwardAgent yes
SendEnv LANG LC_* GIT_*
```

* Make your key known to the ssh-agent, run:
```shell
ssh-add yourkey # for example: ssh-add ~/.ssh/id_rsa
# on mac-osx use:
ssh-add -K yourkey
```

#### Initially setting up the project

* Initially create the vagrant box:
```shell
git clone git@github.com:berlinonline/hb-showcase.git
git submodule update --init --recursive
cd hb-showcase/dev/box
vagrant up # this will take a while, time to grab a coffee
```

* Checkout and setup app within the box:
```shell
vagrant ssh
cd /srv/www/hb-showcase.local/
git clone https://github.com/berlinonline/honeybee .
git checkout v001-dev
make install
```

In the end you'll be prompted for some infos, such as paths to executables and the project's base-url.
The default values work for development, so answer the prompts by pressing ```<ENTER>``` until finished.
The last prompt will show a numbered list of migration-targets and ask you to choose one. Enter the number of the migration target, that is labeled ```all```.

#### Creating the first system-account user/admin

The first user within the system must be created via command line using:
```shell
make user
```

This will give an output similar to:
```
Please set a password for the created account at: https://hb-showcase.local/foh/system_account/user/password?token=c469090bf62c4d21444cd0a83171b1429a11ad9b
Via CLI use the following: bin/cli foh.system_account.user.password '-token' 'c469090bf62c4d21444cd0a83171b1429a11ad9b'
```

Either copy the displayed url and open it in a browser or run the displayed cli command. Then follow through the instructions in order to set the password for the user, that you just created.

#### Mounting the source

* MAC:
    * In the Finder's menubar select: ```Go``` -> ```Connect to Server```
    * then enter the following address: ```nfs://hb-showcase.local/srv/www/```
* Ubuntu:
```shell
mount hb-showcase.local:/srv/www/ /home/${USER}/projects/hb-showcase
```

#### Controlling system services

The following services are running on the cms devbox and are controlled via systemd:

* couchdb
    * http-endpoint: http://hb-showcase.local:5984
    * web-client: http://hb-showcase.local:5984/_utils
* elasticsearch
    * http-endpoint: http://hb-showcase.local:9200
    * web-client: http://hb-showcase.local:9200/_plugin/head
* converjon
    * http-endpoint: https://hb-showcase.local/converjon
    * web-status: https://hb-showcase.local/converjon/status
* nginx
    * cms http-endpoint: https://hb-showcase.local/
* php-fpm

In order to start/stop services or get the status use the corresponding sudo command within the devbox, e.g.:
```shell
sudo service couchdb status|start|stop|restart
```

#### Turning the devbox on/off

Whenever possible stop the box with:
```shell
vagrant suspend
```
and wake it up again using:
```shell
vagrant resume
```
This will send the box asleep, instead of completely shutting it off and thus runs faster.
The box's network interfaces are not reconfigured using suspend/resume though.
For this the virtual machine needs to be completely rebooted, which can be done by calling:
```shell
vagrant reload # is the same as: vagrant halt && vagrant up
```

In order to apply new infrastructure changes to the box run:
```shell
vagrant provision
```
