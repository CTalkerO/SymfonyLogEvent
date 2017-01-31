<?php
/**
 * Created by PhpStorm.
 * User: kimwong
 * Date: 26/8/2016
 * Time: 上午10:21
 */

namespace App\BackBundle\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\DriverManager;


class LogEventListener
{

    protected $container;

    public function __construct(ContainerInterface $container , TokenStorageInterface $storage  , ContainerInterface $containerInterface )
    {
        $this->container = $container;
        $this->userStorage = $storage;
        $this->entityCollection = array(
            //YourBundle::class
        );

        $this->container = $containerInterface;
        $this->config = new \Doctrine\DBAL\Configuration();
        $this->connectionParams = array(
            'dbname' => $this->container->getParameter('database_name'),
            'user' => $this->container->getParameter('database_user'),
            'password' => $this->container->getParameter('database_password'),
            'host' => $this->container->getParameter('database_host'),
            'driver' => $this->container->getParameter('database_driver'),
            'charset' => 'UTF8' ,
        );

    }

    private function isEntitySupported($entity){
        foreach ( $this->entityCollection as $value) {
            if ($entity instanceof $value) {
                return true;
            }
        }
        return false;
    }


    public function OperationLogging($message , $entity ){
        if(!(is_null($message) || $message == '' )){
            $conn = DriverManager::getConnection($this->connectionParams , $this->config);
            $conn->beginTransaction();
            $user = $this->userStorage->getToken();
            $ip = $this->container->get('request')->getClientIp();
            if ( !$user ) return;
            try{
                $conn->transactional ( function ($connection) use ($user , $message , $conn  , $entity , $ip ) {
                    $date = new \DateTime('now');
                    $date =  $date->format('Y-m-d H:i:s');
                    if ( ! $user->getUser() == "anon."  ){
                        $OperatedBy = $user->getUsername();
                    }else{
                        $OperatedBy = "front_user";
                        $project = null;
                    }
                    $class_name = (new \ReflectionClass($entity))->getShortName();
                    $element = $class_name;
                    $event = $message;
                    $array = [
                        "event" => $event ,
                        "ip" => $ip,
                        "operatedBy" => $OperatedBy,
                        "operatedAt" => $date,
                        "element" => $element ,

                    ];
                    $conn->insert('app_oplog', $array);
                });
                $conn->commit();
            }catch (\Exception $e){
                $conn->rollBack();
            }
        }
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        return $this->logChangeSet($eventArgs);
    }


    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $entity =  $eventArgs->getEntity();
        if ($this->isEntitySupported($entity)) {
            $message = $this->getCreateLogMessage($entity);
            $this->OperationLogging($message , $entity );
        }
        return $this->logChangeSet($eventArgs);
    }


    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        $entity =  $eventArgs->getEntity();
        if ($this->isEntitySupported($entity)) {
            $message = $this->getRemoveLogMessage($entity);
            $this->OperationLogging($message ,  $entity);
        }
    }


    /**
     * Logs entity changeset
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function logChangeSet(LifecycleEventArgs $eventArgs)
    {
        $em     =  $eventArgs->getEntityManager();
        $uow    =  $em->getUnitOfWork();
        $entity =  $eventArgs->getEntity();
        $classMetadata = $em->getClassMetadata(get_class($entity));

        if ($this->isEntitySupported($entity)) {
            $uow->computeChangeSet($classMetadata, $entity);
            $changeSet = $uow->getEntityChangeSet($entity);
            $message = $this->getUpdateLogMessage($changeSet, $entity);
            foreach ($uow->getScheduledCollectionUpdates() as $key => $col) {
                $oldRelations = $col->getValues();
                foreach ($oldRelations as  $relation) {
                    $message .= "\n" . sprintf(
                            'property "%s" owned relation "%s"',
                            (new \ReflectionClass($relation))->getShortName(),
                            $relation->getId()
                        );
                }
            }
            $this->OperationLogging($message, $entity);
        }
    }
    
    /**
     * @return string some log informations
     */
    public function getUpdateLogMessage(array $changeSets = [] , $entity )
    {
        $class_name = (new \ReflectionClass($entity))->getShortName();

        $dirty = false;
        $message = [];
        $head = [];
        foreach ($changeSets as $property => $changeSet) {
            for ($i = 0, $s = sizeof($changeSet); $i < $s; $i++) {
                if ($property === "lastLogin"){
                    return null;
                }
                if ($changeSet[$i] instanceof \DateTime) {
                    $changeSet[$i] = $changeSet[$i]->format("Y-m-d H:i:s");
                }
            }

            if ($changeSet[0] != $changeSet[1]) {
                $dirty = true;
                $message[] = sprintf(
                    'property "%s" changed from "%s" to "%s"',
                    $property,
                    !is_array($changeSet[0]) ? $changeSet[0] : "an array",
                    !is_array($changeSet[1]) ? $changeSet[1] : "an array"
                );
            }
        }
        if($dirty) {
            $head[] = sprintf('%s #%d :',
                $class_name,
                $entity->getId()
            );
        }

        return implode("\n", array_merge($head, $message));
    }

    public function getCreateLogMessage($entity)
    {
        $class_name = (new \ReflectionClass($entity))->getShortName();
        return sprintf('%s #%d created', $class_name, $entity->getId() );
    }

    public function getRemoveLogMessage($entity)
    {
        $class_name = (new \ReflectionClass($entity))->getShortName();
        return sprintf('%s #%d removed', $class_name, $entity->getId()  );
    }



}
