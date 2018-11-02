<?php

namespace SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Component\Validator\Constraints as Assert;
//slugable
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Oeuvre
 *
 * @ORM\Table(name="oeuvre")
 * @ORM\Entity(repositoryClass="SiteBundle\Repository\OeuvreRepository")
 * @Vich\Uploadable
 * @ORM\HasLifecycleCallbacks()
 */
class Oeuvre
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
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="artist", referencedColumnName="id", onDelete="SET NULL")
     */
    private $artist;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @Gedmo\Slug(fields={"name"}, updatable=true)
     * @ORM\Column(length=255)
     */
    protected $slug;

    /** Photo
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     * 
     * @Vich\UploadableField(mapping="oeuvre_photo", fileNameProperty="photoName", size="photoSize")
     * 
     * @var File
     */
    private $photoFile;

    /** Photo
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $photoName;

    /** Photo
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var integer
     */
    private $photoSize;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;


    /** Categorie Art
     * @ORM\ManyToMany(targetEntity="Builder\ListBundle\Entity\G_ListItem", cascade={"persist"})
     * @ORM\JoinTable(name="oeuvre_categories_art")
     */ //G_List: categories_art
     private $categoriesArt; //Optionnel

     /**
     * @ORM\ManyToMany(targetEntity="Builder\ListBundle\Entity\G_ListItem", cascade={"persist"})
     * @ORM\JoinTable(name="oeuvre_types_oeuvre")
     */ //G_List: type_oeuvre
    private $typesOeuvre;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @ORM\Column(name="publishedat", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $publishedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="isActive", type="boolean")
     */
    private $isActive;


    /** @ORM\OneToMany(targetEntity="SiteBundle\Entity\Comment", mappedBy="oeuvre", cascade={"persist"}, orphanRemoval=true) */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="SiteBundle\Entity\Image", mappedBy="oeuvre", cascade={"persist"}, orphanRemoval= true)
     * @Assert\Valid()
     */
    private $images;

    public function __construct()
    {
        $this->isActive = true;
       
        $this->publishedAt = new \DateTime();
        $this->comments = new ArrayCollection();
        
        $this->images = new ArrayCollection();
        $this->typesOeuvre =  new ArrayCollection();
        $this->categoriesArt =  new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the value of artist.
     *
     * @return User
     */
    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * Sets the value of artist.
     *
     * @param User $artist
     *
     * @return self
     */
    public function setArtist(User $artist)
    {
        // if ($artist == null) {
        //     $this->artist = null;
        // } else {
            $this->artist = $artist;
        // }

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getSlug()
    {
        return $this->slug;
    }
    
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }
    

    /**
     * Set description
     *
     * @param string $description
     *
     * @return string
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set typesOeuvre
     *
     * @param mixed $typesOeuvre
     *
     * @return G_ListItem
     */
    public function setTypesOeuvre($typesOeuvre)
    {
        $this->typesOeuvre = $typesOeuvre;    
        return $this;
    }    

    /**
     * Get typesOeuvre
     *
     * @return G_ListItem $typesOeuvre
     */
    public function getTypesOeuvre()
    {
        return $this->typesOeuvre;
    }

    /**
     * @param G_ListItem $typesOeuvre
     */
    public function addTypesOeuvre($typeOeuvre)
    {
        $this->typesOeuvre[] = $typeOeuvre;
    }

    /**
     * @param G_ListItem $typesOeuvre
     */
    public function removeTypesOeuvre($typeOeuvre)
    {
        $this->typesOeuvre->removeElement($typeOeuvre);
    }

    /**
     * Set categoriesArt
     *
     * @param mixed $categoriesArt
     *
     * @return G_ListItem
     */
    public function setCategoriesArt($categoriesArt)
    {
        $this->categoriesArt = $categoriesArt;    
        return $this;
    }    

    /**
     * Get categoriesArt
     *
     * @return G_ListItem $categoriesArt
     */
    public function getCategoriesArt()
    {
        return $this->categoriesArt;
    }

    /**
     * @param G_ListItem $categoriesArt
     */
    public function addCategoriesArt($categorieArt)
    {
        $this->categoriesArt[] = $categorieArt;
    }

    /**
     * @param G_ListItem $categoriesArt
     */
    public function removeCategoriesArt($categorieArt)
    {
        $this->categoriesArt->removeElement($categorieArt);
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Oeuvre
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param integer $publishedAt
     *
     * @return Image
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * @return integer|null
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return Oeuvre
     */
    public function setPhotoFile(File $image = null)
    {
        $this->photoFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getPhotoFile()
    {
        return $this->photoFile;
    }


    /**
     * Set photoName
     *
     * @param string $photoName
     *
     * @return Oeuvre
     */
    public function setPhotoName($photoName)
    {
        $this->photoName = $photoName;

        return $this;
    }

    /**
     * Get photoName
     *
     * @return string
     */
    public function getPhotoName()
    {
        return $this->photoName;
    }

    /**
     * Set photoSize
     *
     * @param integer $photoSize
     *
     * @return Oeuvre
     */
    public function setPhotoSize($photoSize)
    {
        $this->photoSize = $photoSize;

        return $this;
    }

    /**
     * Get photoSize
     *
     * @return integer
     */
    public function getPhotoSize()
    {
        return $this->photoSize;
    }

    /**
     * @param integer $updatedAt
     *
     * @return Image
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return integer|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function updateDate()
    {
        $this->setUpdatedAt(new \Datetime());
    }

    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set menu pages
     *
     * @param string $comments
     *
     * @return Page Content
     */
    public function setComments($comments)
    {
        if (count($comments) > 0) {
            foreach ($comments as $i) {
                $this->addComment($i);
            }
        }

        return $this;
    }

    /**
     * Add comments
     *
     * @param string $comments
     *
     * @return Page
     */
    public function addComment($comments)
    {
        $comments->setOeuvre($this);
        $this->comments[] = $comments;

        return $this;
    }

    /**
     * Remove comments
     *
     * @param string $comments
     */
    public function removeComment($comments)
    {
        $this->comments->removeElement($comments);
    }


    public function getImageMapper()
    {
        $array = [
            'photoFile' => 'photoName'
        ];

        return $array;
    }


    public function setImages($images)
    {
        if (count($images) > 0) {
            foreach ($images as $i) {

                $this->addImage($i);
            }
        }
        return $this;
    }

    /**
     * Add image
     *
     * @param Image $image
     *
     */
    public function addImage(Image $image)
    {
        $image->setOeuvre($this);
        $this->images->add($image);
    }

    /**
     * Remove image
     *
     * @param Image $image
     */
    public function removeImage(Image $image)
    {
        $this->images->removeElement($image);
    }

    /**
     * Get images
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getImages()
    {
        return $this->images;
    }


}