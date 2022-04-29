<?php

namespace Bundle\Asmb\Common\Extension;

use Bolt\Application;
use Bolt\Exception\InvalidRepositoryException;
use Bolt\Filesystem\Handler\File;
use Bolt\Filesystem\Manager;
use Bolt\Legacy\Content;
use Bolt\Storage\EntityManagerInterface;
use Bundle\Asmb\Common\Form\FormType\ContactType;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Entity\Championship\PoolRanking;
use Bundle\Asmb\Competition\Parser\Tournament\AbstractParser;
use Bundle\Asmb\Competition\Parser\Tournament\DbParser;
use Bundle\Asmb\Competition\Parser\Tournament\JaTennisJsonParser;
use Bundle\Asmb\Competition\Parser\Tournament\JaTennisJsParser;
use Bundle\Asmb\Competition\Repository\Championship\CategoryRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolRankingRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolRepository;
use Bundle\Asmb\Competition\Repository\ChampionshipRepository;
use Carbon\Carbon;
use Cocur\Slugify\Slugify;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Déclaration de fonctions Twig.
 */
trait TwigFunctionsTrait
{
    public function getContactForm()
    {
        /** @var Application $app */
        $app = $this->getContainer();

        /** @var FormBuilderInterface $builder */
        $builder = $app['form.factory']->createBuilder(ContactType::class);
        $form = $builder->getForm();

        return $form->createView();
    }

    protected function registerTwigFunctions()
    {
        return [
            'contactForm' => 'getContactForm',
        ];
    }
}
