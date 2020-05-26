<?php

namespace Bundle\Asmb\Common\Extension;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\PoolMeetingHelper;
use Carbon\Carbon;

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
     * Formate la dates+heure donnée en par exemple :
     * lun. 10 févr. 20:30
     *
     * @param Carbon $datetime
     * @return string
     */
    public function getLocalizedDatetime(Carbon $datetime)
    {
        return $datetime->formatLocalized('%a %d %b %H:%M');
    }

    /**
     * Formate la dates+heure donnée en par exemple :
     * lun. 10 févr.
     *
     * @param Carbon $date
     * @return string
     */
    public function getLocalizedDate(Carbon $date)
    {
        $format = ($date->daysInMonth === 1) ? '%a %der %b' : '%a %d %b';

        return $date->formatLocalized($format);
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFilters()
    {
        return [
            'fileExtension' => 'getFileExtension',
            'fileSize' => 'getFileSize',
            'localizeddate' => 'getLocalizedDate',
            'localizeddatetime' => 'getLocalizedDatetime',
        ];
    }
}
