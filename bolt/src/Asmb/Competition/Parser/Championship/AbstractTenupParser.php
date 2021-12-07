<?php

namespace Bundle\Asmb\Competition\Parser\Championship;

use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Filesystem\Manager;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Classe abstraite pour les parseurs de données de la FFT sur Tenup
 *
 * @property string url
 * @copyright 2021
 */
abstract class AbstractTenupParser
{
    /**
     * Délai en MINUTE au bout duquel on considère le JSON comme obsolète.
     */
    const MAX_JSON_LIFETIME = 60;

    /** @var array */
    protected $config;
    /** @var Manager */
    protected $fileSystemManager;

    public function __construct(array $config, Manager $fileSystemManager)
    {
        $this->config = $config;
        $this->fileSystemManager = $fileSystemManager;
    }

    final public function parse(Championship $championship, Pool $pool): ?array
    {
        $parsedData = null;

        // On tente tout d'abord de récupérer le JSON stocké en local contenant la réponse au parsing demandé
        $jsonContent = $this->getJsonContentFromLocal($championship, $pool->getFftId());
        if (null === $jsonContent) {
            // le JSON n'existe pas ou sa date de création est devenue obsolète : on réinterroge TenUp et on sauvegarde
            // la réponse en local
            $fields = [
                'fiche_championnat' => $championship->getFftId(),
                'division' => $pool->getDivisionFftId(),
                'poule' => $pool->getFftId(),
                'formSubmit' => 'true'
            ];
            $jsonContent = $this->callPost($fields);

            if (null !== $jsonContent) {
                $this->putJsonContentToLocal($championship, $pool->getFftId(), $jsonContent);
            }
        }

        if (null !== $jsonContent) {
            $jsonData = json_decode($jsonContent, true);
            $parsedData = $this->doParse($championship, $pool, $jsonData);
        }

        return $parsedData;
    }

    abstract protected function doParse(Championship $championship, Pool $pool, array $jsonData): ?array;

    protected function callPost(array $fields = []): ?string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->config['url_ajax'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_USERAGENT => 'BoltCms/Perrine',
            CURLOPT_POSTFIELDS => $fields,
        ));

        $response = curl_exec($curl);
        $infos = curl_getinfo($curl);
        curl_close($curl);

        if (Response::HTTP_OK === $infos['http_code']) {
            return $response;
        } // TODO gérer erreurs ?

        return null;
    }

    protected function getJsonContentFromLocal(Championship $championship, $identifier): ?string
    {
        $fileName = $championship->getYear() . '/' . $championship->getFftId() . '/' . $identifier . '.json';

        try {
            /** @var JsonFile $jsonFile */
            $jsonFile = $this->fileSystemManager->get('cache://tenup/' . $fileName);

            $jsonCreationDate = Carbon::createFromTimestamp($jsonFile->getTimestamp());
            if (Carbon::now()->diffInMinutes($jsonCreationDate) > self::MAX_JSON_LIFETIME) {
                // JSON considéré comme trop ancien : on le supprime
                $jsonFile->delete();
                return null;
            }

            return $jsonFile->read();
        } catch (FileNotFoundException $e) {
            return null;
        }
    }

    protected function putJsonContentToLocal(Championship $championship, string $identifier, string $content)
    {
        $fileName = $championship->getYear() . '/' . $championship->getFftId() . '/' . $identifier . '.json';
        $this->fileSystemManager->put('cache://tenup/' . $fileName, $content);
    }
}
