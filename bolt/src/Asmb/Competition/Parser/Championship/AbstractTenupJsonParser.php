<?php

namespace Bundle\Asmb\Competition\Parser\Championship;

use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Filesystem\Manager;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Carbon\Carbon;
use League\Flysystem\Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Classe abstraite pour les parseurs de données de la FFT sur Tenup
 *
 * @property string url
 * @copyright 2021
 */
abstract class AbstractTenupJsonParser
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

    final public function parse(Championship $championship, Pool $pool, ?PoolMeeting $poolMeeting = null): ?array
    {
        $parsedData = null;
        $jsonData = null;

        // On tente tout d'abord de récupérer le JSON stocké en local contenant la réponse au parsing demandé
        $jsonContent = $this->getJsonContentFromLocal($championship, $pool, $poolMeeting);
        if (null === $jsonContent) {
            // le JSON n'existe pas ou sa date de création est devenue obsolète : on réinterroge TenUp et on sauvegarde
            // la réponse en local

            // DEUX CAS : appel en POST sur une url AJAX pour récupérer un contenu JSON
            // OU appel en GET sur une url de feuille de match, pour parser le contenu HTML et extraire du JSON
            if (null === $poolMeeting) {
                $fields = [
                    'fiche_championnat' => $pool->getChampionshipFftId(),
                    'division' => $pool->getDivisionFftId(),
                    'poule' => $pool->getFftId(),
                    'formSubmit' => 'true'
                ];
                $jsonContent = $this->callPost($fields);
            } elseif (null !== $poolMeeting->getMatchesSheetFftId()) {
                $url = $this->config['url_match_sheet'];
                $url = str_replace(
                    ['{$championship}', '{$matchSheet}'],
                    [$pool->getChampionshipFftId(), $poolMeeting->getMatchesSheetFftId()],
                    $url
                );
                $htmlContent = $this->callGet($url);

                $contentFrom = '"fft_feuille_de_match":';
                $contentTo = ',"vuejs_context"';
                $idxFrom = strpos($htmlContent, $contentFrom);
                $idxTo = strpos($htmlContent, $contentTo);

                $jsonContent = substr(
                    $htmlContent,
                    $idxFrom + strlen($contentFrom),
                    $idxTo - ($idxFrom + strlen($contentFrom))
                );
            }

            if (null !== $jsonContent) {
                $jsonData = json_decode($jsonContent, true);
                if ($jsonData) { // on sauvegarde le json dans un fichier que s'il est correctemet formé
                    $this->putJsonContentToLocal($championship, $pool, $poolMeeting, $jsonContent);
                }
            }
        } else {
            $jsonData = json_decode($jsonContent, true);
        }

        if ($jsonData) {
            $parsedData = $this->doParse($championship, $pool, $poolMeeting, $jsonData);
        }

        return $parsedData;
    }

    abstract protected function doParse(Championship $championship, Pool $pool, ?PoolMeeting $poolMeeting, array $jsonData): ?array;

    protected function callPost(array $fields = []): ?string
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->config['url_ajax'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_USERAGENT => 'BoltCMS/Perrine',
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $headers = [
            'Content-Type' => 'application/json',
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        $infos = curl_getinfo($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if (Response::HTTP_OK === $infos['http_code']) {
            return $response;
        }

        throw new Exception('Impossible de récupérer les résultats depuis Ten\'Up avec les données '
            . json_encode($fields) . ' : ' . $error
        );
    }

    protected function callGet(string $url): ?string
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_USERAGENT => 'BoltCMS/Perrine',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($curl);
        $infos = curl_getinfo($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if (Response::HTTP_OK === $infos['http_code']) {
            return $response;
        }

        throw new Exception('Impossible de récupérer les données depuis ' . $url . ' : ' . $error);
    }

    protected function getJsonContentFromLocal(Championship $championship, Pool $pool, ?PoolMeeting $poolMeeting): ?string
    {
        $fileName = $this->getJsonLocalFilename($championship, $pool, $poolMeeting);

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

    protected function putJsonContentToLocal(Championship $championship, Pool $pool, ?PoolMeeting $poolMeeting, string $content)
    {
        $fileName = $this->getJsonLocalFilename($championship, $pool, $poolMeeting);

        if (null !== $fileName) {
            $this->fileSystemManager->put('cache://tenup/' . $fileName, $content);
        }
    }

    protected function getJsonLocalFilename(Championship $championship, Pool $pool, ?PoolMeeting $poolMeeting): ?string
    {
        $fileName = null;

        if (null === $poolMeeting) {
            $fileName = $championship->getYear() . '/' . $pool->getChampionshipFftId() . '_' . $pool->getFftId() . '.json';
        } elseif (null !== $poolMeeting->getMatchesSheetFftId()) {
            $fileName = $championship->getYear() . '/' . $pool->getChampionshipFftId() . '_' . $pool->getFftId() . '_' . $poolMeeting->getMatchesSheetFftId() . '.json';
        }

        return $fileName;
    }
}
