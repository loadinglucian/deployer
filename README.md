# DeployerPHP

```
▒ ▶ DeployerPHP ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
▒ The server and site deployment tool for PHP
```

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## Why does this exist?

```
I built DeployerPHP because I wanted my server, deployment, and build configs
to live in the repository, next to the code, instead of in a dashboard. That
way, everybody on your team can see how the application is deployed, they can
open a PR and update how it's built, etc.

I wanted a tool that would let me install as many servers and deploy as many
sites as I wanted, wherever I wanted. I didn't want to be limited by arbitrary
subscription limits or tied to a single hosting provider.

I wanted a tool with composable commands so I could build automation pipelines
to spin up and install servers and sites or run any workflow on demand.

Finally, I wanted this tool to be completely written in PHP so it fits in with
the rest of my code.

I hope you find it useful!

-Lucian
```

## Full disclosure

DeployerPHP will never limit you on what you can do: you'll always be able to manage all your servers and sites however you want without restrictions or limitations. DeployerPHP doesn't belong to any corporation and is distributed under the MIT License, so it's completely free for you to use or modify however you want, for personal or commercial projects.

Now, DeployerPHP does have a set of "pro" features that revolve around small conveniences and third-party API integrations with popular hosting providers. Those are completely free to use for now as well, but at some point, I might add a nag message or maybe a WinRAR-style license type of thing for a small token subscription or something like that.

Hey, rent isn't free, am I right?

Fuggedaboutit!

## Documentation

- [Getting Started](docs/getting-started.md)
    - [Installation](docs/getting-started.md#installation)
    - [Requirements](docs/getting-started.md#requirements)
    - [Your First Deployment](docs/getting-started.md#your-first-deployment)
- [Server Management](docs/servers.md)
    - [Adding a Server](docs/servers.md#adding-a-server)
    - [Installing a Server](docs/servers.md#installing-a-server)
    - [Server Information](docs/servers.md#server-information)
    - [Firewall Configuration](docs/servers.md#firewall-configuration)
    - [Running Commands](docs/servers.md#running-commands)
    - [SSH Access](docs/servers.md#ssh-access)
    - [Viewing Logs](docs/servers.md#viewing-logs)
    - [Deleting a Server](docs/servers.md#deleting-a-server)
- [Services](docs/services.md)
    - [MySQL](docs/services.md#mysql)
    - [MariaDB](docs/services.md#mariadb)
    - [PostgreSQL](docs/services.md#postgresql)
    - [Redis](docs/services.md#redis)
    - [Memcached](docs/services.md#memcached)
    - [Valkey](docs/services.md#valkey)
    - [Nginx](docs/services.md#nginx)
    - [PHP-FPM](docs/services.md#php-fpm)
- [Site Management](docs/sites.md)
    - [Creating a Site](docs/sites.md#creating-a-site)
    - [Deploying a Site](docs/sites.md#deploying-a-site)
    - [Enabling HTTPS](docs/sites.md#enabling-https)
    - [Shared Files](docs/sites.md#shared-files)
    - [Viewing Logs](docs/sites.md#viewing-logs)
    - [SSH Access](docs/sites.md#ssh-access)
    - [Rollbacks](docs/sites.md#rollbacks)
    - [Deleting a Site](docs/sites.md#deleting-a-site)
    - [Cron Jobs](docs/sites.md#cron-jobs)
    - [Supervisor Processes](docs/sites.md#supervisor-processes)
    - [Scaffolding](docs/sites.md#scaffolding)
- [Pro](docs/pro.md)
    - [AWS EC2](docs/pro.md#aws-ec2)
    - [DigitalOcean](docs/pro.md#digitalocean)
