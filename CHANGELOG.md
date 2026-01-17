# Changelog

## [v1.0.0-alpha4] - 2026-01-17

### Added

- DNS management commands for AWS Route53, DigitalOcean, and Cloudflare (#224)
- Configurable web root for site creation (#235)
- Centralized DTO builder classes for type-safe object construction (#238)
- Ubuntu LTS version validation for server provisioning (#233)
- Workflow scaffolding for preview deployments (#244)
- Production deployment workflow scaffolding (#247)
- Timezone configuration for server installation (#245)
- Server-info detection in playbook execution (#241)
- Cross-platform AI rules/skills format for scaffold:ai (#246)

### Changed

- Consolidate 12 logs commands into unified `server:logs` command (#213)
- Reorganize Pro namespace and add command aliases (#216)
- Reorganize SSH and run command namespaces (#234)
- Relax laravel/prompts constraint to ^0.3.0 (#256)
- Reorder installation options in getting-started guide (#257)
- Refactor GitHub Actions workflows and reorganize PHP setup action (#255)

### Fixed

- Normalize API endpoint paths for Cloudflare/Guzzle compatibility (#229)
- Use sudo for file operations in deployer-owned paths (#230)
- Initialize framework variable before conditionals in scaffold (#231)
- Make PHP CLI and FPM extensions required during server setup (#232)

### Removed

- Claude-review workflow (#237)

## [v1.0.0-alpha3] - 2026-01-06

### Added

- `scaffold:ai` command for generating AI agent rules (#203)
- Documentation for scaffold:ai command (#210)

### Changed

- Use GitHub Review API for inline code comments in CI (#206)
- Skip claude-review workflow for Dependabot PRs (#209)

### Removed

- PHPMD from project dependencies (#207, #208)

## [v1.0.0-alpha2] - 2026-01-05

### Fixed

- Correct nginx escaping in playbooks (#202)
- Filter DigitalOcean droplet sizes by region (#202)

## [v1.0.0-alpha] - 2026-01-04

This is the first public alpha release of DeployerPHP, a server and site deployment tool for PHP applications.

### Added

#### Core Infrastructure

- Dependency injection container system with reflection-based auto-wiring (#14)
- Inventory CRUD operations for servers and sites (#16)
- Custom environment inventory paths (#19)
- SSH/SFTP service for remote operations (#21)
- Filesystem service integration (#22)
- Console prompts wrappers using laravel/prompts (#26)

#### Server Management

- Server CRUD commands (`server:add`, `server:delete`) (#27)
- Server info command with hardware metrics (#62, #77, #79)
- Server SSH command for interactive access (#124)
- Server firewall command with UFW integration (#137, #166)
- Server logs command for unified log viewing (#148)
- Server provisioning with negatable options (#74)
- Cascade deletion of associated sites when deleting servers (#89)

#### Site Management

- Site CRUD commands (`site:create`, `site:delete`) (#46, #90)
- Site SSH command for interactive access (#124)
- Site HTTPS command with Certbot integration (#97)
- Site shared files commands (`site:shared:push`, `site:shared:pull`) (#92, #93)
- Atomic deployments with releases directory structure (#98)
- Site rollback command for deployment education (#181)

#### Service Commands

- PHP-FPM service control (`php:start`, `php:stop`, `php:restart`) (#162)
- Nginx service control (`nginx:start`, `nginx:stop`, `nginx:restart`) (#180)
- MySQL installation and service control (#153)
- MariaDB installation and service control
- PostgreSQL installation and service control
- Redis installation and service control (#163)
- Valkey installation and service control (#163)
- Memcached installation and service control (#163)

#### Process Management

- Supervisor management commands (#146)
- Cron job management commands (`cron:create`, `cron:delete`, `cron:sync`) (#144)
- Runner script generation for cron jobs

#### Cloud Providers (Pro)

- DigitalOcean API service integration (#51)
- DigitalOcean SSH key CRUD commands (#53)
- DigitalOcean droplet provisioning (#59)
- AWS EC2 provisioning and key management (#164)
- Family-based EC2 instance type selection with Elastic IP (#176)

#### Scaffolding

- Deployment hooks scaffolding (#95)

#### Documentation

- Laravel-style documentation (#182)
- Getting-started guide (#187, #188)
- Contributing guide with code of conduct (#197)

### Changed

- Migrate from Caddy to Nginx with Certbot for web server (#180)
- Rename namespace from Deployer to DeployerPHP (#178, #179)
- Reorganize Pro commands into provider namespaces (AWS, DigitalOcean) (#172)
- Rename service classes to follow PascalCase naming convention (#174)
- Consolidate shared path functionality into SitesTrait (#167)
- Rename `*:status` commands to `*:logs` for consistency (#159)
- Rename "provision" terminology to "add" for sites (#106)
- Move code structure from src/ to app/ directory (#5, #6)

### Fixed

- Escape service names in journalctl commands (#71)
- Boolean flag detection in getOptionOrPrompt (#37)
- Command replay for single parameter commands (#143)
- Credential file operations exception handling (#158)
- Database credentials path validation (#161)
- Ensure deployer user owns site directories during clone (#147)

[Unreleased]: https://github.com/bigpixelrocket/deployer-php/compare/v1.0.0-alpha4...HEAD
[v1.0.0-alpha4]: https://github.com/bigpixelrocket/deployer-php/compare/v1.0.0-alpha3...v1.0.0-alpha4
[v1.0.0-alpha3]: https://github.com/bigpixelrocket/deployer-php/compare/v1.0.0-alpha2...v1.0.0-alpha3
[v1.0.0-alpha2]: https://github.com/bigpixelrocket/deployer-php/compare/v1.0.0-alpha...v1.0.0-alpha2
[v1.0.0-alpha]: https://github.com/bigpixelrocket/deployer-php/releases/tag/v1.0.0-alpha
