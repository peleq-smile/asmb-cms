<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Visitors\Entity\Visitor;

/**
 * Repository for champioship categories.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class CategoryRepository extends Repository
{
    /**
     * @return array
     */
    public function findAllAsChoices()
    {
        $asChoices = [];
        $categories = $this->findBy([], ['position', 'ASC']);

        /** @var \Bundle\Asmb\Competition\Entity\Championship\Category $category */
        foreach ($categories as $category) {
            $asChoices[$category->getName()] = $category->getName();
        }

        return $asChoices;
    }
}
