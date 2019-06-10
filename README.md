AutoUFSM
======
#### How To Build

1. Download [composer](https://getcomposer.org/download/) and place it on project root (or install it).

2. Install project dependencies with composer
    
   You must run a command from the same path where is located composer.json (project root). The exact command 
   depends on which you installer composer globally on you system or just downloaded it into the project folder.

   * if you placed it on your project root then run:
      ```bash
      php composer install
      ```
   * otherwise if you installed it globally then run:
      ```bash
      composer install
      ```
      
3. Finally, build the AutoUFSM phar:

   ```
   php -d phar.readonly=0 vendor/bin/phar-builder package composer.json
   ```
   
   After that the standalone executable will be generated at _\<Project Root\>/bin/AutoUFSM.phar_.
   
#### Using it

For a list of available commands run

```bash
php bin/AutoUFSM.phar list
```
