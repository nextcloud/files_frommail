### installation

- need mailparse

```
pecl install mailparse
```

if fail:

```
pecl download mailparse
tar -zxvf mailparse-3.0.2.tgz
cd mailparse-3.0.2/
phpize
edit mailparse.c
```

remove lines 34-37:
>     #if !HAVE_MBSTRING                                                                                                                                                                    
>     #error The mailparse extension requires the mbstring extension!                                                                                                                       
>     #endif                          

```
./configure --with-php-config=/usr/bin/php-config
make
make install

echo "extension=mailparse.so" > /etc/php/7.0/mods-available/mailparse.ini

 cd /etc/php/7.0/apache2/conf.d/
ln -s /etc/php/7.0/mods-available/mailparse.ini ./20-mailparse.ini
apachectl restart
```



### configuration MTA:

new entry in /etc/aliases:

>     files-artificial: "|/usr/bin/php -f /path/to/NextcloudMailCatcher.php"

- execute _newaliases_

- if virtual alias, create a new alias: files@artificial-owl.com -> files@hostname.example.net 

