#!/usr/bin/env bash

## Script de déploiement des fichiers modifiés dans GIT.
## ATTENTION : en cas de nouveaux dossiers, ils doivent être créés en amont !!!!!!!!!!!

# le .env doit contenir les déclarations de FTP_HOST, FTP_USER et FTP_PASS
source .env

DATE=$(date '+%Y-%m-%d')
FILES_LIST_TO_DEPLOY="/tmp/asmb-deploy-${DATE}.log"

git diff --name-only bolt :^bolt/public/theme/peleq/source > ${FILES_LIST_TO_DEPLOY}

# Dossiers local (sans '/' !)
SRC_DIR='bolt'
# Dossier distant sur le FTP
FTP_DIR='v2'

# Génération de la livraison par FTP
FTP_SCRIPT="/tmp/asmb-deploy-${DATE}.txt"

echo "open $FTP_HOST" > $FTP_SCRIPT
echo "user $FTP_USER $FTP_PASS" >> $FTP_SCRIPT
while read SRC_FILE; do
    # on remplace "bolt/" par "/v2/"
    echo "put $SRC_FILE ${SRC_FILE/"$SRC_DIR"/"$FTP_DIR"}" >> $FTP_SCRIPT
done < ${FILES_LIST_TO_DEPLOY}
echo bye >> $FTP_SCRIPT

ftp -n < $FTP_SCRIPT

echo ""
echo "FICHIERS LIVRÉS :"
cat ${FILES_LIST_TO_DEPLOY}
echo ""
exit