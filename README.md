# Mise en place env de dev avec Docker

## PHP/Apache

### Conf apache

On récupère la conf depuis le conteneur :
```
docker exec -u=1000:1000 php cat /etc/apache2/sites-available/000-default.conf > ~/perso/asmb-cms/docker/php-apache/000-default.conf
docker exec -u=1000:1000 php cat /etc/apache2/sites-available/default-ssl.conf > ~/perso/asmb-cms/docker/php-apache/default-ssl.conf
```

### Personnaliser le .bachrc

```
docker exec -u=1000:1000 php cat /home/perrine/.bashrc > ~/perso/asmb-cms/docker/php-apache/.bashrc
```

### Mise en place du SSL sur env de dev

Génerer un certificat et une clé en local :
```
openssl req -x509 -out localhost.crt -keyout localhost.key \
  -newkey rsa:2048 -nodes -sha256 \
  -subj '/CN=localhost' -extensions EXT -config <( \
   printf "[dn]\nCN=localhost\n[req]\ndistinguished_name = dn\n[EXT]\nsubjectAltName=DNS:localhost\nkeyUsage=digitalSignature\nextendedKeyUsage=serverAuth")
```

cf. https://letsencrypt.org/docs/certificates-for-localhost/#making-and-trusting-your-own-certificates
