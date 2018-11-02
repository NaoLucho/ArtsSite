<?php 
namespace AdminBundle\Events\EventListener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

use AdminBundle\Entity;
use Application\Sonata\UserBundle\Entity\User;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Doctrine\Common\Annotations\AnnotationReader;

//Class pour log les DBLogs, qui se base sur les evenements doctrine
//1: Exclu les logs qui ne sont pas des entités du namespace Builder
//Donc des utilisateurs, groups et médias
//La principale raison est que ces classes sont sans ORM annotations qui rend impossible l'execution des logs
//Il faudrait pour cela ajouter une colonne propertytype dans les Logs. TOBETTER
//2: La colonne OldValue n'est pas toujours rempli, pour les relations ManyToOne ex:Page:rights 
// nous avons des comportements complexe avec les collectionsUpdates et collectionDeletes
// pour récupérer les OLD value est evenements ne se déclanche pas comme on l'attendrait
// quand on vide la collection il la supprime, et il n'y a pas de pre/postUpdate ni de 
// pre/postRemove, seulement les pre/postFlush, qui ne permettent pas d'avoir la oldValue.
class ActionDBListener
{
    private $container;
    //private $em;

    private $logs = []; //log persist, remove and update fields
    private $logs_collectionUpdates = []; //log collectionUpdates
    private $alreadylogs = false;
    private $countLoopLogs = 0;
    //private $prevIdentityMaps = [];
    private $collectionUpdates = [];

    private $saveOldCollectionValues = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        //$this->em = $this->container->get('doctrine')->getEntityManager();
    }

    // public function prePersist(LifecycleEventArgs $args) //1
    // {
    //     $em = $args->getEntityManager();
    //     dump($args); // OF NEW G_ListItem
    //     dump($em->getUnitOfWork());//entityInsertions Empty
    //     //$this->log($em, "prePersist");
    // }

    // public function preFlush(PreFlushEventArgs $args)//2
    // {
    //     $em = $args->getEntityManager();
    //     dump($args);
    //     $uow = $em->getUnitOfWork();
    //     dump($uow);
        
    //     //POSSIBLE de sauver les old value d'une collection que l'on vide ici!
    //     //a save ici, mais comment savoir quoi save! Il faudrait ici save toutes les collections...
    //     //$this->saveOldCollectionValues[$entityname.$entity->getId().$changedProp] = $values->getSnapshot();

    // }

    //LOG REMOVE
    public function preRemove(LifecycleEventArgs $args)//3
    {
        $em = $args->getEntityManager();
        $entity = $args->getEntity();// OF DELETE Entity
        $entityname = $em->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();
        $uow = $em->getUnitOfWork();
        // dump($args);
        // dump($uow);
        if (strpos($entityname, 'Builder') === false 
        || $entityname == "AdminBundle\Entity\DBLog"
        || $entityname == "AdminBundle\Entity\DBLogCorrespondence" ) {
            return;
        }
        //LOG entity, entity->id, "removed"
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!is_object($user)) { //$user can be "anon." if not found
            $user = null;
        }

        $logEntry = new Entity\DBLog();
        $logEntry->setEnv($this->container->getParameter("kernel.environment"));
        
        if ($user) {
            $logEntry->setUser($user);
            $logEntry->setUserName($user->getUsername());
        }

        $logEntry->setEntityName($entityname);
        $logEntry->setEntityId($entity->getId());
        $logEntry->setAction("remove");
        // $logEntry->setPropertyName("id");
        // $logEntry->setOldValue($entity->getId());
        // $logEntry->setNewValue(null);
        $this->logs[] = $logEntry;

        //Direct save activated
        //$em->persist($logEntry);

        //dump($logEntry);

        $this->tempAddCollectionUpdates($uow->getScheduledCollectionUpdates(), "preRemove", $em, $user);
    }

    //LOG PERSIST
    public function postPersist(LifecycleEventArgs $args) //4
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $entity = $args->getEntity();
        //dump($args);
        //dump($uow);

        $entityname = $em->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();
        //dump($entityname);
        if (strpos($entityname, 'Builder') === false || $entityname == "AdminBundle\Entity\DBLog"
        || $entityname == "AdminBundle\Entity\DBLogCorrespondence" ) {
            return;
        }

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!is_object($user)) { //$user can be "anon." if not found
            $user = null;
        }

        $logEntry = new Entity\DBLog();
        $logEntry->setEnv($this->container->getParameter("kernel.environment"));
        
        if ($user) {
            $logEntry->setUser($user);
            $logEntry->setUserName($user->getUsername());
        }

        $entityname = $em->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();
        $logEntry->setEntityName($entityname);
        $logEntry->setEntityId($entity->getId());
        $logEntry->setAction("create");
        $this->logs[] = $logEntry;


        //Direct save activated
        //$em->persist($logEntry);
        
        //log entityChangeSets params:
        $this->addLogEntityChangeSets($em, $user, $entity, "create");

        $this->tempAddCollectionUpdates($uow->getScheduledCollectionUpdates(), "postPersist", $em, $user);
    }

    // public function preUpdate(PreUpdateEventArgs $args) // 5
    // {//getScheduledCollectionUpdates VALIDE
    //     $em = $args->getEntityManager();
    //     $uow = $em->getUnitOfWork();
    //     dump($args);
    //     dump($uow);
    // }

    //LOG Update of Element
    public function postUpdate(LifecycleEventArgs $args) // 6
    {//getScheduledCollectionUpdates VALIDE
        $em = $args->getEntityManager();
        //dump($args);
        $uow = $em->getUnitOfWork();
        //dump($uow);

        $entity = $args->getEntity();
        $entityname = $em->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();
        if (strpos($entityname, 'Builder') === false || $entityname == "AdminBundle\Entity\DBLog"
        || $entityname == "AdminBundle\Entity\DBLogCorrespondence" ) {
            return;
        }
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!is_object($user)) { //$user can be "anon." if not found
            $user = null;
        }
        $this->addLogEntityChangeSets($em, $user, $entity, "update");

        $this->tempAddCollectionUpdates($uow->getScheduledCollectionUpdates(), "postUpdate", $em, $user);
    }

    // public function postRemove(LifecycleEventArgs $args) //7
    // {
    //     $em = $args->getEntityManager();
    //     $uow = $em->getUnitOfWork();
    //     dump($args);
    //     dump($uow);
    // }

    // public function onFlush(OnFlushEventArgs $args) 
    // {
    //     $em = $args->getEntityManager();
    //     $uow = $em->getUnitOfWork();
    //     dump($args);
    //     dump($uow);
    // }

    public function postFlush(PostFlushEventArgs $args) //8 LAST
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        // dump($args);
        // dump($uow);
        if (!$this->container->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }
        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return;
        }
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!is_object($user)) { //$user can be "anon." if not found
            $user = null;
        }
        //dump($this->collectionUpdates);
        //dump($this->logs);

        
        // if ($this->alreadylogs) {
        //     dump($this->alreadylogs);
        //     return;
        // }

        // dump($uow->getScheduledCollectionDeletions());
        //CAS SPECIFIQUE: 
        // quand on vide une liste nous n'avons pas de collectionUpdate, c'est un collectionDelete
        // Et lorsque c'est l'unique action, le PreUpdate et PostUpdate ne se déclenchent pas
        // Nous ne pouvons récupérer l'évenement qu'ici dans le PostFlush
        // Mais la OLD Value n'est alors pas accessible!
        if (count($uow->getScheduledCollectionDeletions()) > 0) {
            //Quand doctrine vide une arrayCollection il la supprime
            //donc il faut log cette information car il n'y a pas de collectionUpdate

            foreach ($uow->getScheduledCollectionDeletions() as $collectionDeletion) {
                $entity = $collectionDeletion->getOwner();
                $entityname = $em->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();

                $propertyName = $collectionDeletion->getMapping()["fieldName"];
                //Verifier s'il n'y a pas d'update de cette collection, dans ce cas la deletion ne doit pas se faire
                $managedByCollectionUpdate = false;
                foreach($this->collectionUpdates as $collectionUpdate)
                {
                    $entityUpdated = $collectionUpdate->getOwner();
                    $propertyNameUpdated = $collectionDeletion->getMapping()["fieldName"];
                    if(get_class($entity) == get_class($entityUpdated)
                    && $entity->getId() == $entityUpdated->getId()
                    && $propertyName == $propertyNameUpdated ) {
                        $managedByCollectionUpdate = true;
                        break;
                    }
                }
                if($managedByCollectionUpdate){
                    break;
                }
                $logEntry = new Entity\DBLog();
                $logEntry->setEnv($this->container->getParameter("kernel.environment"));
                
                if ($user) {
                    $logEntry->setUser($user);
                    $logEntry->setUserName($user->getUsername());
                }

                $logEntry->setEntityName($entityname);
                $logEntry->setEntityId($entity->getId());
                $logEntry->setAction("update");
                
                $logEntry->setPropertyName($propertyName);
                //not possible to get old value?
                //dump($collectionDeletion->getSnapshot());
                //dump($uow->getOriginalEntityData($entity));

                $oldentitylist = [];
                if(array_key_exists($entityname.$entity->getId().$propertyName,$this->saveOldCollectionValues))
                {
                    $oldentitylist = $this->saveOldCollectionValues[$entityname.$entity->getId().$propertyName];
                }
                //dump($oldentitylist);
                $entityIdsOld = "";
                $classname = "";
                foreach ($oldentitylist as $oldentity) {
                    if ($classname == "") {
                        $classname = get_class($oldentity);
                        $pos = strpos($classname, "\\Entity\\");
                        if ($pos !== false) {
                            $classname = substr($classname, $pos + 8);
                        }
                    }
                    $entityIdsOld = $entityIdsOld . $classname . ":" . $oldentity->getId() . " ";
                }
                $logEntry->setOldValue($entityIdsOld);
                $logEntry->setNewValue("");

            // $logEntry->setPropertyName("id");
            // $logEntry->setOldValue($entity->getId());
            // $logEntry->setNewValue(null);

                $this->logs_collectionUpdates[] = $logEntry;
            }
        }
        
        //Save des collections updates 
        //FAIL pour oldvalue:
        //Finalement il faut save les log des collectionUpdates au fur et à mesure (postupdate) car lors du postflush le contenu oldValue n'est plus accessible.
        //$this->saveTempCollectionUpdates($em, $user);

        //Old méthode pour éviter la boucle infini de log les DBlogs
        // if ($this->alreadylogs) {
        //     return;
        // }
        
        //Old > il est possible de faire tous les log à partir de UOW, en une fois
        //NON car nous n'avons plus la possibilité ici de récupérer les OldValue
        //$this->logAll($em, "flush");
        
        // TOUS LES CHANGEMENT SUR LES ENTITES SONT FAIT, 
        // ET LES LOGS SONT BIEN LISTES DANS LES VARIABLES:
        // nettoyer doctrine pour éviter des comportements désastreux qui restent dans uow
        $uow->clear();
        
        // recharger les entités que l'on va utiliser dans la sauvegarde des logs:
        if(is_object($user)){
            $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id'=>$user->getId()));
        }

        // dump($this->logs);
        // dump($this->logs_collectionUpdates);
        if (!empty($this->logs) || !empty($this->logs_collectionUpdates)) {
            //PERSIST
            foreach ($this->logs as $logEntry) {
                //only Builder ADMIN 
                //Exclude logs USER, GROUP, MEDIA ...
                if($user){
                    $logEntry->setUser($user);
                }
                if (!(strpos($logEntry->getEntityName(), 'Builder') === false)) {
                    $em->persist($logEntry);
                }
            }
            foreach ($this->logs_collectionUpdates as $logEntry) {
                //only Builder ADMIN 
                //Exclude logs USER, GROUP, MEDIA ...
                if($user){
                    $logEntry->setUser($user);
                }
                if (!(strpos($logEntry->getEntityName(), 'Builder') === false)) {
                    $em->persist($logEntry);
                }
            }

            $this->logs = [];
            $this->logs_collectionUpdates = [];
            $this->collectionUpdates = [];
            $this->alreadylogs = true; //Evitait le log des log, infini, encore utile?
            //dump($this->alreadylogs);
            $em->flush();

            $uow->clear(); //ne semble plus utile de supprimer les uow 
        }
    }


    private function addLogEntityChangeSets($em, User $user = null, $entity, string $action)
    {
        $uow = $em->getUnitOfWork();
        $entityname = $em->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();
        if (strpos($entityname, 'Builder') === false || $entityname == "AdminBundle\Entity\DBLog"
        || $entityname == "AdminBundle\Entity\DBLogCorrespondence" ) {
            return;
        }
        //dump($entity);
        //LOG Related information
        foreach ($uow->getEntityChangeSet($entity) as $changedProp => $values) {
            //dump($changedProp);
            // dump($values);

            // IF it is an update of collection, a persist collection is updated
            // it will be manage later, to save oldcollection value save it in saveOldCollectionValues
            if (!is_array($values) && get_class($values) == "Doctrine\ORM\PersistentCollection") {
                //check $entityupdatename $propertyName $entityupdate->getOwner()->getId()
                $this->saveOldCollectionValues[$entityname.$entity->getId().$changedProp] = $values->getSnapshot();
                // dump(get_class($values));
                continue;
            }

            // EXCLUDE Update property content of Builder\PageBundle\Entity\Content
            if($this->container->getParameter("kernel.environment") !== "prod"
            && $action == "update" && $entityname == "Builder\PageBundle\Entity\Content" 
            && $changedProp == "content" && $entity->getType() == "ProdContent")
            {
                continue;
            }
            // dump($values[0]);
            // dump($values[1]);
            if ($values[0] != $values[1]) {
                $logEntry = new Entity\DBLog();
                $logEntry->setEnv($this->container->getParameter("kernel.environment"));
                if ($user) {
                    $logEntry->setUser($user);
                    $logEntry->setUserName($user->getUsername());
                }
                $logEntry->setAction($action);

                $logEntry->setEntityName($entityname);
                $logEntry->setEntityId($entity->getId());

                // save value depending type: $values

                //Parcourir les propriétés de l'entité:
                $logEntry->setPropertyName($changedProp);

                $oldvalue = $values[0];
                $newvalue = $values[1];

                $logEntry->setOldValue("" . $this->formatValueToString($oldvalue));
                $logEntry->setNewValue("" . $this->formatValueToString($newvalue));

                //if (!$this->alreadylogs)
                $this->logs[] = $logEntry;

                //Direct save activated
                //$em->persist($logEntry);

                // dump($logEntry);
            }
        }
    }

    private function formatValueToString($value)
    {
        $formatedValue = $value;
        $typeOfValue = gettype($value);

        // dump($value);
        // dump($typeOfValue);

        if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
            $formatedValue = $value->format('Y-m-d H:i:s');
            $typeOfValue = "datetime";
        }
        if (is_array($value)) {
            $formatedValue = "";
            foreach ($value as $val) {
                $formatedValue = $formatedValue . $this->formatValueToString($val) . " ";
            }
            $typeOfValue = "array";
        }

        if ($value && $typeOfValue == "object") {
            $classname = get_class($value);
            $pos = strpos($classname, "\\Entity\\");
            if ($pos !== false) {
                $classname = substr($classname, $pos + 8);
            }
            if (method_exists($value, 'getId')) {

                $formatedValue = $classname . ":" . $value->getId();
            } else {
                // dump(method_exists($value, 'getId'));
                $formatedValue = $classname . ":" . $value;
            }
        }


        // dump($formatedValue);
        if ((!is_array($formatedValue))
            && ((!is_object($formatedValue) && settype($formatedValue, 'string') !== false)
            || (is_object($formatedValue) && method_exists($formatedValue, '__toString')))) {
            return trim($formatedValue);
        } else {
            // dump($value);
            // dump($formatedValue);
            return $typeOfValue;
        }
    }

    private function tempAddCollectionUpdates(array $scheduledCollectionUpdates, string $action, $em, User $user = null)
    {
        //dump($action);
        //dump($scheduledCollectionUpdates);
        foreach ($scheduledCollectionUpdates as $scheduledCollectionUpdate) {
            //check if owner is a class to log
            $entityname = $em->getMetadataFactory()->getMetadataFor(get_class($scheduledCollectionUpdate->getOwner()))->getName();
            if (strpos($entityname, 'Builder') === false || $entityname == "AdminBundle\Entity\DBLog") {
                return;
            }

            //check if already add:
            //dump($scheduledCollectionUpdate->getMapping());
            //dump($scheduledCollectionUpdate->getOwner()->getId());

            if ($scheduledCollectionUpdate->isDirty()) {
                $alreadyAdded = false;
                foreach ($this->collectionUpdates as $collectionUpdateAdded) {
                    if ($scheduledCollectionUpdate->getMapping() === $collectionUpdateAdded->getMapping()
                        && $scheduledCollectionUpdate->getOwner()->getId() == $collectionUpdateAdded->getOwner()->getId()) {
                        $alreadyAdded = true;
                    }
                }
                if (!$alreadyAdded) {
                    //direct add
                    if ($this->saveTempCollectionUpdates($em, $user, $scheduledCollectionUpdate)) {
                        $this->collectionUpdates[] = $scheduledCollectionUpdate;
                    }


                }
            }
        }
        //dump($this->collectionUpdates);
    }

    private function saveTempCollectionUpdates($em, $user, $entityupdate)
    {
        //SAVE UPDATES OF COLLECTION
        // dump($entityupdate);
        //foreach ($this->collectionUpdates as $entityupdate) {
            // dump($entityupdate->getOwner());
            // dump($entity);
            // dump($entityupdate);
            // dump($entityupdate->getOwner());
            // dump($entityupdate->getOwner() === $entity);
            // dump($entityupdate->getMapping());
                //if($entityupdate->getOwner()===$entity) //dans uow il y a aussi les autres updates prévus


        $entityupdatename = $em->getMetadataFactory()->getMetadataFor(get_class($entityupdate->getOwner()))->getName();

            // dump($entityname);
             

        $logEntry = new Entity\DBLog();
        $logEntry->setEnv($this->container->getParameter("kernel.environment"));
        if ($user) {
            $logEntry->setUser($user);
            $logEntry->setUserName($user->getUsername());
        }
        $logEntry->setAction("update");
        $logEntry->setEntityName($entityupdatename);
        $logEntry->setEntityId($entityupdate->getOwner()->getId());


        $entityIdsOld = "";
        $entityIdsNew = "";
        $propertyName = $entityupdate->getMapping()["fieldName"];
        $logEntry->setPropertyName($propertyName);
        //dump($entityupdatename);
        //dump($propertyName);
        //
        //ATTENTION: les relations OneToMany sont géré par le champ MappedBy des objets liés
        //Il est inutile de log le changement, qui ne peut pas être ensuite exe correctement.
        $docReader = new AnnotationReader();


        $reflect = new \ReflectionClass($entityupdate->getOwner());
        if ($entityupdate->getOwner() instanceof \Doctrine\Common\Persistence\Proxy) 
        // This gets the real object, the one that the Proxy extends 
        {
            $reflect = $reflect->getParentClass();

        }

        $docInfos = $docReader->getPropertyAnnotations($reflect->getProperty($propertyName));
        if (strstr(get_class($docInfos[0]), 'OneToMany') === "OneToMany") {
            //
            return false;
        }

        
        $classname = "";

        //valeur du Snapshot pas toujours valide
        //comparer avec la valeur si elle existe dans le changeset:
        $oldentitylist = $entityupdate->getSnapshot();
        if(array_key_exists($entityupdatename.$entityupdate->getOwner()->getId().$propertyName,$this->saveOldCollectionValues))
        {
            $oldentitylist = $this->saveOldCollectionValues[$entityupdatename.$entityupdate->getOwner()->getId().$propertyName];
        }
        //dump($oldentitylist);
        foreach ($oldentitylist as $oldentity) {
            if ($classname == "") {
                $classname = get_class($oldentity);
                $pos = strpos($classname, "\\Entity\\");
                if ($pos !== false) {
                    $classname = substr($classname, $pos + 8);
                }
            }
            $entityIdsOld = $entityIdsOld . $classname . ":" . $oldentity->getId() . " ";
        }
            //dump($entityIdsOld);
    
                    //NEW
            //dump($entityupdate->toArray());
        foreach ($entityupdate->toArray() as $newentity) {
            if ($newentity->getId() == null) {
                    //need to wait persist event for log:
                return false;
            }
            if ($classname == "") {
                $classname = get_class($newentity);
                $pos = strpos($classname, "\\Entity\\");
                if ($pos !== false) {
                    $classname = substr($classname, $pos + 8);
                }
            }
            $entityIdsNew = $entityIdsNew . $classname . ":" . $newentity->getId() . " ";
        }
            //dump($entityIdsNew);

        $logEntry->setOldValue(trim($entityIdsOld));
        $logEntry->setNewValue(trim($entityIdsNew));

        if (!$this->alreadylogs) {
            $this->logs_collectionUpdates[] = $logEntry;

            //Direct save activated
            //$em->persist($logEntry);
        }
        //dump($logEntry);
        return true;
        //}
    }


    // //FULL LOG on postFlush with UOW
    // // First save all changesets, Second save all collectionUpdates
    // // FAIL: collectionUpdates not correct at PostFlush, getSnapshot is not oldValues
    // private function logAll(EntityManager $em, string $action)
    // {
    //     //$em = $args->getEntityManager();

    //     //$entity = $args->getEntity();
    //     //dump($entity);

    //     $user = $this->container->get('security.token_storage')->getToken()->getUser();

    //     $uow = $em->getUnitOfWork();

    //     //dump($this->prevIdentityMaps);
    //     //dump($uow->getIdentityMap());
    //     //$this->prevIdentityMaps = $uow->getIdentityMap();
    //     // // voir https://api.kdyby.org/class-Doctrine.ORM.UnitOfWork.html
    //     foreach ($uow->getIdentityMap() as $typeEntity => $listEntity) {
    //         foreach ($listEntity as $entity) {

    //             $entityname = $em->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();
    //             dump($entityname);
                
    //             //SAVE FIELDS UPDATES:

    //             dump($uow->getEntityChangeSet($entity));
    //             if ($entity instanceof Entity\DBLog || get_class($entity) == "Gedmo\Loggable\Entity\LogEntry") {
    //                 return;
    //             }
    //             //LOG Related information
    //             foreach ($uow->getEntityChangeSet($entity) as $changedProp => $values) {
    //                 dump($changedProp);
    //                 dump($values);
    //                 if ($values[0] != $values[1]) {
    //                     $logEntry = new Entity\DBLog();
    //                     $logEntry->setEnv($this->container->getParameter("kernel.environment"));
    //                     $logEntry->setUser($user);
    //                     if ($user) {
    //                         $logEntry->setUserName($user->getUsername());
    //                     }

    //                     $logEntry->setEntityName($entityname);
    //                     $logEntry->setEntityId($entity->getId());


                    
                    
    //                 // save value depending type: $values


    //                 //Parcourir les propriétés de l'entité:
    //                     $logEntry->setPropertyName($changedProp);

    //                     $oldvalue = $values[0];//$args->getOldValue($changedProp);
    //                     $newvalue = $values[1];//$args->getNewValue($changedProp);




    //                     $typeOfOldValue = gettype($oldvalue);
    //                 //dump($typeOfOldValue);
    //                     if ($oldvalue instanceof \DateTime || $oldvalue instanceof \DateTimeImmutable) {
    //                         $oldvalue = $oldvalue->format('Y-m-d H:i:s');
    //                         $typeOfOldValue = "DateTime";
    //                     }
    //                     if ($oldvalue && $typeOfOldValue == "object") {
    //                         $oldvalue = $oldvalue->getId();
    //                         if ($newvalue) {
    //                             $newvalue = $newvalue->getId();
    //                         }
    //                     }
    //                 //dump($oldvalue);
    //                     $logEntry->setOldValue($oldvalue);

    //                     $typeOfNewValue = gettype($newvalue);
    //                 //dump($typeOfNewValue);
    //                     if ($newvalue instanceof \DateTime || $newvalue instanceof \DateTimeImmutable) {
    //                         $newvalue = $newvalue->format('Y-m-d H:i:s');
    //                         $typeOfNewValue = "DateTime";
    //                     }
    //                     if ($newvalue && $typeOfNewValue == "object") {
    //                         $newvalue = $newvalue->getId();
    //                     }
    //                 //dump($newvalue);
    //                     $logEntry->setNewValue($newvalue);
    //                 //dump($args->getNewValue($changedProp));
    //                     if (!$this->alreadylogs)
    //                         $this->logs[] = $logEntry;
    //                     dump($logEntry);
    //                 }
    //             }
    //         }
    //     }
        
    //     //SAVE UPDATES OF COLLECTION
    //     foreach ($uow->getScheduledCollectionUpdates() as $entityupdate) {
    //         // dump($entityupdate->getOwner());
    //         // dump($entity);
    //         // dump($entityupdate);
    //         // dump($entityupdate->getOwner());
    //         // dump($entityupdate->getOwner() === $entity);
    //         // dump($entityupdate->getMapping());
    //             //if($entityupdate->getOwner()===$entity) //dans uow il y a aussi les autres updates prévus

    //         $entityupdatename = $em->getMetadataFactory()->getMetadataFor(get_class($entityupdate->getOwner()))->getName();

    //         // dump($entityname);
    //         // dump($entityupdatename);
    //         // dump($entityupdate->getMapping()["fieldName"]);

    //         $logEntry = new Entity\DBLog();
    //         $logEntry->setUser($user);
    //         if ($user) {
    //             $logEntry->setUserName($user->getUsername());
    //         }
    //         $logEntry->setEntityName($entityupdatename);
    //         $logEntry->setEntityId($entityupdate->getOwner()->getId());


    //         $entityIdsOld = "";
    //         $entityIdsNew = "";
    //         $logEntry->setPropertyName($entityupdate->getMapping()["fieldName"]);
                    
    //                 //OLD
    //         dump($entityupdate->getSnapshot());
    //         foreach ($entityupdate->getSnapshot() as $oldentity) {
    //             $entityIdsOld = $entityIdsOld . $oldentity->getId() . ";";
    //         }
    //         dump($entityIdsOld);
    
    //                 //NEW
    //         dump($entityupdate->toArray());
    //         foreach ($entityupdate->toArray() as $newentity) {
    //             $entityIdsNew = $entityIdsNew . $newentity->getId() . ";";
    //         }
    //         dump($entityIdsNew);

    //         $logEntry->setOldValue($entityIdsOld);
    //         $logEntry->setNewValue($entityIdsNew);

    //         if (!$this->alreadylogs) {
    //             $this->logs[] = $logEntry;
    //         }
    //         dump($logEntry);

    //     }
    // }


}