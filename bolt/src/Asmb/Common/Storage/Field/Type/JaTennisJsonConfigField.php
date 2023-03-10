<?php

namespace Bundle\Asmb\Common\Storage\Field\Type;

use Bolt\Storage\Field\Type\FieldTypeBase;
use Doctrine\DBAL\Types\Type;

class JaTennisJsonConfigField extends FieldTypeBase
{
    public function getName()
    {
        return 'jatennisjsonconfig';
    }

    public function getTemplate()
    {
        return '@AsmbCommon/field_type/_jatennisjsonconfig.twig';
    }

    public function getStorageType()
    {
        return Type::getType('json');
    }
}