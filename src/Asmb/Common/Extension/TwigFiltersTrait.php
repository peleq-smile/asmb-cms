<?php

namespace Bundle\Asmb\Common\Extension;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\PoolMeetingHelper;

/**
 * Déclaration de filtres Twig.
 */
trait TwigFiltersTrait
{
    /**
     * Retourne l'extension du fichier dont le chemin est passé en paramètre.
     *
     * @param string $filename
     *
     * @return string
     */
    public function getFileExtension($filename)
    {
        /** @var \Bolt\Filesystem\Matcher $fileMatcher */
        $fileMatcher = $this->container['filesystem.matcher'];
        /** @var \Bolt\Filesystem\Handler\File $file */
        $file = $fileMatcher->getFile($filename);

        return $file->getExtension();
    }

    /**
     * Retourne la taille du fichier (formatté) dont le chemin est passé en paramètre.
     *
     * @param string $filename
     *
     * @return string
     */
    public function getFileSize($filename)
    {
        /** @var \Bolt\Filesystem\Matcher $fileMatcher */
        $fileMatcher = $this->container['filesystem.matcher'];
        /** @var \Bolt\Filesystem\Handler\File $file */
        $file = $fileMatcher->getFile($filename);

        return $file->getSizeFormatted(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFilters()
    {
        return [
            'fileExtension' => 'getFileExtension',
            'fileSize'      => 'getFileSize',
        ];
    }
}
