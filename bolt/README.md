# Bolt Installer for Composer

To start the install just run the following command replacing the project with
the name you want to use.

```
composer create-project bolt/composer-install:^3.5 <MYPROJECT> --prefer-dist
```


After the packages have downloaded, you can choose whether you would like a
separate public directory and if so choose a name.

# Personnalisation des CSS et JS [ASMB]

## Utilisation de `gulp` pour compiler les CSS et JS
```
# À faire au moins une fois :
cd ~/html/public/theme/peleq/source; yarn install

cd ~/html/public/theme/peleq/source; gulp [build|watch|--help]
``` 


cf. https://docs.bolt.cm/3.6/internals/javascript-css-build#quickstart

