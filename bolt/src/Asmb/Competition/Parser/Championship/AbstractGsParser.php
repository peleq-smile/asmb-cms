<?php

namespace Bundle\Asmb\Competition\Parser\Championship;

use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use Symfony\Component\Debug\Exception\ContextErrorException;

/**
 * Classe abstraite pour les parseurs de données de la FFT.
 *
 * @property string url
 * @copyright 2019
 */
abstract class AbstractGsParser
{
    const MAX_PER_PAGE = 10;

    /** @var array */
    protected $config;
    /** @var \DomDocument */
    protected $document;
    /** @var \DomXPath */
    protected $xpath;
    /** @var int */
    protected $page = null;

    /**
     * AbstractParser constructor.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->document = new DomDocument;
        $this->document->preserveWhiteSpace = false;
    }

    public function setPage(?int $page)
    {
        $this->page = $page;
    }

    /**
     * Parse et retourne les données sous forme d'un tableau d'objet.
     * @noinspection PhpUnusedParameterInspection
     */
    final public function parse(Championship $championship, Pool $pool): array
    {
        $allPagesParsedData = [];

        if (null !== $pool->getFftId()) {
            while (true) {
                $url = $this->buildUrlToParse($pool->getFftId(), $this->page);

                try {
                    $this->document->loadHTMLFile($url);
                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (ContextErrorException $e) {
                    // There is some error but we don't care, it works.
                }

                $this->xpath = new DomXPath($this->document);

                $parsedData = $this->doParse($pool);
                $allPagesParsedData = array_merge($allPagesParsedData, $parsedData);

                if (null === $this->page || empty($parsedData)) {
                    break;
                }
                $this->page++;
            }
        }

        return $allPagesParsedData;
    }

    /**
     * Construit l'url finale à parser à partir de l'id FFT d'une poule.
     */
    protected function buildUrlToParse(string $fftId, $page): string
    {
        if (null !== $page) {
            $url = $this->config['url_pool_meetings'];
            return str_replace(['{$pool}', '{$page}'], [$fftId, $page], $url);
        } else {
            $url = $this->config['url_pool_ranking'];
            return str_replace('{$pool}', $fftId, $url);
        }
    }

    /**
     * Effectue l'extraction des données lors du parsing.
     *
     * @param Pool $pool
     *
     * @return array
     */
    abstract protected function doParse(Pool $pool): array;

    /**
     * Extrait le texte à partir de la requête XPATH donnée dans le contexte du noeud donné.
     */
    protected function getTextContentFromXpath(string $xpathQuery, \DOMElement $node, bool $doStripTags = true): string
    {
        $subNode = $this->xpath->query($xpathQuery, $node)->item(0);

        // Suppression des tabulations, les sauts de lignes sont remplacés par des espaces.
        $textContent = str_replace(["\t", PHP_EOL], ['', ' '], $subNode->textContent);

        // Suppression éventuelle des balises
        if ($doStripTags) {
            $textContent = strip_tags($textContent);
        }

        // Les multiples espaces à suivre sont remplacés par un et un seul espace
        $textContent = preg_replace("#\s\s+#", ' ', $textContent);
        // Suppression des espaces éventuels de début et de fin de la chaîne
        $textContent = trim($textContent);

        return $textContent;
    }

    /**
     * Extrait la date à partir de la requête XPATH donnée dans le contexte du noeud donné.
     * Format attendu : d/m/y.
     */
    protected function getDateContentFromXpath(string $xpathQuery, \DOMElement $node): ?Carbon
    {
        $textContent = $this->getTextContentFromXpath($xpathQuery, $node);
        // TODO gérer les reports de date ? (en gras et/ou avec un * devant)

        preg_match('#\d\d/\d\d/\d\d#', $textContent, $matches);
        if (!empty($matches)) {
            $dateParsed = $matches[0];

            try {
                $date = Carbon::createFromFormat('d/m/y', $dateParsed);
            } catch (InvalidArgumentException $e) {
                $date = null;
            }
        } else {
            $date = null;
        }

        return $date;
    }
}
