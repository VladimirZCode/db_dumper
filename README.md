## Database dumper

"db_dumper" dumps a database based on commands provided through a ENV configuration and uploads the dump to a cloud.
Features of the dumper:
 - All configuration options are provided via ENV configuration. Create a .env file from .env.dist file to provide specific configuration options.
 - The dump is uploaded to Google Drive if the option is provided in the command parameters.
 - It is built as a command line application.

## Installation

Pull or download the following necessary folders and files:

```
dbdump, src, .env.dist, google-service-account-keys.json, application.php, composer.json, composer.lock
```

### Configuration

 - Create your .env file from .env.dist.
 - Fill "DB_DUMP_FOLDER_ID". Copy it from the Google Drive.
 - Example of "DB_DUMP_COMMANDS_LIST" to create and download the database dump via SSH:

 ```
 'ssh user@192.168.16.2 mysqldump -hdb -udb -pdb --databases db --skip-comments | gzip -c > %s; scp user@192.168.16.2:%s .'
 ```

 - Create your google-service-account-keys.json file from google-service-account-keys.json.dist with the credentials.
 - Create a project on https://console.cloud.google.com/. Register there as a developer, get an authorization key for the google-service-account-keys.json file.

Install the dependencies:

```
composer install
```

## How to run the application on a local setup

```
php application.php dbdumper
```

## How to run the application in Docker

Pull or download the repository files.

Install DDEV via the command (https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/):

```
brew install ddev/ddev/ddev
```

To start DDEV:

```
ddev start
```

To stop DDEV:

```
ddev stop
```

To build the application:

```
composer install
```

To run the application in DDEV container:

```
ddev exec php application.php dbdumper
```

Tip: If the following error appears do the following:

```
Error: Ports are not available: exposing port TCP 127.0.0.1:443 -> 0.0.0.0:0
failed to connect to /var/run/com.docker.vmnetd.sock: is vmnetd running
To fix:
/Applications/Docker.app/Contents/MacOS/install remove-vmnetd
sudo /Applications/Docker.app/Contents/MacOS/install vmnetd
```

## How to set up the Google Cloud API and the Google Drive

Helpful instruction: https://www.labnol.org/google-api-service-account-220404

Create a new folder on the Google Drive.
Email of the Google Cloud Service account which I have to share the folder:
service-account@db-dumper.iam.gserviceaccount.com

## Information for developers

To test the application on local environment follow the steps

### Run the database container’s MySQL client as root user:

```
ddev mysql -uroot -proot
```

### Create a table and insert some data:

```
CREATE TABLE task (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    start_date DATETIME,
    due_date DATETIME
);
insert into task values (1, 'Task 1', '2024-01-14 00:00:00', '2024-01-16 23:59:59'), (2, 'Task 2', '2024-01-16 00:00:00', '2024-01-18 23:59:59');
```

To connect as normal user:

```
ddev mysql
```

## Useful commands

### To update the composer autoloader

```
composer dump -o
```

### To debug the application, run the command with the following parameters

```
php -dxdebug.remote_autostart=1 -dxdebug.idekey=DOCKER_XDEBUG application.php dbdumper
```
