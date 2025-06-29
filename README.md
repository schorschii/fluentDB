# fluentDB
**Self Hosted / On Premise General Database and CMDB Application**

It focuses on easy usability (good GUI/UX), simplicity (assessable code with minimal external dependencies) and performance (many users can manage a big amount of objects with minimal server resources).

It features an idoit-like JSON-RPC API.

### Roadmap
This project is in a very early stage of development. Planned features are:
- object type & category management via web UI  
  (currently, object types and catagories must be created manually in the database)
- permission management
- CSV import
- IP address management

### Screenshots
// TODO

## System Requirements
### Server
- Software
  - any Linux Distribution
  - MySQL/MariaDB Database Server
  - Apache2 Web Server
  - PHP 7.0 or newer
- Hardware Recommendations for ~1000 Objects & 10 Active Users
  - 2 CPU cores
  - 4GB RAM
  - 20GB HDD

### (Admin) Client
- Chromium-based Web Browser (Chrome/Chromium v80 or newer, Opera etc.)
- Firefox (v80 or newer)

## Translations & Contributions Welcome!
Please open a pull request for any improvements you like!

For translators: the language files are in `lib/Language/<langcode>.php`. There you can insert new files with your translations or correct existing ones. Thank you very much!

## Information, Manual, Documentation
**Please read the documentation in the [`/docs`](docs/README.md) folder.**

Quick Links:
- [Overview](docs/README.md)
- [Installation Guide](docs/Server-Installation.md)

## License
This project is open source, which means you have the freedom to view the source code, report issues and submit improvements on GitHub, which are very welcome. However, a license is required if you want to manage more than 20 computers with this system. Please buy the appropriate licenses [here](https://georg-sieber.de/?page=oco).

## Support & Specific Adjustments
You need support or specific adjustments for your environment? You can hire me to extend fluentDB to your needs or to write custom reports etc. Please [contact me](https://georg-sieber.de/?page=impressum) if you are interested.
