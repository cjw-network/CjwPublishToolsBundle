<?php

namespace Cjw\PublishToolsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ezinfocollection
 *
 * @ORM\Table(name="ezinfocollection")
 * @ORM\Entity
 */
class Ezinfocollection
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
     * @ORM\Column(name="contentobject_id", type="integer", nullable=false, options={"default"=0})
     */
    private $contentobject_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer", nullable=false, options={"default"=0})
     */
    private $created;

    /**
     * @var integer
     *
     * @ORM\Column(name="creator_id", type="integer", nullable=false, options={"default"=0})
     */
    private $creator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="modified", type="integer", nullable=true, options={"default"=0})
     */
    private $modified;

    /**
     * @var string
     *
     * @ORM\Column(name="user_identifier", type="string", length=34, nullable=true)
     */
    private $user_identifier;

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

    public function getId()
    {
        return $this->id;
    }
}
