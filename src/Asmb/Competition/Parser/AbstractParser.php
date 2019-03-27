<?php

namespace Bundle\Asmb\Competition\Parser;

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
abstract class AbstractParser
{
    const MAX_PER_PAGE = 10;

    /** @var \DomDocument */
    protected $document;
    /** @var \DomXPath */
    protected $xpath;

    /**
     * AbstractParser constructor.
     */
    public function __construct()
    {
        $this->document = new DomDocument;
        $this->document->preserveWhiteSpace = false;
    }

    /**
     * Parse et retourne les données sous forme d'un tableau d'objet.
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
     * @param int                                               $pageCount
     *
     * @return array
     */
    final public function parse(Pool $pool, $pageCount = 1)
    {
        $parsedData = [];

        if (null !== $pool->getFftId()) {
            for ($page = 0; $page < $pageCount ; $page++) {
                $url = $this->buildUrlToParse($pool->getFftId(), $page);

                try {
                    $this->document->loadHTMLFile($url);
                } catch (ContextErrorException $e) {
                    // que faire ?
                }

                $this->xpath = new DomXPath($this->document);
                $parsedData = array_merge($parsedData, $this->doParse($pool));
            }
        }

        return $parsedData;
    }

    /**
     * Construit l'url finale à parser à partir de l'id FFT d'une poule.
     *
     * @param string $fftId
     * @param        $page
     *
     * @return string
     */
    protected function buildUrlToParse($fftId, $page)
    {
        return str_replace(['$fftId$','$page$'], [$fftId, $page], $this->getUrl());
    }

    /**
     * Url pour la récupération des données
     *
     * @return mixed
     */
    abstract protected function getUrl();

    /**
     * Effectue l'extraction des données lors du parsing.
     *
     * @param Pool $pool
     *
     * @return array
     */
    abstract protected function doParse(Pool $pool);

    /**
     * Extrait le texte à partir de la requête XPATH donnée dans le contexte du noeud donné.
     *
     * @param string      $xpathQuery
     * @param \DOMElement $node
     * @param bool        $doStripTags
     *
     * @return string|string[]
     */
    protected function getTextContentFromXpath($xpathQuery, \DOMElement $node, $doStripTags = true)
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
     *
     * @param string      $xpathQuery
     * @param \DOMElement $node
     *
     * @return Carbon
     */
    protected function getDateContentFromXpath($xpathQuery, \DOMElement $node)
    {
        $textContent = $this->getTextContentFromXpath($xpathQuery, $node);
        // TODO gérer les reports de date ? (en gras et/ou avec un * devant)

        preg_match('#\d\d/\d\d/\d\d#', $textContent, $matches);
        if (! empty($matches)) {
            $dateParsed = $matches[0];

            try {
                $date = Carbon::createFromFormat('d/m/y', $dateParsed);
            } catch (InvalidArgumentException $e) {
                $date = null;
            }
        }


        return $date;
    }
}
