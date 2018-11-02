<?php
// src/Controller/CRUDController.php

namespace AdminBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
//use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Common\Annotations\AnnotationReader;
use AdminBundle\Entity\DBLog;
use AdminBundle\Entity\DBLogCorrespondence;

class DBLogCRUDController extends Controller
{

    // allows you to chose your custom showAction template :
    public function getTemplate($name)
    {
        // if ( $name == "show" )
        //     return 'AdminBundle:custom_show.html.twig' ;
        return parent::getTemplate($name);
    }

    /**
     * @param ProxyQueryInterface $selectedModelQuery
     * @param Request             $request
     *
     * @return RedirectResponse
     */
    public function batchActionExecuter(ProxyQueryInterface $selectedModelQuery, Request $request = null)
    {
        $this->admin->checkAccess('edit');
        $this->admin->checkAccess('delete');


        $modelManager = $this->admin->getModelManager();
        $em = $modelManager->getEntityManager($this->admin->getClass());
        // $em = $this->getEntityManager($this->admin->getClass());
        // dump($this->getEntityManager($this->admin->getClass()));
        //dump($modelManager->getRepository($this->admin->getClass()));
        //dump($em);
        // $target = $request->get('idx');
        // $selectedDBLogs = $em->getRepository($this->admin->getClass())
        //     ->findBy(
        //         [$this->admin->getIdParameter() => $request->get('idx')],
        //         [$this->admin->getIdParameter() => 'ASC']
        //     );
        
        // $target = $modelManager->find('AdminBundle:DBLog', 404);
        // dump($target);
        // $target = $modelManager->find($this->admin->getClass(), $request->get('idx'));

        $selectedDBLogs = $selectedModelQuery->execute();

        if ($selectedDBLogs === null || count($selectedDBLogs) == 0) {
            $this->addFlash('sonata_flash_info', 'flash_batch_merge_no_target');

            return new RedirectResponse(
                $this->admin->generateUrl('list', [
                    'filter' => $this->admin->getFilterParameters()
                ])
            );
        }

        //get selected DBLogs (WORK) = list des dblogs = $selectedDBLogs
        //$selectedModels = $selectedModelQuery->execute();
        // do the action work here
        //dump($selectedDBLogs);
        $haserror = false;
        $newEntities = [];
        $docReader = new AnnotationReader();
        $message = "";
        try {
            //$propertyAccessor = PropertyAccess::createPropertyAccessor();
            foreach ($selectedDBLogs as $DBlog) {
            // dump($DBlog);
                $message = "";
                $error = "";
                $actionDB = $DBlog->getAction();

                if ($actionDB == "create" && $DBlog->getPropertyName() != null) {
                    $actionDB = "update";
                }
                $entityDB = null;
                //dump($actionDB);
                switch ($actionDB) {
                    case "create":
                    //try to find entity
                    //dump($DBlog);
                        $entityDB = $em->getRepository($DBlog->getEntityName())->find($DBlog->getEntityId());
                    // dump($entityDB);
                        if ($entityDB === null) {
                        } else {
                            $message = $message . 'Warning: Entity ' . $DBlog->getEntityName() . ' id ' . $DBlog->getEntityId() . ' already exists<br>';
                        }
                    //create it
                        $reflect = new \ReflectionClass($DBlog->getEntityName());
                        $entityDB = $reflect->newInstance();
                    //$em->persist($entityDB);
                        $newEntities[] = array($DBlog, $entityDB);
                        $message = $message . 'OK: Create ' . $DBlog->getEntityName() . ' id ' . $DBlog->getEntityId() . ' (if it is already exists an other id will be defined) <br>';
                    //dump($newEntities);
                        break;


                    case "update":
                        //search entity:
                        $er = $em->getRepository($DBlog->getEntityName());
                    //dump($er);

                    // #1: l'entité à modifier est-elle une entité nouvellement crée?
                        $entityDB = $this->getPersistingEntity($newEntities, $DBlog->getEntityName(), $DBlog->getEntityId());
                    
                    //dump($entityDB);
                        if ($entityDB !== null) {
                            $message = $message . 'OK: Entity ' . $DBlog->getEntityName() . ' in creation found (initial id was ' . $DBlog->getEntityId() . ') <br>';
                        } else { // # SINON
                        // # L'entité à modifier a t'elle été créer par DBLog, auquel cas récupérer sa correspondance locale
                            $entityDB = $this->getCorrespondenceEntity($em, $DBlog->getEnv(), $DBlog->getEntityName(), $DBlog->getEntityId());

                            if ($entityDB != null) {
                                $message = $message . 'OK: Entity ' . $DBlog->getEntityName() . ' id ' . $DBlog->getEntityId() . ' with local correspondance id ' . $entityDB->getId() . ' found<br>';
                            } else {
                            // # Si l'entité n'est ni nouvelle, ni une correspondance, alors récupérer l'entité locale.
                                $entityDB = $er->createQueryBuilder('e')->where('e.id = :id')
                                    ->setParameter('id', $DBlog->getEntityId())
                                    ->getQuery()
                                    ->getOneOrNullResult();

                                if ($entityDB != null) {
                                    $message = $message . 'OK: Entity ' . $DBlog->getEntityName() . ' id ' . $DBlog->getEntityId() . ' found<br>';
                                }
                            }
                        }
                       
                     

                    // dump($entityDB);
                        if ($entityDB === null) {
                            $error = $error . 'KO: Entity ' . $DBlog->getEntityName() . ' id ' . $DBlog->getEntityId() . ' not found<br>';
                            
                            // return new RedirectResponse(
                            //     $this->admin->generateUrl('list', [
                            //         'filter' => $this->admin->getFilterParameters()
                            //     ])
                            // );
                        } else {
                            //Entity found

                            $reflect = new \ReflectionClass($DBlog->getEntityName());
                            //Search Property
                            //if (!property_exists($entityDB, $DBlog->getPropertyName())) {
                            $propertyName = $DBlog->getPropertyName();
                            if (!$reflect->hasProperty($propertyName)) {
                                $error = $error . 'KO: Property ' . $propertyName . ' not found<br>';
                            } else {
                                $message = $message . 'OK: Property ' . $propertyName . ' found<br>';
                                //compare old value with current value
                                $strOldValue = $DBlog->getOldValue();
                            // dump($propertyName);
                                $currentValue = $entityDB->{'get' . $propertyName}();
                            //dump($currentValue);
                                //$currentValue = $propertyAccessor->getValue($entityDB, $DBlog->getPropertyName());
                                $strCurrentValue = $this->formatValueToString($currentValue);
                            // if ($strOldValue == $strCurrentValue) {
                            //     $message = $message . 'OK: OldValue and CurrentValue = ' . $strCurrentValue . ' <br>';
                            // } else {
                            //     dump($strOldValue);
                            //     dump($strCurrentValue);
                            //     $message = $message . 'WARNING: OldValue = ' . $strOldValue . ' != CurrentValue = ' . $strCurrentValue . ' <br>';
                            // }

                                //set value to new value:
                                $strNewValue = $DBlog->getNewValue();
                            // dump($strNewValue);
                                $docInfos = $docReader->getPropertyAnnotations($reflect->getProperty($propertyName));
                            //dump($reflect->getProperty($propertyName));
                            //dump($docInfos);
                            //dump(get_class($docInfos[0]));
                                if (count($docInfos) > 0) {

                                    if (strstr(get_class($docInfos[0]), 'ToOne') === "ToOne") {
                                    //CLASS:ID
                                        $elemInfo = explode(":", $strNewValue);
                                        $np = "";
                                        if (class_exists("AdminBundle\\Entity\\" . $elemInfo[0])) {
                                            $np = "AdminBundle\\Entity\\";
                                        } elseif (class_exists("SiteBundle\\Entity\\" . $elemInfo[0])) {
                                            $np = "SiteBundle\\Entity\\";
                                        } elseif (class_exists("Application\\Sonata\\UserBundle\\Entity\\" . $elemInfo[0])) {
                                            $np = "Application\\Sonata\\UserBundle\\Entity\\";
                                        } elseif (class_exists("Builder\\PageBundle\\Entity\\" . $elemInfo[0])) {
                                            $np = "Builder\\PageBundle\\Entity\\";
                                        } elseif (class_exists("Builder\\FormBundle\\Entity\\" . $elemInfo[0])) {
                                            $np = "Builder\\FormBundle\\Entity\\";
                                        } elseif (class_exists("Builder\\ListBundle\\Entity\\" . $elemInfo[0])) {
                                            $np = "Builder\\Entity\\";
                                        }
                                    
                                    //Search in new entities
                                        $elem = $this->getPersistingEntity($newEntities, $np . $elemInfo[0], $elemInfo[1]);
                                        if ($elem == null) {
                                        //Search with CorrespondenceID if exists
                                            $elem = $this->getCorrespondenceEntity($em, $DBlog->getEnv(), $np . $elemInfo[0], $elemInfo[1]);
                                        }
                                        if ($elem == null) {
                                        //Search entity in DB
                                            $elem = $em->getRepository($np . $elemInfo[0])->find($elemInfo[1]);
                                        }
                                    // dump($elem);
                                        if ($elem == null) {
                                            $error = $error . 'KO: Entity ' . $np . $elemInfo[0] . ' id ' . $elemInfo[1] . ' NOT FOUND when Update Entity ' . $DBlog->getEntityName() . ' id ' . $DBlog->getEntityId() . '  Property ' . $propertyName . '  to value ' . $strNewValue . ' <br>';
                                        } else {
                                            $entityDB->{'set' . $propertyName}($elem);
                                        // dump($entityDB);
                                            $message = $message . 'OK: Update Property ' . $propertyName . ' to value ' . $strNewValue . ' <br>';
                                        }
                                    } elseif (strstr(get_class($docInfos[0]), 'ToMany') === "ToMany") {
                                    //CLASS:ID CLASS:ID
                                        $elemInfoList = explode(" ", $strNewValue);
                                    //dump($elemInfoList);
                                        $listElem = [];
                                        foreach ($elemInfoList as $elemInfo) {
                                            if (trim($elemInfo) != "") {
                                                $elemInfo = explode(":", $elemInfo);
                                            //dump($elemInfo);
                                            //AdminBundle\Entity or SiteBundle\Entity
                                                $np = "";
                                                if (class_exists("AdminBundle\\Entity\\" . $elemInfo[0])) {
                                                    $np = "AdminBundle\\Entity\\";
                                                } elseif (class_exists("SiteBundle\\Entity\\" . $elemInfo[0])) {
                                                    $np = "SiteBundle\\Entity\\";
                                                } elseif (class_exists("Application\\Sonata\\UserBundle\\Entity\\" . $elemInfo[0])) {
                                                    $np = "Application\\Sonata\\UserBundle\\Entity\\";
                                                } elseif (class_exists("Builder\\PageBundle\\Entity\\" . $elemInfo[0])) {
                                                    $np = "Builder\\PageBundle\\Entity\\";
                                                } elseif (class_exists("Builder\\FormBundle\\Entity\\" . $elemInfo[0])) {
                                                    $np = "Builder\\FormBundle\\Entity\\";
                                                } elseif (class_exists("Builder\\ListBundle\\Entity\\" . $elemInfo[0])) {
                                                    $np = "Builder\\Entity\\";
                                                }

                                            //Search in new entities
                                                $elem = $this->getPersistingEntity($newEntities, $np . $elemInfo[0], $elemInfo[1]);
                                                if ($elem == null) {
                                                //Search with CorrespondenceID if exists
                                                    $elem = $this->getCorrespondenceEntity($em, $DBlog->getEnv(), $np . $elemInfo[0], $elemInfo[1]);
                                                }
                                                if ($elem == null) {
                                                //Search entity in DB
                                                    $elem = $em->getRepository($np . $elemInfo[0])->find($elemInfo[1]);
                                                }
                                            
                                            // dump($elem);
                                                if ($elem == null) {
                                                    $error = $error . 'KO: Entity ' . $np . $elemInfo[0] . ' id ' . $elemInfo[1] . ' NOT FOUND when Update Entity ' . $DBlog->getEntityName() . ' id ' . $DBlog->getEntityId() . '  Property ' . $propertyName . '  to value ' . $strNewValue . ' <br>';
                                                } else {
                                                    $listElem[] = $elem;
                                                }

                                            }

                                        }
                                        if ($error == "") {
                                        //no error:
                                            $message = $message . 'OK: Update Property ' . $propertyName . ' of Entity ' . $DBlog->getEntityName() . ' id ' . $DBlog->getEntityId() . ' to value ' . $strNewValue . ' <br>';
                                        //$currentListElem = $entityDB->{'get' . $propertyName}();
                                        // dump($message);
                                        // dump($DBlog);
                                        // dump($propertyName);
                                        // dump($listElem);
                                            $entityDB->{'set' . $propertyName}($listElem);
                                        //dump($entityDB);
                                        }

                                    } elseif ($docInfos[0]->type === 'string'
                                        || $docInfos[0]->type === 'text') {
                                        $entityDB->{'set' . $propertyName}($strNewValue);
                                        $message = $message . 'OK: Update Property ' . $propertyName . ' to value ' . $strNewValue . ' <br>';
                                    } elseif ($docInfos[0]->type === 'integer'
                                        || $docInfos[0]->type === 'float') {
                                        $entityDB->{'set' . $propertyName}($strNewValue + 0);
                                        $message = $message . 'OK: Update Property ' . $propertyName . ' to value ' . $strNewValue . ' <br>';
                                    } elseif ($docInfos[0]->type === 'datetime') {
                                        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $strNewValue);
                                    //formatDate:
                                        $entityDB->{'set' . $propertyName}($datetime);
                                        $message = $message . 'OK: Update Property ' . $propertyName . ' to value ' . $strNewValue . ' <br>';
                                    } elseif ($docInfos[0]->type === 'date') {
                                        $format = 'Y-m-d';
                                        $date = \DateTime::createFromFormat($format, $strNewValue);
                                    //formatDate:
                                        $entityDB->{'set' . $propertyName}($datetime);
                                        $message = $message . 'OK: Update Property ' . $propertyName . ' to value ' . $strNewValue . ' <br>';
                                    } elseif ($docInfos[0]->type === 'boolean') {
                                        if ($strNewValue == "") {
                                            $strNewValue = null;
                                        }
                                        $entityDB->{'set' . $propertyName}($strNewValue);
                                        $message = $message . 'OK: Update Property ' . $propertyName . ' to value ' . $strNewValue . ' <br>';
                                    //dump($DBlog);
                                    } else {

                                        $entityDB->{'set' . $propertyName}($strNewValue);
                                        $message = $message . 'OK: Update Property ' . $propertyName . ' to value ' . $strNewValue . ' <br>';
                                    }
                                }
                            //  else { //CAS ou l'on a pas de docInfo : pour les entités des bundle (user/group/media) les annotations manquent
                            //     //check conversion:
                            //     //datetime:
                            //     if (\DateTime::createFromFormat('Y-m-d H:i:s', $strNewValue)) {
                            //         //isDatetime:
                            //         $entityDB->{'set' . $propertyName}(\DateTime::createFromFormat('Y-m-d H:i:s', $strNewValue));
                            //     } elseif (\DateTime::createFromFormat('Y-m-d', $strNewValue)) {
                            //         //isDate:
                            //         $entityDB->{'set' . $propertyName}(\DateTime::createFromFormat('Y-m-d H:i:s', $strNewValue));
                            //     } else {
                            //         //set direcly:
                            //         $entityDB->{'set' . $propertyName}($strNewValue);
                            //     }
                            // }

                            //dump($entityDB);
                            }
                        }
                        break;
                    case "remove":
                        //dump($actionDB);
                    //Check if it's a remove of new entity:
                        $entityDB = $this->getPersistingEntity($newEntities, $DBlog->getEntityName(), $DBlog->getEntityId());
                        //dump($entityDB);
                        if ($entityDB != null) { //entity will be persist
                            $message = $message . 'DELETE: entity '.$DBlog->getEntityName().':'.$DBlog->getEntityId().' to delete will be persist (delete persist)<br>';
                            //dump($newEntities);
                            for ($d = 0; $d < count($newEntities); $d++) {
                                if ($newEntities[$d][0]->getEntityName() == $DBlog->getEntityName() && $newEntities[$d][0]->getEntityId() == $DBlog->getEntityId()) {
                                    unset($newEntities[$d]);
                                    $message = $message . ' => entity deleted OK';
                                    break;
                                }
                            }
                            //dump($newEntities);
                        } else {
                            // Chercher si l'entité à supprimer a été créer par DBLog, auquel cas récupérer sa correspondance locale
                            $entityDB = $this->getCorrespondenceEntity($em, $DBlog->getEnv(), $DBlog->getEntityName(), $DBlog->getEntityId());
                        
                            //else try to find entity
                            if ($entityDB === null) {
                                $entityDB = $em->getRepository($DBlog->getEntityName())->find($DBlog->getEntityId());
                            }
                            // dump($entityDB);
                            if ($entityDB === null) {
                                $message = $message . 'error entity '.$DBlog->getEntityName().':'.$DBlog->getEntityId().' not found <br>';
                                $error = $error . 'KO:delete Entity ' . $DBlog->getEntityName() . ' id ' . $DBlog->getEntityId() . ' not exists<br>';
                            } else {
                                //delete it:
                                $deleteEntities[$DBlog->getEntityName() . ':' . $DBlog->getEntityId()] = $entityDB;
                                $removeEntityName = $DBlog->getEntityName();
                                $removeEntityId = $DBlog->getEntityId();
                                $message = $message . 'OK: Delete ' . $DBlog->getEntityName() . ' id ' . $DBlog->getEntityId() . ' <br>';
                                $em->remove($entityDB);

                                //Update all DBLogs (NOT NEED => DBLogCorrespondence)
                            }
                        }
                        break;

                }
                //$modelManager->delete($selectedModel);
                if ($error != "") {
                    $haserror = true;
                    $this->addFlash('sonata_flash_alert', $DBlog->getId() . '> ' . $message);
                    $this->addFlash('sonata_flash_error', $DBlog->getId() . '> ' . $error);
                } else {

                    $DBlog->setStatus("migrated");
                    $this->addFlash('sonata_flash_success', $DBlog->getId() . '> ' . $message);
                }
            }

            if (!$haserror) {
                foreach ($newEntities as $newEntityInfo) {
                // dump($newEntityInfo)
                    $newEntity = $newEntityInfo[1];
                    $em->persist($newEntity);
                // for each creation, edit ID of DBLogs with new ID
                // Pour éviter les problemes si on ne fait pas tout d'un coup.
                // Après le flush peut être:
                // Recupérer tous les DBLogs du même env, concernant l'entity
                }
            // dump($message);
                $em->flush();
            //$modelManager->update($selectedModel);
                $this->addFlash('sonata_flash_success', 'Execution effectuée');
            // dump($newEntities);
            
            //#### NEW METHOD: Save DBLogCorrespondence
                foreach ($newEntities as $newEntityInfo) {
                    $DBlog = $newEntityInfo[0];
                    $message = 'NEW (DBLog:' . $DBlog->getId() . ') => Create ' . $DBlog->getEntityName() . ' id distant ' . $DBlog->getEntityId() . ' <br>';
                    if ($DBlog->getEntityId() != $newEntity->getId()) {
                        // dump($DBlog->getEntityId());
                        // dump($newEntity->getId());
                        // dump($newEntityInfo);
                        $newEntity = $newEntityInfo[1];
                    //SAVE CORRESPONDENCE OF ENTITIES ID OF DIFFERENT ENV
                        $DBLogCorrespondence = new DBLogCorrespondence();
                        $DBLogCorrespondence->setEnv($DBlog->getEnv());
                        $DBLogCorrespondence->setEntityName($DBlog->getEntityName());
                        $DBLogCorrespondence->setEntityIdDistant($DBlog->getEntityId());
                        $DBLogCorrespondence->setEntityIdLocal($newEntity->getId());

                        $em->persist($DBLogCorrespondence);
                        $message = $message . 'DBLogCorrespondence saved: env ' . $DBlog->getEnv() . ' entity ' . $DBlog->getEntityName() . ' entityId ' . $DBlog->getEntityId() . '=>' . $newEntity->getId();
                    }
                    $this->addFlash('sonata_flash_success', $message);
                }
            
            //#### OLD METHOD
            // //Foreach creation of entity, update all DBlogs with the new id of created entity
            // foreach ($newEntities as $newEntityInfo) {
            //     $DBlog = $newEntityInfo[0];
            //     $newEntity = $newEntityInfo[1];
            //     //find DBLogs from same plateforme, later than creation:
            //     $repository = $em->getRepository($this->admin->getClass());
            //     $qb = $repository->createQueryBuilder('dblog')
            //         ->where('dblog.env = :env')
            //         //->andwhere('dblog.createdAt >= :createdAt')
            //         //with entityname and id = entityname and oldid
            //         // OR NewValue contains entityname:oldid
            //         ->andwhere('(dblog.entityName = :entityName AND dblog.entityId = :entityId)' .
            //             'OR dblog.newValue LIKE :entityValue')
            //         ->setParameter('env', $DBlog->getEnv())
            //         //->setParameter('createdAt', $DBlog->getCreatedAt())
            //         ->setParameter('entityName', $DBlog->getEntityName())
            //         ->setParameter('entityId', $DBlog->getEntityId() . "")
            //         ->setParameter('entityValue', substr($DBlog->getEntityName(), strpos($DBlog->getEntityName(), "\\Entity\\") + 8) . ':' . $DBlog->getEntityId())
            //         ->getQuery();

            //     $DBLogsToCorrect = $qb->getResult();
            //     //dump($DBLogsToCorrect);
            //     foreach ($DBLogsToCorrect as $DBlogToCorrect) {
            //         $this->addFlash('sonata_flash_success', 'DBLOG to correct:' . $DBlogToCorrect->getId());
            //         //If it's the same entityName and entityID than oldValue
            //         if ($DBlogToCorrect->getEntityName() == $DBlog->getEntityName()
            //             && $DBlogToCorrect->getEntityId() == $DBlog->getEntityId()) {
            //             $this->addFlash('sonata_flash_success', 'DBLOG corrected: entityId ' . $DBlogToCorrect->getEntityId() . '=>' . $newEntity->getId());

            //             $DBlogToCorrect->setEntityId($newEntity->getId());
            //         } else { //the oldEntityId is in NewValue Field
            //             //Set NewValue Entity:Id with new 
            //             // $valueToSearch = substr($DBlog->getEntityName(), strpos($DBlog->getEntityName(), "\\Entity\\") + 8) . ':' . $DBlog->getEntityId();
            //             // if(strpos($valueToSearch, $DBlogToCorrect->getNewValue()) !== false){
            //             $newValue = "";
            //             $elemInfoList = explode(" ", $DBlogToCorrect->getNewValue());
            //             $this->addFlash('sonata_flash_success', 'DBLOG corrected: entityId ' . $DBlog->getEntityId() . '=>' . $newEntity->getId() . ' in ' . $DBlogToCorrect->getNewValue());
            //             foreach ($elemInfoList as $elemInfo) {
            //                 $elemInfo = explode(":", $elemInfo);
            //                 if ($elemInfo[1] == $DBlog->getEntityId() . "") {
            //                     $elemInfo[1] = $newEntity->getId();
            //                 }
            //                 $newValue = $elemInfo[0] . ":" . $elemInfo[1] . "";
            //             }
            //             $DBlogToCorrect->setNewValue(trim($newValue));
            //             // }
            //         }
            //     }
            // }
                $em->flush();
            } else {
                $this->addFlash('sonata_flash_error', 'Echec, Execution annulée');
            }

        } catch (\Exception $e) {

            dump($e);
            $errorinfo = 'CODE:' . $e->getCode() . '</br> MESSAGE: </br>' . $e->getMessage();
            $errorinfo = $errorinfo . '</br> TRACE: </br>' . $e->getTraceAsString();

            $this->addFlash('sonata_flash_error', 'CURRENT MESSAGE: '.$message.'<br>ERREUR SERVER: <br>' . $errorinfo);
            // return new RedirectResponse(
            //     $this->admin->generateUrl('list', [
            //         'filter' => $this->admin->getFilterParameters()
            //     ])
            // );
            //return;
        }



        return new RedirectResponse(
            $this->admin->generateUrl('list', [
                'filter' => $this->admin->getFilterParameters()
            ])
        );
    }

    private function getCorrespondenceEntity($em, $env, $entityName, $entityId)
    {
        $DBLogCorrespondence = $em->getRepository('AdminBundle\Entity\DBLogCorrespondence')
            ->createQueryBuilder('c')
            ->where('c.env = :env')
            ->andWhere('c.entityName = :entityName')
            ->andWhere('c.entityIdDistant = :id')
            ->setParameter('env', $env)
            ->setParameter('entityName', $entityName)
            ->setParameter('id', $entityId . '')
            ->orderby('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($DBLogCorrespondence !== null) {
            $entityDB = $em->getRepository($entityName)
                ->createQueryBuilder('e')
                ->where('e.id = :id')
                ->setParameter('id', $DBLogCorrespondence->getEntityIdLocal())
                ->getQuery()
                ->getOneOrNullResult();

            if ($entityDB != null) {
                return $entityDB;
            }

        }
        return null;

    }

    private function getPersistingEntity($newEntities, $entityName, $entityId)
    {
        // dump($newEntities);
        // dump($entityName);
        // dump($entityId);
        foreach ($newEntities as $newEntityInfo) {
            $DBlog = $newEntityInfo[0];
            $newEntity = $newEntityInfo[1];
            // dump($newEntityInfo);
            if ($DBlog->getEntityName() == $entityName && $DBlog->getEntityId() == $entityId) {
                // dump($newEntity);
                return $newEntity;
            }
        }
        return null;
    }

    private function formatValueToString($value)
    {
        // dump($value);
        $formatedValue = $value;
        $typeOfValue = gettype($value);
        //dump($typeOfValue);
        if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
            $formatedValue = $value->format('Y-m-d H:i:s');
            $typeOfValue = "datetime";
        }
        if (is_array($value)) {
            //dump($typeOfValue);
            $formatedValue = "";
            foreach ($value as $val) {
                $formatedValue = $formatedValue . $this->formatValueToString($val) . " ";
            }
            $typeOfValue = "array";
        }

        
        //if it is persistentCollection
        // $collection_idsarray = $persistentCollection->getValues();
        // $collection_ids = $collection->map(function($e)  {
        //     return $e->getId();
        // })->toArray();

        if ($value && $typeOfValue == "object") {
            $classname = get_class($value);
            //dump($classname);
            $pos = strpos($classname, "\\Entity\\");
            if ($pos !== false) {
                $classname = substr($classname, $pos + 8);
            }
            if (method_exists($value, 'getId')) {

                $formatedValue = $classname . ":" . $value->getId();
            } else {

                if ($classname == "Doctrine\\ORM\\PersistentCollection") {

                    $collection_ids = $value->map(function ($e) {
                        //dump(get_class($e));
                        return substr(get_class($e), strpos(get_class($e), "\\Entity\\") + 8) . ':' . $e->getId();
                    })->toArray();
                    //dump($collection_ids);
                    $formatedValue = implode(' ', $collection_ids);
                    //dump($formatedValue);
                }
                //else
                {
                    
                    // $formatedValue =  $classname .  ":" .  $value;
                }




            }
        }

        //dump( $value);
        if ((!is_array($formatedValue))
            && ((!is_object($formatedValue) && settype($formatedValue, 'string') !== false)
            || (is_object($formatedValue) && method_exists($formatedValue, '__toString')))) {
            return $formatedValue;
        } else {
            // dump($value);
            // dump($formatedValue);
            return $typeOfValue;
        }
    }


    // /**
    //  * EXEMPLE OF NEW ACTION
    //  * @param $id
    //  */
    // public function cloneAction($id)
    // {
    //     $object = $this->admin->getSubject();

    //     if (!$object) {
    //         throw new NotFoundHttpException(sprintf('unable to find the object with id: %s', $id));
    //     }

    //     // Be careful, you may need to overload the __clone method of your object
    //     // to set its id to null !
    //     $clonedObject = clone $object;

    //     $clonedObject->setAction($object->getAction() . ' (Clone)');

    //     $this->admin->create($clonedObject);

    //     $this->addFlash('sonata_flash_success', 'Cloned successfully');

    //     return new RedirectResponse($this->admin->generateUrl('list'));

    //     // if you have a filtered list and want to keep your filters after the redirect
    //     // return new RedirectResponse($this->admin->generateUrl('list', ['filter' => $this->admin->getFilterParameters()]));
    // }
}