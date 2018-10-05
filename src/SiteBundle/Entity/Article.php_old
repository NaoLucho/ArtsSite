<?php

namespace SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Application\Sonata\UserBundle\Entity\User;
use SiteBundle\Entity\Pdf;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Component\Validator\Constraints as Assert;
//slugable
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Article
 *
 * @ORM\Table(name="article",
 * indexes={@ORM\Index(name="fulltext_article_title", columns={"title"}, flags={"fulltext"}),
 *          @ORM\Index(name="fulltext_article_content", columns={"content"}, flags={"fulltext"})})
 * @ORM\Entity(repositoryClass="SiteBundle\Repository\ArticleRepository")
 * @Vich\Uploadable
 * @ORM\HasLifecycleCallbacks()
 */
class Article
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
     * @ORM\JoinColumn(name="owner", referencedColumnName="id", onDelete="SET NULL")
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, unique=true)
     */
    private $title;

    /**
     * @Gedmo\Slug(fields={"title"}, updatable=true)
     * @ORM\Column(length=255)
     */
    protected $slug;
    //ALTER TABLE article ADD slug VARCHAR(255) NOT NULL;
    //CREATE UNIQUE INDEX UNIQ_23A0E66989D9B62 ON article (slug);

    /** Photo
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     * 
     * @Vich\UploadableField(mapping="article_photo", fileNameProperty="photoName", size="photoSize")
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
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /** Type de l'article
     * @ORM\ManyToOne(targetEntity="Builder\ListBundle\Entity\G_ListItem")
     * @ORM\JoinColumn(name="li_types_articles", referencedColumnName="id", nullable=false )
     */ //G_List: types_articles
     private $typeArticle; //Optionnel

    /**
     * Rights
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\Group")
     * @ORM\JoinColumn(name="resource_rights" )
     */
    private $rights;

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

    /**
     * @var bool
     *
     * @ORM\Column(name="toReview", type="boolean")
     */
    //ALTER TABLE article ADD toReview TINYINT(1) NOT NULL;
    private $toReview;

    /** @ORM\OneToMany(targetEntity="SiteBundle\Entity\Comment", mappedBy="article", cascade={"persist"}, orphanRemoval=true) */
    private $comments;

    /**
     * @ORM\ManyToOne(targetEntity="SiteBundle\Entity\Article", inversedBy="linknext")
     * @ORM\JoinColumn(name="linkprev_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $linkprev;

    /**
     * @ORM\OneToMany(targetEntity="SiteBundle\Entity\Article", mappedBy="linkprev")
     * @Assert\Count(max=1,maxMessage="Selectionnez qu'un seul article")
     * __ORM\JoinColumn(name="linknext_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $linknext;

    /**
     * @var string
     *
     * @ORM\Column(name="seodesc", type="string", length=300, nullable=true)
     */
    private $seoDesc = '';

    /**
     * @var string
     *
     * @ORM\Column(name="seokeywords", type="string", length=300, nullable=true)
     */
    private $seoKeywords = '';

    //ALTER TABLE article ADD seodesc VARCHAR(300) DEFAULT NULL, ADD seoKeywords VARCHAR(300) DEFAULT NULL;

    /**
     * @ORM\OneToMany(targetEntity="SiteBundle\Entity\Pdf", mappedBy="article", cascade={"persist"}, orphanRemoval= true)
     * @Assert\Valid()
     */
    private $pdfs;


    public function __construct()
    {
        $this->isActive = true;
        $this->toReview = false;
        $this->publishedAt = new \DateTime();
        $this->comments = new ArrayCollection();
        $this->pdfs = new ArrayCollection();
        $this->linknext = new ArrayCollection();
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
     * Gets the value of author.
     *
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Sets the value of author.
     *
     * @param User $author
     *
     * @return self
     */
    public function setAuthor(User $author)
    {
        // if ($author == null) {
        //     $this->author = null;
        // } else {
            $this->author = $author;
        // }

        return $this;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Title
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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
     * Set content
     *
     * @param string $content
     *
     * @return Content
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set typeArticle
     *
     * @param \Builder\ListBundle\Entity\G_ListItem $type_article
     *
     * @return Article
     */
    public function setTypeArticle(\Builder\ListBundle\Entity\G_ListItem $typeArticle = null)
    {
        $this->typeArticle = $typeArticle;

        return $this;
    }

    /**
     * Get typeArticle
     *
     * @return \Builder\ListBundle\Entity\G_ListItem
     */
    public function getTypeArticle()
    {
        return $this->typeArticle;
    }

    /**
     * @return mixed
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * Set list rights
     *
     * @param \Doctrine\Common\Collections\Collection $rights
     *
     * @return List rights
     */
    public function setRights($rights)
    {
        $this->rights = $rights;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Article
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
     * Set toReview
     *
     * @param boolean $toReview
     *
     * @return Article
     */
    public function setToReview($toReview)
    {
        $this->toReview = $toReview;

        return $this;
    }

    /**
     * Get toReview
     *
     * @return bool
     */
    public function getToReview()
    {
        return $this->toReview;
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
     * @return Article
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
     * @return Article
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
     * @return Article
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
        $comments->setArticle($this);
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

    /**
     * Gets the value of linkprev.
     *
     * @return Article
     */
    public function getLinkprev()
    {
 
        return $this->linkprev;

    }

    /**
     * Sets the value of linkprev.
     *
     * @param Article $linkprev
     *
     * @return self
     */
    public function setLinkprev($linkprev)
    {
        
        if($linkprev != null)
        {
            //vérifier qu'il n'y ait pas déjà un linkprev, si oui l'enlever.
            // dump($linkprev->getLinknext());
            if(count($linkprev->getLinknext()) > 0 && $linkprev->getLinknext()[0] != null)
            {
                // dump($linkprev->getLinknext()[0]);
                $linkprev->getLinknext()[0]->setLinkprev(null);
            }
        }

        // $this->linkprev[0] = $linkprev;
        // dump($linkprev);
        if ($linkprev == null) {
            $this->linkprev = null;
        } else {
            $this->linkprev = $linkprev;
        }
        return $this;
    }

    /**
     * Gets the value of linknext.
     *
     * @return Article
     */
    public function getLinknext()
    {
        // dump($this->linknext);
        return $this->linknext;
    }

    // public function addLinknext($linknext){
    //     $this->setLinknext($linknext);
    // }
    // /**
    //  * Sets the value of linknext.
    //  *
    //  * @param Article $linknext
    //  *
    //  * @return self
    //  */
    // public function setLinknext( $linknext)
    // {
    //     dump($linknext);
        
    //     // if($linknext != null){
    //     //     $linknext->setLinkprev($this);
    //     // } elseif( $this->linknext != null) {
    //     //     $this->linknext->setLinkprev(null);
    //     // }
    //     // $this->linknext = $linknext;

    //     $this->linknext = [];
    //     if ($linknext != null) {
    //         $this->linknext[0] = $linknext;
    //         $linknext->setLinkprev($this);
    //         dump($this);
    //         dump($linknext);
    //     }
    //     return $this;
    // }


    /**
     * Set seoDesc
     *
     * @param string $seoDesc
     *
     * @return Page
     */
    public function setSeoDesc($seoDesc)
    {
        $this->seoDesc = $seoDesc;

        return $this;
    }

    /**
     * Get seoDesc
     *
     * @return string
     */
    public function getSeoDesc()
    {
        return $this->seoDesc;
    }

    /**
     * Set seoKeywords
     *
     * @param string $seoKeywords
     *
     * @return Page
     */
    public function setSeoKeywords($seoKeywords)
    {
        $this->seoKeywords = $seoKeywords;

        return $this;
    }

    /**
     * Get seoKeywords
     *
     * @return string
     */
    public function getSeoKeywords()
    {
        return $this->seoKeywords;
    }


    public function setPdfs($pdfs)
    {
        if (count($pdfs) > 0) {
            foreach ($pdfs as $i) {

                $this->addPdf($i);
            }
        }
        return $this;
    }

    /**
     * Add resultsPdf
     *
     * @param Pdf $pdf
     *
     */
    public function addPdf(Pdf $pdf)
    {
        $pdf->setArticle($this);
        $this->pdfs->add($pdf);
    }

    /**
     * Remove pdf
     *
     * @param Pdf $pdf
     */
    public function removePdf(Pdf $pdf)
    {
        $this->pdfs->removeElement($pdf);
    }

    /**
     * Get pdfs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPdfs()
    {
        return $this->pdfs;
    }



}