<?php
namespace AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Application\Sonata\UserBundle\Entity\User;

/**
 * @ORM\Entity(repositoryClass="AdminBundle\Repository\DBLogCorrespondenceRepository")
 * @ORM\Table(name="db_log_correspondence")
 * @ORM\HasLifecycleCallbacks
 */
class DBLogCorrespondence
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="env",  type="string", length=255, nullable=true)
     */
    private $env;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(name="entityname", type="string", length=255, nullable=true)
     */
    private $entityName;

    /**
     * @ORM\Column(name="entityiddistant", type="integer", nullable=true)
     */
    private $entityIdDistant;

    /**
     * @ORM\Column(name="entityidlocal", type="integer", nullable=true)
     */
    private $entityIdLocal;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set env.
     *
     * @param string|null $env
     *
     * @return DBLogCorrespondence
     */
    public function setEnv($env = null)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Get env.
     *
     * @return string|null
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        if($this->createdAt == null)
        {
            $this->createdAt = new \DateTime();
        }
    }
    
    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return DBLogCorrespondence
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set entityName.
     *
     * @param string|null $entityName
     *
     * @return DBLogCorrespondence
     */
    public function setEntityName($entityName = null)
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * Get entityName.
     *
     * @return string|null
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Set entityIdDistant.
     *
     * @param int|null $entityIdDistant
     *
     * @return DBLogCorrespondence
     */
    public function setEntityIdDistant($entityIdDistant = null)
    {
        $this->entityIdDistant = $entityIdDistant;

        return $this;
    }

    /**
     * Get entityIdDistant.
     *
     * @return int|null
     */
    public function getEntityIdDistant()
    {
        return $this->entityIdDistant;
    }

    /**
     * Set entityIdLocal.
     *
     * @param int|null $entityIdLocal
     *
     * @return DBLogCorrespondence
     */
    public function setEntityIdLocal($entityIdLocal = null)
    {
        $this->entityIdLocal = $entityIdLocal;

        return $this;
    }

    /**
     * Get entityIdLocal.
     *
     * @return int|null
     */
    public function getEntityIdLocal()
    {
        return $this->entityIdLocal;
    }
}
