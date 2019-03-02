<?php

namespace Bundle\Asmb\Competition\Controller\Backend;

use Bolt\Controller\Backend\BackendBase;
use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship\Team;
use Bundle\Asmb\Competition\Form\FormType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for Team routes.
 *
 * @copyright 2019
 */
class TeamController extends BackendBase
{
    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/new/{categoryName}', 'add')
            ->bind('teamadd');

        $c->match('/edit/{id}', 'edit')
            ->assert('id', '\d*')
            ->bind('teamedit');

        $c->post('/delete/{id}', 'delete')
            ->assert('id', '\d*')
            ->bind('teamdelete');

        return $c;
    }

    /**
     * @param Request $request
     * @param string  $cat
     *
     * @return Response
     */
    public function add(Request $request, $categoryName = '')
    {
        $team = new Team();
        $team->setCategoryName($categoryName);

        return $this->renderEditForm($request, $team);
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function edit(Request $request, $id = '')
    {
        $team = $this->getRepository('championship_team')->find($id);

        if (!$team) {
            $this->flashes()->error(Trans::__('general.phrase.wrong-parameter-cannot-edit'));
            $this->redirectToRoute('championship');
        }

        return $this->renderEditForm($request, $team);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param null                                      $id
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Request $request, $id = null)
    {
        $team = $this->getRepository('championship_team')->find($id);

        if (!$team) {
            $this->flashes()->error(Trans::__('general.phrase.wrong-parameter-cannot-delete'));
        } else {
            $deleted = $this->getRepository('championship_team')->delete($team);

            if ($deleted) {
                $this->flashes()->success(Trans::__('page.edit-team.message.deleted', ['%name%' => $team->getName()]));
            } else {
                $this->flashes()->success(Trans::__('page.edit-team.message.deleting-team',
                                                    ['%name%' => $team->getName()]));
            }
        }

        return $this->redirectToRoute('championship');
    }

    /**
     * Render team edit form.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param Team                                      $team
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function renderEditForm(Request $request, Team $team)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $formOptions = [
            'category_names' => $this->getRepository('championship_category')->findAllAsChoices(),
        ];

        // Generate the form
        $form = $this->createFormBuilder(FormType\TeamEditType::class, $team, $formOptions)
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Team $data */
            $team = $form->getData();
            try {
                $this->getRepository('championship_team')->save($team);

                $this->flashes()->success(
                    Trans::__('page.edit-team.message.saved', ['%name%' => $team->getName()])
                );

                /** @var \Symfony\Component\Form\SubmitButton $buttonSave */
                $buttonSave = $form->get('save');
                if ($buttonSave->isClicked()) {
                    return $this->redirectToRoute('championship');
                }

                return $this->redirectToRoute('teamadd', ['categoryName' => $team->getCategoryName()]);
            } catch (UniqueConstraintViolationException $e) {
                $this->flashes()->error(
                    Trans::__('page.edit-team.message.duplicate-error', ['%name%' => $team->getName()])
                );
            } catch (\Exception $e) {
                $this->flashes()->error(
                    Trans::__('page.edit-team.message.saving-team', ['%name%' => $team->getName()])
                );
            }
        }

        $context = [
            'form' => $form->createView(),
        ];

        return $this->render(
            '@AsmbCompetition/championship/team/edit.twig',
            $context,
            [
                'team' => $team,
            ]
        );
    }
}
