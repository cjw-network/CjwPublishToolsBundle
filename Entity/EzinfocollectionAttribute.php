<?php

namespace Cjw\PublishToolsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EzinfocollectionAttribute
 *
 * @ORM\Table(name="ezinfocollection_attribute")
 * @ORM\Entity
 */
class EzinfocollectionAttribute
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="contentclass_attribute_id", type="integer", nullable=false, options={"default"=0})
     */
    private $contentclass_attribute_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="contentobject_attribute_id", type="integer", nullable=true)
     */
    private $contentobject_attribute_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="contentobject_id", type="integer", nullable=true)
     */
    private $contentobject_id;

    /**
     * @var float
     *
     * @ORM\Column(name="data_float", type="float", nullable=true)
     */
    private $data_float;

    /**
     * @var integer
     *
     * @ORM\Column(name="data_int", type="integer", nullable=true)
     */
    private $data_int;

    /**
     * @var text
     *
     * @ORM\Column(name="data_text", type="text", nullable=true)
     */
    private $data_text;

    /**
     * @var integer
     *
     * @ORM\Column(name="informationcollection_id", type="integer", nullable=false, options={"default"=0})
     */
    private $informationcollection_id;

    /**
     * Set property
     *
     * @param mixed
     * @return Report
     */
    public function set( $key, $value )
    {
        $this->$key = $value;

        return $this;
    }
}
