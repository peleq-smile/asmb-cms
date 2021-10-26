<?php
namespace Bundle\Asmb\Competition\Form\FormType;

use Bundle\Asmb\Competition\Entity\Tournament\Box;

trait FormWithWinnerChoicesTrait
{
    /**
     * @param Box $box
     *
     * @return array
     */
    protected function buildWinnerChoices(Box $box)
    {
        $winnerChoices = [];
        if ($box->getBoxTop()->getPlayerName() && $box->getBoxBtm()->getPlayerName()) {
            $playerTop = $box->getBoxTop()->getPlayerName();
            if (! empty($box->getBoxTop()->getPlayerClub())) {
                $playerTop .= ' ('. $box->getBoxTop()->getPlayerClub() . ')';
            }
            $winnerChoices['top'] = $playerTop;

            $playerBtm = $box->getBoxBtm()->getPlayerName();
            if (! empty($box->getBoxBtm()->getPlayerClub())) {
                $playerBtm .= ' ('. $box->getBoxBtm()->getPlayerClub() . ')';
            }
            $winnerChoices['btm'] = $playerBtm;
        }

        return $winnerChoices;
    }
}